<?php

namespace App\Services\Facility;

use App\Models\Facilities\WorkOrderFacilities;
use App\Models\Engineering\Machine;
use App\Models\Engineering\Plant;
use App\Models\User;
use App\Services\GeneralAffair\GaWhatsappService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\FacilityNotification;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FacilityService
{
    /**
     * LOGIC 1: CREATE TICKET
     */
    public function createTicket(array $data, $file = null)
    {
        return DB::transaction(function () use ($data, $file) {
            $user = auth()->user();
            $userLevel = strtoupper(trim($user->job_level ?? ''));

            // 1. Cek Apakah Pembuat Tiket adalah Boss (SPV/Manager/Head)
            $isBoss = str_contains($userLevel, 'SUPERVISOR') ||
                str_contains($userLevel, 'SPV') ||
                str_contains($userLevel, 'MANAGER') ||
                str_contains($userLevel, 'HEAD') ||
                str_contains($userLevel, 'MGR');

            // 2. Tentukan Status Awal
            // Jika Boss -> Langsung ke Tahap Verifikasi Facility (Bypass Approval)
            // Jika Staff -> Masuk Waiting Approval dulu
            $initialStatus = $isBoss ? 'waiting_facility_approval' : 'waiting_approval';

            // A. Handle File Upload
            $photoPath = $file ? $file->store('wo_facilities', 'public') : null;

            // B. Generate Ticket Number
            $dateCode = date('Ymd');
            $prefix = 'FAC-' . $dateCode . '-';
            $lastTicket = WorkOrderFacilities::where('ticket_num', 'like', $prefix . '%')
                ->orderBy('id', 'desc')->lockForUpdate()->first();
            $newSeq = $lastTicket ? ((int)substr($lastTicket->ticket_num, -3) + 1) : 1;
            $ticketNum = $prefix . sprintf('%03d', $newSeq);

            // C. Logika Mesin (Tetap Sama)
            $machineId = null;
            $machineName = null;
            if ($data['category'] == 'Pemasangan Mesin' && !empty($data['new_machine_name'])) {
                $newMachine = Machine::create([
                    'plant_id' => $data['plant_id'],
                    'name' => $data['new_machine_name'],
                    'code' => 'NEW-' . strtoupper(Str::random(5)),
                ]);
                $machineId = $newMachine->id;
                $machineName = $newMachine->name;
            } else {
                if (!empty($data['machine_id'])) {
                    $m = Machine::find($data['machine_id']);
                    $machineId = $m->id ?? null;
                    $machineName = $m->name ?? null;
                }
            }

            // D. Ambil Nama Plant
            $plantName = Plant::where('id', $data['plant_id'])->value('name') ?? '-';

            // E. Simpan WO
            $ticket = WorkOrderFacilities::create([
                'ticket_num' => $ticketNum,
                'requester_id' => Auth::id(),
                'requester_name' => Auth::user()->name,
                'plant' => $plantName,
                'machine_id' => $machineId,
                'machine_name' => $machineName,
                'location_details' => $data['location_detail'] ?? '-',
                'report_date' => isset($data['report_date']) ? Carbon::parse($data['report_date']) : now(),
                'report_time' => $data['report_time'] ?? now()->format('H:i'),
                'shift' => $data['shift'] ?? '-',
                'description' => $data['description'],
                'category' => $data['category'],
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'photo_path' => $photoPath,

                // [PENTING] Status sesuai level user
                'status' => $initialStatus
            ]);

            // F. NOTIFIKASI (LOGIC BYPASS)

            if ($isBoss) {
                // KASUS 1: BOSS YANG BUAT
                // Tidak perlu kirim ke Approver (diri sendiri/setara).
                // Langsung Info ke Admin Facility bahwa ada tiket masuk minta verifikasi.
                $this->notifyAdmins($ticket, 'fh_new'); // fh_new = Trigger WA "Perlu Verifikasi"

                $message = 'Tiket berhasil dibuat (Auto-Approve). Menunggu Verifikasi Facility.';
                \Log::info("✅ TICKET BYPASS: Created by Boss ({$user->name}) -> Skip Approval.");
            } else {
                // KASUS 2: STAFF BIASA YANG BUAT
                // Kirim ke Approver (SPV/Manager)
                $this->notifyApprovers($ticket, $plantName);

                // Info ke Admin (Sekadar info ada tiket baru, belum butuh aksi)
                $this->notifyAdmins($ticket, 'new_ticket');

                $message = 'Tiket berhasil dibuat. Menunggu persetujuan Atasan.';
            }

            // Info ke Diri Sendiri (Email Confirm)
            $this->safeMail($user->email, new FacilityNotification($ticket, 'created_info'));

            return [
                'success' => true,
                'message' => $message,
                'data' => $ticket
            ];
        });
    }

    /**
     * LOGIC: APPROVE TICKET
     */
    public function approveTicket($id)
    {
        $ticket = WorkOrderFacilities::find($id);
        if (!$ticket) return ['success' => false, 'message' => 'Tiket tidak ditemukan.'];

        $user = auth()->user();

        // Cek Admin (Facility / Super / MV Admin)
        $isAdmin = in_array($user->role, ['fh.admin', 'super.admin', 'mv.admin']) || $user->divisi === 'Facility';

        // ------------------------------------------------------------------
        // KASUS 1: TAHAP VERIFIKASI (Tiket sudah diapprove SPV -> Cek Admin)
        // ------------------------------------------------------------------
        if ($ticket->status == 'waiting_facility_approval') {
            if ($isAdmin) {
                // Admin memverifikasi tiket -> Status jadi PENDING (Siap dikerjakan)
                $ticket->update(['status' => 'pending', 'updated_at' => now()]);

                // Notif ke Requester bahwa tiket sudah diterima Facility
                $this->notifyRequester($ticket, 'status_update');

                \Log::info("✅ FACILITY VERIFIED: {$user->name} verified ticket {$ticket->ticket_num}");

                return ['success' => true, 'message' => 'Tiket Terverifikasi (Pending). Siap dikerjakan Teknisi.'];
            } else {
                return ['success' => false, 'message' => 'Hanya Admin Facility yang bisa memverifikasi di tahap ini.'];
            }
        }

        // ------------------------------------------------------------------
        // KASUS 2: TAHAP APPROVAL MANAGER (Tiket Baru)
        // ------------------------------------------------------------------
        if ($ticket->status == 'waiting_approval') {

            // A. BYPASS ADMIN
            // Jika Admin yang approve di tahap awal, langsung lolos ke tahap verifikasi
            if ($isAdmin) {
                $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);

                $this->notifyRequester($ticket, 'status_update');

                // PENTING: Panggil notifyAdmins agar Admin lain/diri sendiri dapat notif WA
                $this->notifyAdmins($ticket, 'fh_new');

                \Log::info("✅ APPROVE BYPASS: {$user->name} approved ticket {$ticket->ticket_num}");

                return ['success' => true, 'message' => 'Disetujui Admin (Bypass). Menunggu Verifikasi Akhir.'];
            }

            // B. LOGIC MATRIX (SPV/MANAGER)
            if ($this->checkApprovalMatrix($ticket->plant, $user)) {

                // 1. Update Status
                $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);

                // 2. Notif ke Requester (Bahwa tiketnya sudah diapprove bosnya)
                $this->notifyRequester($ticket, 'status_update');

                // 3. [PENTING] Notif ke FACILITY ADMIN (Panggil function di atas)
                $this->notifyAdmins($ticket, 'fh_new');

                \Log::info("✅ APPROVE MATRIX: {$user->name} approved {$ticket->ticket_num}");

                return ['success' => true, 'message' => 'Disetujui. Notifikasi telah dikirim ke Tim Facility.'];
            }

            // GAGAL
            \Log::warning("⛔ APPROVE FAIL: {$user->name} (Div: {$user->divisi}) tried to approve {$ticket->plant}");
            return ['success' => false, 'message' => "Gagal. Divisi Anda ({$user->divisi}) tidak sesuai dengan Matrix Approval area {$ticket->plant}."];
        }

        return ['success' => false, 'message' => 'Status tiket tidak valid.'];
    }

    public function rejectTicket($id, $reason)
    {
        $ticket = WorkOrderFacilities::findOrFail($id);

        $ticket->update([
            'status' => 'rejected',
            'rejection_reason' => $reason . ' (Rejected by ' . Auth::user()->name . ')',
            'actual_completion_date' => null // Reset tanggal jika ada
        ]);

        // 1. Notif Email
        $this->notifyRequester($ticket, 'status_update');

        // 2. Notif WA (Manual Add)
        $requester = User::find($ticket->requester_id);
        if ($requester && $requester->no_hp) {
            $link = url('/facility/' . $ticket->id);
            $msg = "🔧 *WORK ORDER FACILITY*\n" .
                "👋 Halo,\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n" .
                "🚫 *TIKET DITOLAK*\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "📋 *Detail Penolakan*\n" .
                "┣ Nomor: `{$ticket->ticket_num}`\n" .
                "┗ Ditolak oleh: " . Auth::user()->name . "\n\n" .
                "💬 *Alasan Penolakan:*\n" .
                "_{$reason}_\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n" .
                "ℹ️ Silakan ajukan tiket baru jika diperlukan\n" .
                "━━━━━━━━━━━━━━━━━━━━━";
            try {
                GaWhatsappService::send($requester->no_hp, $msg);
                \Log::info("✅ WA REJECT SENT to {$requester->name}");
            } catch (\Exception $e) {
                \Log::error("❌ WA REJECT FAILED: " . $e->getMessage());
            }
        }

        \Log::info("🚫 TICKET REJECTED: {$ticket->ticket_num} by " . Auth::user()->name);

        return ['success' => true, 'message' => 'Ticket Rejected.'];
    }

    public function updateStatus($id, array $data)
    {
        $wo = WorkOrderFacilities::findOrFail($id);
        $wo->status = $data['status'];

        // 1. Sync Teknisi
        if (isset($data['facility_tech_ids'])) {
            $ids = $data['facility_tech_ids'];
            if (!is_array($ids)) $ids = explode(',', (string)$ids);
            // Filter ID valid
            $ids = array_filter($ids, fn($val) => is_numeric($val) && $val > 0);
            $wo->technicians()->sync($ids);
        }

        // 2. Update Start Date
        if (!empty($data['start_date'])) {
            $wo->start_date = $data['start_date'];
        }

        // 3. Update Completion Date
        if ($data['status'] == 'completed') {
            $wo->actual_completion_date = $wo->actual_completion_date ?? now();
        } elseif ($data['status'] != 'completed') {
            $wo->actual_completion_date = null;
        }

        // 4. Set Processed By (Jika belum ada)
        if (!$wo->processed_by) {
            $wo->processed_by = Auth::id();
            $wo->processed_by_name = Auth::user()->name;
        }

        $wo->save();

        // 5. Notifikasi WA ke Requester (DENGAN LOG)
        $requester = User::find($wo->requester_id);

        if ($requester && $requester->no_hp) {
            $msg = "";
            // $link = url('/facility/' . $wo->id); // Generate Link

            switch ($data['status']) {
                case 'in_progress':
                    $msg = "🔧 *WORK ORDER FACILITY*\n\n" .
                        "╔═══════════════════════╗\n" .
                        "║   🛠 *STATUS UPDATE*   ║\n" .
                        "╚═══════════════════════╝\n\n" .
                        "📋 Tiket: *{$wo->ticket_num}*\n" .
                        "📊 Status: *IN PROGRESS*\n\n" .
                        "⏳ Teknisi sedang bekerja\n" .
                        "Mohon menunggu...";
                    break;

                case 'completed':
                    $msg = "🔧 *WORK ORDER FACILITY*\n\n" .
                        "╔═══════════════════════╗\n" .
                        "║   ✅ *COMPLETED*      ║\n" .
                        "╚═══════════════════════╝\n\n" .
                        "📋 Tiket: *{$wo->ticket_num}*\n\n" .
                        "🎉 Pekerjaan selesai!\n\n" .
                        "⭐ Beri rating sekarang:\n" .
                        "🔗 $link";
                    break;

                case 'cancelled':
                case 'rejected':
                    $statusLabel = strtoupper($data['status']);
                    $msg = "🔧 *WORK ORDER FACILITY*\n\n" .
                        "╔═══════════════════════╗\n" .
                        "║   🚫 *{$statusLabel}*      ║\n" .
                        "╚═══════════════════════╝\n\n" .
                        "📋 Tiket: *{$wo->ticket_num}*\n\n" .
                        "ℹ️ Tiket dibatalkan/ditolak\n" .
                        "💬 Hubungi admin jika perlu";
                    break;
            }

            if ($msg) {
                try {
                    GaWhatsappService::send($requester->no_hp, $msg);

                    // [LOG SUKSES]
                    \Log::info("✅ WA STATUS SENT to Requester: {$requester->name} | Status: {$data['status']}");
                } catch (\Exception $e) {
                    // [LOG ERROR]
                    \Log::error("❌ WA STATUS FAILED to Requester: {$requester->name} | Error: " . $e->getMessage());
                }
            }
        } elseif ($requester) {
            // [LOG SKIP]
            \Log::warning("⚠️ WA SKIP: Requester {$requester->name} tidak memiliki No HP.");
        }

        // Email Notif (Bawaan lama)
        $this->notifyRequester($wo, 'status_update');

        return $wo;
    }

    /**
     * LOGIC: GET DASHBOARD STATS (Optimized)
     */
    public function getDashboardStats($request)
    {
        $query = WorkOrderFacilities::where('status', '!=', 'cancelled');

        if ($request->filled('month')) {
            try {
                $start = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()->format('Y-m-d');
                $end = Carbon::createFromFormat('Y-m', $request->month)->endOfMonth()->format('Y-m-d');
                $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
            } catch (\Exception $e) {
            }
        }

        // Eager Loading relations untuk mencegah N+1
        $workOrders = $query->with(['technicians', 'machine', 'user'])->latest()->get();

        // Prepare Chart Data
        $categoryGrouped = $workOrders->groupBy('category');
        $statusGrouped = $workOrders->groupBy('status');
        $plantGrouped = $workOrders->groupBy('plant');

        $stats = [
            'workOrders'    => $workOrders,
            'countTotal'    => $workOrders->count(),
            'countPending'  => $workOrders->where('status', 'pending')->count(),
            'countProgress' => $workOrders->where('status', 'in_progress')->count(),
            'countDone'     => $workOrders->where('status', 'completed')->count(),

            // Charts - Convert to arrays
            'chartCatLabels'    => $categoryGrouped->keys()->values()->toArray(),
            'chartCatValues'    => $categoryGrouped->map->count()->values()->toArray(),
            'chartStatusLabels' => $statusGrouped->keys()->values()->toArray(),
            'chartStatusValues' => $statusGrouped->map->count()->values()->toArray(),
            'chartPlantLabels'  => $plantGrouped->keys()->values()->toArray(),
            'chartPlantValues'  => $plantGrouped->map->count()->values()->toArray(),
        ];

        // Hitung Teknisi (In-Memory Processing)
        $techData = [];
        foreach ($workOrders as $wo) {
            foreach ($wo->technicians as $tech) {
                $techData[$tech->name] = ($techData[$tech->name] ?? 0) + 1;
            }
        }
        arsort($techData);
        $stats['chartTechLabels'] = array_keys($techData);
        $stats['chartTechValues'] = array_values($techData);

        // Gantt Data (Reuse logic via method to avoid duplication)
        // Kita panggil method getGanttChartData secara internal tapi pass data yg sudah di-load
        // agar tidak query ulang
        $stats['ganttData'] = $this->formatGanttData($workOrders);
        $stats['selectedMonth'] = $request->input('month', date('Y-m'));

        return $stats;
    }

    /**
     * LOGIC: GANTT CHART DATA (Requested Method)
     * method ini dipanggil oleh FacilitiesController
     */
    public function getGanttChartData()
    {
        // Limit 100 terakhir agar tidak berat
        $tickets = WorkOrderFacilities::with(['technicians'])
            ->latest()
            ->limit(100)
            ->get();

        return $this->formatGanttData($tickets);
    }

    /**
     * Helper Formatter Gantt (Clean Code)
     */
    private function formatGanttData($collection)
    {
        $ganttData = [];
        foreach ($collection as $wo) {
            $start = $wo->created_at ? $wo->created_at->format('Y-m-d') : date('Y-m-d');
            $end = ($wo->status == 'completed' && $wo->actual_completion_date)
                ? Carbon::parse($wo->actual_completion_date)->format('Y-m-d')
                : ($wo->target_completion_date ?? date('Y-m-d'));

            if ($end < $start) $end = $start;

            $ganttData[] = [
                // --- STANDAR DHTMLX (Untuk Grafik) ---
                'id' => $wo->id,
                'text' => $wo->ticket_num . ' - ' . Str::limit($wo->description, 20),
                'start_date' => $start,
                'duration' => Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1,
                'progress' => ($wo->status == 'completed') ? 1 : (($wo->status == 'in_progress') ? 0.5 : 0),
                'color' => $this->getStatusColor($wo->status),
                'open' => true,

                // --- COMPATIBILITY KEYS (Agar View Blade Tidak Error) ---
                'start' => $start,         // <--- INI YANG DICARI VIEW ANDA
                'end' => $end,             // <--- INI JUGA MUNGKIN DICARI
                'ticket' => $wo->ticket_num,
                'machine_name' => $wo->machine_name ?? '-',
                'category' => $wo->category ?? '-',
                'plant' => $wo->plant ?? '-',
                'status' => $wo->status,
            ];
        }

        return ['data' => $ganttData, 'links' => []];
    }

    private function getStatusColor($status)
    {
        return match ($status) {
            'completed' => '#10b981', // Green
            'in_progress' => '#3b82f6', // Blue
            'pending' => '#f59e0b', // Orange
            'rejected' => '#ef4444', // Red
            default => '#6b7280' // Gray
        };
    }

    /**
     * --- NOTIFICATION HELPERS (Cleaned Up) ---
     */

    private function safeMail(?string $to, $mailable): void
    {
        if (empty($to)) return;
        try {
            Mail::to($to)->send($mailable);
            Log::info("Facility Email sent to: $to");
        } catch (\Exception $e) {
            Log::error('Mail Error (Facility): ' . $e->getMessage());
        }
    }

    private function notifyRequester($ticket, $type)
    {
        $requester = User::find($ticket->requester_id);
        if ($requester && $requester->email) {
            $this->safeMail($requester->email, new FacilityNotification($ticket, $type));
        }
    }

    private function notifyAdmins($ticket, $type)
    {
        // 1. Cari User Admin/Manager FH
        $recipients = User::where('is_active', 1)
            ->where(function ($q) {
                $q->whereIn('role', ['fh.admin', 'fh.manager', 'super.admin'])
                    ->orWhere('divisi', 'Facility');
            })
            ->get();

        $link = url('/facility/' . $ticket->id);

        foreach ($recipients as $admin) {

            // A. Kirim Email
            $this->safeMail($admin->email, new FacilityNotification($ticket, $type));

            // B. Kirim WhatsApp dengan LOGGING LENGKAP
            if ($admin->no_hp) {
                $msg = "";

                if ($type == 'fh_new') {
                    $msg = "🔧 *WORK ORDER FACILITY*\n" .
                        "👋 Halo Tim FH,\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n" .
                        "🔔 *TIKET PERLU VERIFIKASI*\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "📋 *Detail Tiket*\n" .
                        "┣ Nomor: `{$ticket->ticket_num}`\n" .
                        "┣ User: {$ticket->requester_name}\n" .
                        "┣ Plant: {$ticket->plant}\n" .
                        "┣ Status: ✅ Approved SPV/Manager\n\n" .
                        "┣ Category: {$ticket->category}\n\n" .
                        "┗ Mesin: {$ticket->machine_name}\n\n" .
                        "🛠 *Deskirpsi:*\n" .
                        "_{$ticket->description}_\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━";
                } elseif ($type == 'new_ticket') {
                    $msg = "🔧 *WORK ORDER FACILITY*\n" .
                        "👋 Halo,\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n" .
                        "🆕 *INFO TIKET BARU*\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "📋 *Detail Tiket*\n" .
                        "┣ Nomor: `{$ticket->ticket_num}`\n" .
                        "┣ Requester: {$ticket->requester_name}\n" .
                        "┗ Status: ⏳ Menunggu Approval\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n" .
                        "ℹ️ Notifikasi ini bersifat informasi\n" .
                        "━━━━━━━━━━━━━━━━━━━━━";
                }

                if ($msg) {
                    try {
                        GaWhatsappService::send($admin->no_hp, $msg);
                        // [LOG SUKSES]
                        \Log::info("✅ WA ADMIN SENT to: {$admin->name} | Type: $type");
                    } catch (\Exception $e) {
                        // [LOG ERROR - GAGAL KIRIM]
                        \Log::error("❌ WA ADMIN FAILED to: {$admin->name} | Error: " . $e->getMessage());
                    }
                }
            } else {
                // [LOG WARNING - TIDAK ADA NOMOR HP]
                // Ini penting agar Anda tahu kenapa admin tertentu tidak dapat WA
                \Log::warning("⚠️ WA ADMIN SKIP: User '{$admin->name}' tidak memiliki No HP. (Type: $type)");
            }
        }
    }

    private function notifyApprovers($ticket, $plantName)
    {
        $matrix = $this->getFacilityMatrix();
        $config = $matrix[$plantName] ?? null;

        $query = User::where('is_active', 1);

        if ($config) {
            // [FIX] Menggunakan Logic Matrix
            $query->where(function ($q) use ($config) {
                // A. Cek Supervisor
                $q->orWhere(function ($sub) use ($config) {
                    // PERBAIKAN: Gunakan whereIn (bukan where) untuk array
                    $sub->whereIn('job_level', ['SUPERVISOR', 'SPV'])
                        ->whereIn('divisi', $config['spv']);
                });

                // B. Cek Manager
                $q->orWhere(function ($sub) use ($config) {
                    $sub->whereIn('job_level', ['MANAGER', 'HEAD', 'MGR'])
                        ->whereIn('divisi', $config['mgr']);
                });
            });
        } else {
            // [FALLBACK] Logic Keyword (Jika tidak ada di matrix)
            $aliases = $this->getPlantAliases($plantName);

            // PERBAIKAN: Cari Supervisor DAN Manager (jangan cuma Supervisor)
            $query->whereIn('job_level', ['SUPERVISOR', 'SPV', 'MANAGER', 'HEAD', 'MGR'])
                ->where(function ($q) use ($aliases) {
                    foreach ($aliases as $alias) {
                        $q->orWhere('divisi', 'LIKE', '%' . $alias . '%');
                    }
                });
        }

        $approvers = $query->get();

        // Generate Link Approval (Penting agar bisa diklik)
        // $approvalLink = url('/fh/' . $ticket->id); // Sesuaikan route Anda

        foreach ($approvers as $approver) {
            // 1. Kirim Email
            $this->safeMail($approver->email, new FacilityNotification($ticket, 'need_approval'));

            // 2. Kirim WA dengan LOGGING
            if ($approver->no_hp) {
                $msg = "═══════════════════════\n" .
                    "🔧 *WORK ORDER FACILITY*\n" .
                    "═══════════════════════\n\n" .
                    "👋 Halo *{$approver->name}*,\n\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "🔔 *APPROVAL DIPERLUKAN*\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "📋 *Detail Tiket*\n" .
                    "┣ Nomor: `{$ticket->ticket_num}`\n" .
                    "┣ Lokasi: {$ticket->plant}\n" .
                    "┣ Area: {$ticket->location_details}\n" .
                    "┗ Kategori: {$ticket->category}\n\n" .
                    "💬 *Masalah:*\n" .
                    "_{$ticket->description}_\n\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "⚡ Mohon segera diapprove. Terima kasih!\n" .
                    "━━━━━━━━━━━━━━━━━━━━━";

                try {
                    GaWhatsappService::send($approver->no_hp, $msg);

                    // [LOG SUKSES]
                    \Log::info("✅ WA FACILITY SENT to Approver: {$approver->name} ({$approver->no_hp}) | Ticket: {$ticket->ticket_num}");
                } catch (\Exception $e) {
                    // [LOG ERROR]
                    \Log::error("❌ WA FACILITY FAILED to Approver: {$approver->name} | Error: " . $e->getMessage());
                }
            } else {
                \Log::warning("⚠️ WA SKIP: Approver {$approver->name} tidak memiliki No HP.");
            }
        }
    }

    /**
     * SMART MAPPING: Logika Hierarki Jembo Cable
     */
    private function getPlantAliases(string $plantName): array
    {
        $cleanName = strtoupper(trim($plantName));

        if (str_contains($cleanName, 'AUTOWIRE') || str_contains($cleanName, 'AUTO WIRE')) {
            return ['Autowire', 'Manager LV', 'Low Voltage'];
        }
        if (str_contains($cleanName, 'CCV')) {
            return ['CCV', 'Manager MV', 'Medium Voltage'];
        }
        if (str_contains($cleanName, 'PLANT A')) return ['LV A', 'Manager LV', 'Plant A'];
        if (str_contains($cleanName, 'PLANT C')) return ['LV C', 'Manager LV', 'Plant C'];
        if (str_contains($cleanName, 'PLANT B')) return ['MV B', 'Manager MV', 'Plant B'];
        if (str_contains($cleanName, 'PLANT D')) return ['MV D', 'Manager MV', 'Plant D'];
        if (str_contains($cleanName, 'PLANT E')) return ['FO', 'Manager FO', 'Plant E', 'Fiber Optic'];

        return [$plantName];
    }

    // check approval matrix
    /**
     * CORE LOGIC: Cek Approval dengan Debugging
     */
    private function checkApprovalMatrix($ticketPlant, $user)
    {
        // Data User
        $userDivisi  = strtoupper(trim($user->divisi ?? ''));
        $userLevel   = strtoupper(trim($user->job_level ?? ''));
        $userJabatan = strtoupper(trim($user->jabatan ?? '')); // [BARU] Ambil Jabatan

        $matrix = $this->getFacilityMatrix();
        $config = $matrix[$ticketPlant] ?? null;

        // 1. STRICT MATRIX CHECK
        if ($config) {
            // A. Cek Supervisor
            if (str_contains($userLevel, 'SUPERVISOR') || str_contains($userLevel, 'SPV')) {
                foreach ($config['spv'] as $keyword) {
                    $key = strtoupper($keyword);
                    // Cek apakah Keyword (misal: "CCV") ada di Divisi ATAU Jabatan user
                    if (str_contains($userDivisi, $key) || str_contains($userJabatan, $key)) {
                        return true;
                    }
                }
            }

            // B. Cek Manager
            if (str_contains($userLevel, 'MANAGER') || str_contains($userLevel, 'HEAD')) {
                foreach ($config['mgr'] as $keyword) {
                    $key = strtoupper($keyword);
                    if (str_contains($userDivisi, $key) || str_contains($userJabatan, $key)) {
                        return true;
                    }
                }
            }

            // Jika User masuk kategori SPV/Manager tapi keywordnya ga ketemu -> TOLAK
            return false;
        }

        // 2. FALLBACK
        return str_contains($userDivisi, strtoupper($ticketPlant));
    }

    private function getFacilityMatrix()
    {
        return [
            'Plant D - CCV' => [
                'spv' => ['CCV Line', 'SUPERVISOR CCV'],
                'mgr' => ['MV D', 'Medium Voltage']
            ],
            'Plant D' => [
                'spv' => ['MV D', 'Medium Voltage', 'PLANT D'],
                'mgr' => ['MV D', 'Medium Voltage']
            ],
            'Plant A - Autowire' => [
                'spv' => ['SUPERVISOR AUTOWIRE', 'PLANT A'],
                'mgr' => ['Low Voltage', 'LV']
            ],
            'Plant A' => [
                'spv' => ['LV A', 'Low Voltage', 'PLANT A'],
                'mgr' => ['Low Voltage', 'LV']
            ],
            'Plant B' => ['spv' => ['MV B', 'PLANT B'], 'mgr' => ['MV', 'Medium Voltage']],
            'Plant C' => ['spv' => ['LV C', 'PLANT C'], 'mgr' => ['LV']],
            'Plant E' => ['spv' => ['FO', 'PLANT E'], 'mgr' => ['FO']]
        ];
    }
}
