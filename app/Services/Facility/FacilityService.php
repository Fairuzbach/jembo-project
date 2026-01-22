<?php

namespace App\Services\Facility;

use App\Models\Facilities\WorkOrderFacilities;
use App\Models\Engineering\Machine;
use App\Models\Engineering\Plant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\FacilityNotification;
use Carbon\Carbon;

class FacilityService
{
    private function getApprovalMatrix($plantName)
    {
        // Normalisasi nama plant
        $p = strtolower($plantName);

        if (str_contains($p, 'plant a')) {
            return ['spv' => 'spv.lv', 'manager' => 'manager.lv'];
        }
        if (str_contains($p, 'plant b')) {
            return ['spv' => 'spv.mv', 'manager' => 'manager.mv'];
        }
        if (str_contains($p, 'plant c')) {
            return ['spv' => 'spv.lv', 'manager' => 'manager.lv']; // Asumsi sama dgn A sesuai prompt
        }
        if (str_contains($p, 'plant d')) {
            return ['spv' => 'spv.mv', 'manager' => 'manager.mv']; // Asumsi sama dgn B
        }
        if (str_contains($p, 'plant e')) {
            return ['spv' => 'spv.fo', 'manager' => 'manager.fo'];
        }

        // Default Fallback
        return ['spv' => 'super.admin', 'manager' => 'super.admin'];
    }
    /**
     * LOGIC 1: CREATE TICKET
     */
    public function createTicket(array $data, $file = null)
    {
        return DB::transaction(function () use ($data, $file) {
            $user = auth()->user();
            // A. Handle File Upload
            $photoPath = $file ? $file->store('wo_facilities', 'public') : null;

            // B. Generate Ticket Number (Atomic Lock)
            $dateCode = date('Ymd');
            $prefix = 'FAC-' . $dateCode . '-';

            $lastTicket = WorkOrderFacilities::where('ticket_num', 'like', $prefix . '%')
                ->orderBy('id', 'desc')
                ->lockForUpdate() // Cegah nomor ganda saat request bersamaan
                ->first();

            $newSeq = $lastTicket ? ((int)substr($lastTicket->ticket_num, -3) + 1) : 1;
            $ticketNum = $prefix . sprintf('%03d', $newSeq);

            // C. Logika Mesin (Baru vs Lama)
            $machineId = null;
            $machineName = null;

            if ($data['category'] == 'Pemasangan Mesin' && !empty($data['new_machine_name'])) {
                $newMachine = Machine::create([
                    'plant_id' => $data['plant_id'],
                    'name' => $data['new_machine_name'],
                    'code' => 'NEW-' . strtoupper(\Illuminate\Support\Str::random(5)),
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

            // D. Ambil Nama Plant (Query Ringan)
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
                'status' => 'waiting_approval'
            ]);
            // Notif ke SPV Plant Terkait (Todo: Implementasi kirim email ke role SPV spesifik)
            $this->sendNotifications($ticket, $user, $ticket->status, $ticket->plant);
            // F. Kirim Email ke Admin FH
            $this->sendNotification($ticket, 'new_ticket');

            return [
                'success' => true,
                'message' => 'Tiket berhasil dibuat. Menunggu persetujuan Atasan.',
                'data' => $ticket
            ];
        });
    }
    // Pastikan use ini ada di paling atas


    public function approveTicket($id)
    {
        $ticket = WorkOrderFacilities::find($id);
        if (!$ticket) return ['success' => false, 'message' => 'Tiket tidak ditemukan.'];

        $user = auth()->user();

        // Normalisasi
        $cleanRole   = strtolower(trim($user->role ?? ''));
        $userLevel   = strtolower(trim($user->job_level ?? ''));
        $userDivisi  = trim($user->divisi ?? ''); // Case Sensitive untuk akurasi
        $ticketPlant = trim($ticket->plant ?? '');

        // --- BYPASS ADMIN FACILITY ---
        $facilityRoles = ['super.admin', 'fh.admin', 'fh.manager', 'fh.spv'];
        $isFacilityAdmin = in_array($cleanRole, $facilityRoles) || $user->divisi === 'Facility';

        // --- LEVEL BOSS YANG DIIZINKAN ---
        $bossLevels = ['supervisor', 'manager', 'director'];

        // 1. STATE: WAITING APPROVAL
        if ($ticket->status == 'waiting_approval') {

            if ($isFacilityAdmin) {
                // Admin Bypass
                $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);
                $this->sendEmailNotification($ticket, 'plant_approved');
                return ['success' => true, 'message' => 'Disetujui Admin (Bypass).'];
            } else {
                // NORMAL FLOW (Hierarki User)

                // A. Cek Level Jabatan (Harus SPV ke atas)
                if (!in_array($userLevel, $bossLevels) && !str_contains($cleanRole, 'admin')) {
                    return ['success' => false, 'message' => 'Anda tidak memiliki level jabatan untuk melakukan approval.'];
                }

                // B. Cek Wewenang Wilayah (SMART MAPPING)
                $allowedKeywords = $this->getPlantAliases($ticketPlant);
                // Contoh Hasil: ['CCV', 'Manager MV']

                $isAuthorized = false;

                foreach ($allowedKeywords as $keyword) {
                    // Cek apakah Divisi User mengandung keyword yang diizinkan
                    // Gunakan stripos (case-insensitive search)
                    if (stripos($userDivisi, $keyword) !== false) {
                        $isAuthorized = true;
                        break;
                    }
                }

                if ($isAuthorized) {
                    $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);
                    $this->sendEmailNotification($ticket, 'plant_approved');
                    return ['success' => true, 'message' => 'Disetujui. Menunggu verifikasi Facility.'];
                } else {
                    return [
                        'success' => false,
                        'message' => "Gagal. Divisi Anda ($userDivisi) tidak memiliki wewenang approval untuk area ini."
                    ];
                }
            }
        }

        // 2. STATE: WAITING FACILITY
        elseif ($ticket->status == 'waiting_facility_approval') {
            if ($isFacilityAdmin) {
                $ticket->update(['status' => 'pending', 'updated_at' => now()]);
                $this->sendEmailNotification($ticket, 'facility_approved');
                return ['success' => true, 'message' => 'Tiket Disetujui Sepenuhnya (Pending).'];
            } else {
                return ['success' => false, 'message' => 'Hanya Admin Facility yang bisa approve di tahap ini!'];
            }
        }

        return ['success' => false, 'message' => 'Status tiket tidak valid.'];
    }
    public function rejectTicket($id, $reason)
    {
        $ticket = WorkOrderFacilities::findOrFail($id);
        $ticket->update([
            'status' => 'rejected',
            'rejection_reason' => $reason . ' (Rejected by ' . Auth::user()->name . ')'
        ]);

        // Notif ke Requester bahwa ditolak
        $this->sendNotification($ticket, 'status_update');

        return ['success' => true, 'message' => 'Ticket Rejected.'];
    }
    /**
     * LOGIC 2: UPDATE STATUS
     */
    public function updateStatus($id, array $data)
    {
        $wo = WorkOrderFacilities::findOrFail($id);

        // A. Update Status
        $wo->status = $data['status'];

        // B. Update Teknisi (Sync Array)
        if (isset($data['facility_tech_ids'])) {
            $ids = $data['facility_tech_ids'];
            if (!is_array($ids)) $ids = explode(',', (string)$ids);

            // Filter hanya angka valid
            $ids = array_filter($ids, fn($val) => is_numeric($val) && $val > 0);

            $wo->technicians()->sync($ids);
        }

        // C. Update Tanggal
        if (!empty($data['start_date'])) {
            $wo->start_date = $data['start_date'];
        }

        if ($data['status'] == 'completed') {
            $wo->actual_completion_date = $wo->actual_completion_date ?? now();
        } elseif ($data['status'] != 'completed') {
            $wo->actual_completion_date = null;
        }

        // D. Catat Pemroses
        if (!$wo->processed_by) {
            $wo->processed_by = Auth::id();
            $wo->processed_by_name = Auth::user()->name;
        }

        $wo->save();

        // E. Kirim Email ke Requester
        $this->sendNotification($wo, 'status_update');

        return $wo;
    }

    /**
     * LOGIC 3: GET DASHBOARD STATS (ANTI N+1)
     */
    public function getDashboardStats($request)
    {
        $query = WorkOrderFacilities::where('status', '!=', 'cancelled');

        // Filter Bulan/Tanggal
        if ($request->filled('month')) {
            try {
                $start = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()->format('Y-m-d');
                $end = Carbon::createFromFormat('Y-m', $request->month)->endOfMonth()->format('Y-m-d');
                $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
            } catch (\Exception $e) {
            }
        }

        // [ANTI N+1] Eager Loading di sini KUNCI-nya!
        // Ambil technicians, machine, dan user SEKALIGUS.
        $workOrders = $query->with(['technicians', 'machine', 'user'])
            ->latest()
            ->get();

        // Siapkan Data Return
        $stats = [
            'workOrders' => $workOrders,
            'countTotal' => $workOrders->count(),
            'countPending' => $workOrders->where('status', 'pending')->count(),
            'countProgress' => $workOrders->where('status', 'in_progress')->count(),
            'countDone' => $workOrders->where('status', 'completed')->count(),

            // Chart Helpers
            'chartCatLabels' => $workOrders->groupBy('category')->keys(),
            'chartCatValues' => $workOrders->groupBy('category')->map->count()->values(),
            'chartStatusLabels' => $workOrders->groupBy('status')->keys(),
            'chartStatusValues' => $workOrders->groupBy('status')->map->count()->values(),
        ];

        // Hitung Teknisi (Aman karena technicians sudah di-load)
        $techData = [];
        foreach ($workOrders as $wo) {
            foreach ($wo->technicians as $tech) {
                $techData[$tech->name] = ($techData[$tech->name] ?? 0) + 1;
            }
        }
        arsort($techData);
        $stats['chartTechLabels'] = collect($techData)->keys();
        $stats['chartTechValues'] = collect($techData)->values();

        // Gantt Chart Data
        $ganttData = [];
        foreach ($workOrders as $wo) {
            $start = $wo->created_at ? $wo->created_at->format('Y-m-d') : date('Y-m-d');
            $end = ($wo->status == 'completed' && $wo->actual_completion_date)
                ? $wo->actual_completion_date
                : ($wo->target_completion_date ?? date('Y-m-d'));

            if ($end < $start) $end = $start;

            $ganttData[] = [
                'ticket' => $wo->ticket_num,
                'status' => $wo->status,
                'start' => $start,
                'end' => $end,
                'plant' => $wo->plant ?? '-',
                'machine_name' => $wo->machine_name ?? '-',
                'category' => $wo->category ?? '-'
            ];
        }
        $stats['ganttData'] = $ganttData;

        return $stats;
    }

    /**
     * PRIVATE: SEND EMAIL
     */
    private function sendNotification($ticket, $type)
    {
        try {
            $recipients = [];

            if ($type === 'new_ticket') {
                // Ke Admin Facility
                $recipients = User::where('role', 'fh.admin')->pluck('email')->toArray();
            } elseif ($type === 'status_update') {
                // Ke Requester
                $requester = User::find($ticket->requester_id);
                if ($requester && $requester->email) {
                    $recipients[] = $requester->email;
                }
            }

            if (!empty($recipients)) {
                Mail::to($recipients)->send(new FacilityNotification($ticket, $type));
            }
        } catch (\Exception $e) {
            // Log error tapi jangan hentikan aplikasi
            \Illuminate\Support\Facades\Log::error("Gagal kirim email Facility: " . $e->getMessage());
        }
    }
    private function safeMail(?string $to, $mailable): void
    {
        if (empty($to)) return;

        try {
            \Illuminate\Support\Facades\Mail::to($to)->send($mailable);
            \Illuminate\Support\Facades\Log::info("Facility Email sent to: $to");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mail Error (Facility): ' . $e->getMessage());
        }
    }

    /**
     * Logic Notifikasi Awal (Saat Tiket Created)
     * Adaptasi dari GA: sendNotifications
     */
    private function sendNotifications($ticket, $user, string $statusAwal, string $targetPlant): void
    {
        // A. Email ke Pelapor
        if ($user->email) {
            $this->safeMail($user->email, new \App\Mail\FacilityNotification($ticket, 'created_info'));
        }

        // B. Email ke Approver (Manager/SPV)
        if ($statusAwal === 'waiting_approval') {

            // Ambil Keyword Mapping (Contoh: Plant A Autowire -> ['Autowire', 'Manager LV'])
            $targetAliases = $this->getPlantAliases($targetPlant);

            // Cari Boss yang:
            // 1. Levelnya SPV/Manager/Director
            // 2. Divisinya mengandung salah satu Keyword
            $approvers = \App\Models\User::where(function ($q) {
                $q->whereIn('job_level', ['supervisor', 'manager', 'director'])
                    ->orWhere('role', 'LIKE', '%admin%');
            })
                ->where(function ($q) use ($targetAliases) {
                    foreach ($targetAliases as $alias) {
                        $q->orWhere('divisi', 'LIKE', '%' . $alias . '%');
                    }
                })
                ->get();

            // Kirim Email
            foreach ($approvers as $approver) {
                $this->safeMail($approver->email, new \App\Mail\FacilityNotification($ticket, 'need_approval'));
            }
        }
    }
    private function sendEmailNotification($ticket, $type)
    {
        // A. Email ke Requester (Status Update)
        $requester = \App\Models\User::find($ticket->requester_id);
        if ($requester && $requester->email) {
            $this->safeMail($requester->email, new \App\Mail\FacilityNotification($ticket, 'status_update'));
        }

        // B. Jika Tiket Disetujui Manager -> Info ke Facility Admin
        if ($type === 'plant_approved') {
            $fhAdminEmails = \App\Models\User::whereIn('role', ['fh.admin', 'super.admin'])
                ->orWhere('divisi', 'Facility')
                ->pluck('email');

            foreach ($fhAdminEmails as $email) {
                $this->safeMail($email, new \App\Mail\FacilityNotification($ticket, 'fh_new'));
            }
        }

        // C. Jika Tiket Selesai / Pending Facility
        if ($type === 'facility_approved') {
            // (Opsional) Bisa tambah logic lain disini
        }
    }
    /**
     * SMART MAPPING: Logika Hierarki Jembo Cable (LV/MV & CCV/Autowire)
     */
    /**
     * SMART MAPPING: Logika Hierarki Jembo Cable (LV/MV & CCV/Autowire)
     * Menerjemahkan Input Tiket -> Kata Kunci Jabatan Approver
     */
    private function getPlantAliases(string $plantName): array
    {
        $cleanName = strtoupper(trim($plantName));

        // KASUS SPESIALIS (Autowire & CCV)
        if (str_contains($cleanName, 'AUTOWIRE') || str_contains($cleanName, 'AUTO WIRE')) {
            return ['Autowire', 'Manager LV', 'Low Voltage'];
        }
        if (str_contains($cleanName, 'CCV')) {
            return ['CCV', 'Manager MV', 'Medium Voltage'];
        }

        // KASUS LOW VOLTAGE (Plant A & C)
        if (str_contains($cleanName, 'PLANT A')) return ['LV A', 'Manager LV', 'Plant A'];
        if (str_contains($cleanName, 'PLANT C')) return ['LV C', 'Manager LV', 'Plant C'];

        // KASUS MEDIUM VOLTAGE (Plant B & D)
        if (str_contains($cleanName, 'PLANT B')) return ['MV B', 'Manager MV', 'Plant B'];
        if (str_contains($cleanName, 'PLANT D')) return ['MV D', 'Manager MV', 'Plant D'];

        if (str_containts($cleanName, 'PLANT E')) return ['FO', 'Manager FO', 'Plant E', 'Fiber Optic Manager'];
        // Default
        return [$plantName];
    }
}
