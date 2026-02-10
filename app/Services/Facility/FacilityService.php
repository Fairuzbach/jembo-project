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

            // 1. CEK ROLE ADMIN (PRIORITAS UTAMA)
            // Cek apakah dia fh.admin
            $isAdmin = ($user->role === 'fh.admin' ||
                $user->role === 'super.fh.admin' ||
                $user->role === 'super.admin');

            // 2. CEK LEVEL BOSS
            $isBoss = str_contains($userLevel, 'SUPERVISOR') ||
                str_contains($userLevel, 'SPV') ||
                str_contains($userLevel, 'MANAGER') ||
                str_contains($userLevel, 'HEAD') ||
                str_contains($userLevel, 'MGR');

            // 3. TENTUKAN STATUS AWAL (HIERARKI LOGIC)
            if ($isAdmin) {
                // [BYPASS ADMIN] 
                // Admin Facility buat tiket -> Langsung masuk list kerja (Pending)
                // Tidak perlu approval atasan, tidak perlu verifikasi diri sendiri.
                $initialStatus = 'pending';
            } elseif ($isBoss) {
                // [BYPASS BOSS]
                // Boss buat tiket -> Skip SPV, tapi butuh Verifikasi Admin Facility
                $initialStatus = 'waiting_facility_approval';
            } else {
                // [STAFF BIASA]
                // Staff buat tiket -> Butuh Approval SPV/Manager
                $initialStatus = 'waiting_approval';
            }

            // A. Handle File Upload
            $photoPath = $file ? $file->store('wo_facilities', 'public') : null;

            // B. Generate Ticket Number
            $dateCode = date('Ymd');
            $prefix = 'FAC-' . $dateCode . '-';
            $lastTicket = WorkOrderFacilities::where('ticket_num', 'like', $prefix . '%')
                ->orderBy('id', 'desc')->lockForUpdate()->first();
            $newSeq = $lastTicket ? ((int)substr($lastTicket->ticket_num, -3) + 1) : 1;
            $ticketNum = $prefix . sprintf('%03d', $newSeq);

            // C. Logika Mesin & Plant
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
            $plantName = Plant::where('id', $data['plant_id'])->value('name') ?? '-';

            // D. Simpan WO
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

                // GUNAKAN STATUS HASIL LOGIC DI ATAS
                'status' => $initialStatus
            ]);

            // E. NOTIFIKASI
            $message = '';

            if ($isAdmin) {
                // Admin buat tiket -> Tidak kirim notif approval ke siapa-siapa.
                $message = 'Tiket dibuat oleh Admin (Auto-Approved). Status: Pending.';
                \Log::info("✅ FACILITY BYPASS: Admin {$user->name} created ticket {$ticket->ticket_num}");
            } elseif ($isBoss) {
                // Boss buat tiket -> Kirim Notif ke Admin Facility (minta verifikasi)
                $this->notifyAdmins($ticket, 'fh_new');
                $message = 'Tiket berhasil dibuat (Auto-Approve). Menunggu Verifikasi Facility.';
            } else {
                // Staff buat tiket -> Kirim Notif ke Atasan (minta approve)
                $this->notifyApprovers($ticket, $plantName);
                $message = 'Tiket berhasil dibuat. Menunggu persetujuan Atasan.';
            }

            // Info ke Diri Sendiri
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
        $isAdmin = in_array($user->role, ['fh.admin', 'super.admin', 'super.fh.admin']) || $user->divisi === 'FACILITY';

        // ------------------------------------------------------------------
        // CEK 0: STATUS PENDING
        // ------------------------------------------------------------------
        if ($ticket->status === 'pending') {
            return [
                'success' => false,
                'message' => 'Tiket ini SUDAH DISETUJI (Status Pending). Silakan gunakan tombol UPDATE/SELESAIKAN untuk memproses pekerjaan.'
            ];
        }
        if ($ticket->requester_id == $user->id && !$isAdmin) {
            return [
                'success' => false,
                'message' => 'Anda tidak dapat menyetujui tiket yang Anda buat sendiri. Harap tunggu persetujuan atasan.'
            ];
        }
        if ($ticket->status == 'waiting_facility_approval') {
            if ($isAdmin) {
                $ticket->update(['status' => 'pending', 'updated_at' => now()]);
                $this->notifyRequester($ticket, 'status_update');
                \Log::info("✅ FACILITY VERIFIED: {$user->name} verified ticket {$ticket->ticket_num}");
                return ['success' => true, 'message' => 'Tiket Terverifikasi (Pending). Siap dikerjakan Teknisi.'];
            } else {
                return ['success' => false, 'message' => 'Hanya Admin Facility yang bisa memverifikasi di tahap ini.'];
            }
        }

        if ($ticket->status == 'waiting_approval') {
            if ($isAdmin) {
                $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);
                $this->notifyRequester($ticket, 'status_update');
                $this->notifyAdmins($ticket, 'fh_new');
                \Log::info("✅ APPROVE BYPASS: {$user->name} approved ticket {$ticket->ticket_num}");
                return ['success' => true, 'message' => 'Disetujui Admin (Bypass). Menunggu Verifikasi Akhir.'];
            }
            if ($this->checkApprovalMatrix($ticket->plant, $user)) {
                $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);
                $this->notifyRequester($ticket, 'status_update');
                $this->notifyAdmins($ticket, 'fh_new');
                \Log::info("✅ APPROVE MATRIX: {$user->name} approved {$ticket->ticket_num} (Plant: {$ticket->plant})");
                return ['success' => true, 'message' => 'Disetujui. Notifikasi telah dikirim ke Tim Facility.'];
            }
            \Log::warning("⛔ APPROVE FAIL: {$user->name} (Div: {$user->divisi}) tried to approve {$ticket->plant}");
            return [
                'success' => false,
                'message' => "Gagal. Divisi/Jabatan Anda tidak memiliki wewenang approval untuk area {$ticket->plant} (Khusus CCV/Autowire harus sesuai wewenang)."
            ];
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
        \Log::info("🔍 DEBUG UPDATE STATUS:", [
            'ticket_id' => $wo->id,
            'status_received' => $data['status'], // Lihat apa yg dikirim frontend
            'requester_id' => $wo->requester_id,
            'requester_name' => $requester ? $requester->name : 'USER TIDAK DITEMUKAN',
            'requester_hp' => $requester ? $requester->no_hp : 'N/A'
        ]);
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
                        "🎉 Pekerjaan selesai!\n\n";
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
                default:
                    \Log::warning("⚠️ STATUS TIDAK DIKENALI: '{$data['status']}' - Pesan WA tidak dibuat.");
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
            $startObj = $wo->created_at ? $wo->created_at : now();
            $start    = $startObj->format('Y-m-d H:i:s');

            // 2. [LOGIC] Tentukan End Date
            // Jika completed: pakai actual_completion
            // Jika belum: pakai target_completion, kalau target null pakai hari ini
            if ($wo->status == 'completed' && $wo->actual_completion_date) {
                $endObj = Carbon::parse($wo->actual_completion_date);
            } else {
                $endObj = $wo->target_completion_date
                    ? Carbon::parse($wo->target_completion_date)
                    : now(); // Default hari ini jika target kosong
            }

            // Validasi: End tidak boleh sebelum Start
            if ($endObj->lt($startObj)) {
                $endObj = $startObj->copy()->addHours(1); // Min durasi 1 jam
            }

            // 3. [LOGIC] Hitung Durasi (Dalam Hari)
            // Menggunakan float diffInHours / 24 agar lebih presisi daripada diffInDays
            $duration = max(1, $startObj->diffInDays($endObj) + 1);

            $ganttData[] = [
                // --- WAJIB UNTUK DHTMLX ---
                'id'         => $wo->id,
                'text'       => $wo->ticket_num . ' - ' . Str::limit($wo->description, 30),
                'start_date' => $start, // Format: 2024-02-02 14:30:00
                'duration'   => $duration,
                'progress'   => ($wo->status == 'completed') ? 1 : (($wo->status == 'in_progress') ? 0.5 : 0),
                'color'      => $this->getStatusColor($wo->status),
                'open'       => true, // Agar tree terbuka default

                // --- DATA TAMBAHAN (Untuk Tooltip JS) ---
                'plant'        => $wo->plant ?? '-',
                'status'       => strtoupper($wo->status),
                'technician'   => $wo->technicians->pluck('name')->join(', ') ?: 'Unassigned',
                'description'  => Str::limit($wo->description, 100)
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
            ->where('jabatan', 'NOT LIKE', '%HSE%')
            ->where(function ($q) {
                // A. Cari berdasarkan Role Admin
                $q->whereIn('role', ['fh.admin', 'fh.manager', 'super.admin'])

                    // B. ATAU Cari Orang Facility TAPI HANYA LEVEL MANAGER (Jangan Staff)
                    ->orWhere(function ($sub) {
                        $sub->where('divisi', 'Facility') // Atau 'FACILITY' sesuai database
                            ->where(function ($lvl) {
                                $lvl->where('job_level', 'LIKE', '%MANAGER%')
                                    ->orWhere('job_level', 'LIKE', '%SUPERVISOR%')
                                    ->orWhere('job_level', 'LIKE', '%MGR%');
                            });
                    });
            })
            ->get();

        $link = url('/facility/' . $ticket->id);

        foreach ($recipients as $admin) {
            // Jangan kirim notif admin ke diri sendiri (jika dia pembuat tiketnya)
            if ($admin->id == $ticket->requester_id) continue;

            // A. Kirim Email
            $this->safeMail($admin->email, new FacilityNotification($ticket, $type));

            // B. Kirim WhatsApp dengan LOGGING LENGKAP
            if ($admin->no_hp) {
                $msg = "";

                if ($type == 'fh_new') {
                    // Pesan untuk Admin Facility saat ada tiket yang SUDAH diapprove Manager/SPV
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
                        "🛠 *Deskripsi:*\n" .
                        "_{$ticket->description}_\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━";
                } elseif ($type == 'new_ticket') {
                    // Pesan Info Tiket Baru (Hanya Info)
                    $msg = "🔧 *WORK ORDER FACILITY*\n" .
                        "👋 Halo Admin,\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n" .
                        "🆕 *INFO TIKET BARU*\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "📋 *Detail Tiket*\n" .
                        "┣ Nomor: `{$ticket->ticket_num}`\n" .
                        "┣ Requester: {$ticket->requester_name}\n" .
                        "┗ Status: ⏳ Menunggu Approval Atasan\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━";
                }

                if ($msg) {
                    try {
                        GaWhatsappService::send($admin->no_hp, $msg);
                        \Log::info("✅ WA ADMIN SENT to: {$admin->name} | Type: $type");
                    } catch (\Exception $e) {
                        \Log::error("❌ WA ADMIN FAILED to: {$admin->name} | Error: " . $e->getMessage());
                    }
                }
            } else {
                // Log Warning dikurangi levelnya agar tidak memenuhi log jika memang banyak staff tanpa HP
                // Atau filter query di atas whereNotNull('no_hp') jika hanya ingin mengirim ke yg punya HP
                \Log::debug("⚠️ WA ADMIN SKIP: User '{$admin->name}' tidak memiliki No HP.");
            }
        }
    }

    private function notifyApprovers($ticket, $plantName)
    {
        $matrix = $this->getFacilityMatrix();
        $config = $matrix[$plantName] ?? null;

        $requester = User::find($ticket->requester_id);
        $reqLevel = strtoupper(trim($requester->job_level ?? ''));

        $targetLevel = 'SPV';
        if (str_contains($reqLevel, 'SUPERVISOR') || str_contains($reqLevel, 'SPV')) {
            $targetLevel = 'MGR';
        } elseif (str_contains($reqLevel, 'MANAGER')) {
            return;
        }

        $query = User::where('is_active', 1);

        if ($config) {
            // [FIX] Menggunakan Logic Matrix
            $query->where(function ($q) use ($config, $targetLevel) {
                // A. Cek Supervisor
                if ($targetLevel === 'SPV') {
                    $q->where(function ($sub) {
                        $sub->where('job_level', 'LIKE', '%SUPERVISOR%')->orWhere('job_level', 'LIKE', '%SPV%');
                    })->whereIn('divisi', $config['spv']);
                } else {
                    $q->where(function ($sub) {
                        $sub->where('job_level', 'LIKE', '%MANAGER%');
                    })->whereIn('divisi', $config['mgr']);
                }
            });
        } else {
            // [FALLBACK] Logic Keyword (Jika tidak ada di matrix)
            $aliases = $this->getPlantAliases($plantName);

            if (empty($aliases)) return;

            $query->where(function ($q) use ($targetLevel) {
                if ($targetLevel === 'SPV') {
                    $q->where('job_level', 'LIKE', '%SUPERVISOR%');
                } else {
                    $q->where('job_level', 'LIKE', '%MANAGER%');
                }
            })->where(function ($q) use ($aliases) {
                foreach ($aliases as $alias) {
                    $q->orWhere('divisi', 'LIKE', '%' . $alias . '%');
                }
            });
        }
        $approvers = $query->get();

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
        // Data User (Normalisasi)
        \Log::info("🕵️‍♂️ CEK MATRIX APPROVAL:");
        \Log::info("User: " . $user->name);
        \Log::info("Role: " . $user->role); // Pastikan ini 'user'
        \Log::info("Divisi User (Asli): '" . $user->divisi . "'"); // Pakai kutip biar kelihatan spasi
        \Log::info("Jabatan User (Asli): '" . $user->jabatan . "'");
        \Log::info("Target Tiket (Asli): '" . $ticketPlant . "'");
        $userDivisi  = strtoupper(trim($user->divisi ?? ''));
        $userLevel   = strtoupper(trim($user->job_level ?? ''));
        $userJabatan = strtoupper(trim($user->jabatan ?? ''));

        // Data Tiket (Normalisasi)
        $plantTarget = strtoupper(trim($ticketPlant));

        // ------------------------------------------------------------------
        //  LOGIC PENGECUALIAN / STRICT BLOCKING
        // ------------------------------------------------------------------

        if ($plantTarget === 'PLANT D' || $plantTarget === 'PLANT D (OLD)') {
            if (str_contains($userDivisi, 'CCV') || str_contains($userJabatan, 'CCV')) {
                \Log::warning("⛔ BLOCKED: User CCV ({$user->name}) mencoba approve tiket Plant D biasa.");
                return false;
            }
        }
        if ($plantTarget === 'PLANT A') {
            if (str_contains($userDivisi, 'AUTOWIRE') || str_contains($userJabatan, 'AUTOWIRE')) {
                \Log::warning("⛔ BLOCKED: User Autowire ({$user->name}) mencoba approve tiket Plant A biasa.");
                return false;
            }
        }
        $matrix = $this->getFacilityMatrix();
        $config = $matrix[$plantTarget] ?? null;

        // 1. STRICT MATRIX CHECK
        if ($config) {
            // A. Cek Supervisor
            if (str_contains($userLevel, 'SUPERVISOR') || str_contains($userLevel, 'SPV')) {
                foreach ($config['spv'] as $keyword) {
                    $key = strtoupper($keyword);
                    // Cek apakah Keyword ada di Divisi ATAU Jabatan user
                    if (str_contains($userDivisi, $key) || str_contains($userJabatan, $key)) {
                        return true;
                    }
                }
            }

            // B. Cek Manager
            if (str_contains($userLevel, 'MANAGER') || str_contains($userLevel, 'HEAD') || str_contains($userLevel, 'MGR')) {
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

        // 2. FALLBACK (Hanya jika tidak ada di Matrix)
        return str_contains($userDivisi, $plantTarget);
    }

    private function getFacilityMatrix()
    {
        return [
            'PLANT D - CCV' => [
                'spv' => ['CCV Line', 'SUPERVISOR CCV'],
                'mgr' => ['MV D', 'MEDIUM VOLTAGE']
            ],
            'PLANT D' => [
                'spv' => ['MV D', 'MEDIUM VOLTAGE', 'PLANT D'],
                'mgr' => ['MV D', 'MEIDUM VOLTAGE']
            ],
            'PLANT A - AUTOWIRE' => [
                'spv' => ['SUPERVISOR AUTOWIRE', 'AUTO WIRE'],
                'mgr' => ['LOW VOLTAGE', 'LV']
            ],
            'PLANT A' => [
                'spv' => ['LV A', 'LOW VOLTAGE', 'PLANT A'],
                'mgr' => ['LOW VOLTAGE', 'LV']
            ],
            'PLANT B' => ['spv' => ['MV B', 'PLANT B'], 'mgr' => ['MV', 'MEDIUM VOLTAGE']],
            'PLANT C' => ['spv' => ['LV C', 'PLANT C'], 'mgr' => ['LV']],
            'PLANT E' => ['spv' => ['FO', 'PLANT E'], 'mgr' => ['FO']]
        ];
    }
}
