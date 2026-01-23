<?php

namespace App\Services\Facility;

use App\Models\Facilities\WorkOrderFacilities;
use App\Models\Engineering\Machine;
use App\Models\Engineering\Plant;
use App\Models\User;
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

            // A. Handle File Upload
            $photoPath = $file ? $file->store('wo_facilities', 'public') : null;

            // B. Generate Ticket Number (Atomic Lock)
            $dateCode = date('Ymd');
            $prefix = 'FAC-' . $dateCode . '-';

            $lastTicket = WorkOrderFacilities::where('ticket_num', 'like', $prefix . '%')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $newSeq = $lastTicket ? ((int)substr($lastTicket->ticket_num, -3) + 1) : 1;
            $ticketNum = $prefix . sprintf('%03d', $newSeq);

            // C. Logika Mesin
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
                'status' => 'waiting_approval'
            ]);

            // F. Notifikasi Terpusat
            // 1. Info ke SPV Plant (Approval)
            $this->notifyApprovers($ticket, $plantName);
            // 2. Info ke Admin FH (New Ticket)
            $this->notifyAdmins($ticket, 'new_ticket');
            // 3. Info ke User (Created)
            $this->safeMail($user->email, new FacilityNotification($ticket, 'created_info'));

            return [
                'success' => true,
                'message' => 'Tiket berhasil dibuat. Menunggu persetujuan Atasan.',
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
        $cleanRole   = strtolower(trim($user->role ?? ''));
        $userLevel   = strtolower(trim($user->job_level ?? ''));
        $userDivisi  = trim($user->divisi ?? '');
        $ticketPlant = trim($ticket->plant ?? '');

        // Bypass Logic
        $facilityRoles = ['super.admin', 'fh.admin', 'fh.manager', 'fh.spv'];
        $isFacilityAdmin = in_array($cleanRole, $facilityRoles) || $user->divisi === 'Facility';
        $bossLevels = ['supervisor', 'manager', 'director'];

        // 1. STATE: WAITING APPROVAL (Approval Level Plant)
        if ($ticket->status == 'waiting_approval') {

            if ($isFacilityAdmin) {
                // Admin Bypass
                $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);
                $this->notifyRequester($ticket, 'status_update');
                // Info ke Admin Facility bahwa ada tiket masuk fase pengerjaan
                $this->notifyAdmins($ticket, 'fh_new');
                return ['success' => true, 'message' => 'Disetujui Admin (Bypass).'];
            } else {
                // Logic Wewenang Hierarki
                if (!in_array($userLevel, $bossLevels) && !str_contains($cleanRole, 'admin')) {
                    return ['success' => false, 'message' => 'Anda tidak memiliki level jabatan untuk approval.'];
                }

                $allowedKeywords = $this->getPlantAliases($ticketPlant);
                $isAuthorized = false;

                foreach ($allowedKeywords as $keyword) {
                    if (stripos($userDivisi, $keyword) !== false) {
                        $isAuthorized = true;
                        break;
                    }
                }

                if ($isAuthorized) {
                    $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);
                    $this->notifyRequester($ticket, 'status_update');
                    $this->notifyAdmins($ticket, 'fh_new'); // Info ke Facility team
                    return ['success' => true, 'message' => 'Disetujui. Menunggu verifikasi Facility.'];
                } else {
                    return ['success' => false, 'message' => "Gagal. Divisi Anda ($userDivisi) tidak memiliki wewenang approval area ini."];
                }
            }
        }

        // 2. STATE: WAITING FACILITY (Approval Admin/Manager FH)
        elseif ($ticket->status == 'waiting_facility_approval') {
            if ($isFacilityAdmin) {
                $ticket->update(['status' => 'pending', 'updated_at' => now()]);
                $this->notifyRequester($ticket, 'status_update');
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

        $this->notifyRequester($ticket, 'status_update');
        return ['success' => true, 'message' => 'Ticket Rejected.'];
    }

    public function updateStatus($id, array $data)
    {
        $wo = WorkOrderFacilities::findOrFail($id);
        $wo->status = $data['status'];

        if (isset($data['facility_tech_ids'])) {
            $ids = $data['facility_tech_ids'];
            if (!is_array($ids)) $ids = explode(',', (string)$ids);
            $ids = array_filter($ids, fn($val) => is_numeric($val) && $val > 0);
            $wo->technicians()->sync($ids);
        }

        if (!empty($data['start_date'])) {
            $wo->start_date = $data['start_date'];
        }

        if ($data['status'] == 'completed') {
            $wo->actual_completion_date = $wo->actual_completion_date ?? now();
        } elseif ($data['status'] != 'completed') {
            $wo->actual_completion_date = null;
        }

        if (!$wo->processed_by) {
            $wo->processed_by = Auth::id();
            $wo->processed_by_name = Auth::user()->name;
        }

        $wo->save();
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

        $stats = [
            'workOrders'    => $workOrders,
            'countTotal'    => $workOrders->count(),
            'countPending'  => $workOrders->where('status', 'pending')->count(),
            'countProgress' => $workOrders->where('status', 'in_progress')->count(),
            'countDone'     => $workOrders->where('status', 'completed')->count(),

            // Charts
            'chartCatLabels'    => $workOrders->groupBy('category')->keys(),
            'chartCatValues'    => $workOrders->groupBy('category')->map->count()->values(),
            'chartStatusLabels' => $workOrders->groupBy('status')->keys(),
            'chartStatusValues' => $workOrders->groupBy('status')->map->count()->values(),
            'chartPlantLabels'  => $workOrders->groupBy('plant')->keys(),
            'chartPlantValues'  => $workOrders->groupBy('plant')->map->count()->values(),
        ];

        // Hitung Teknisi (In-Memory Processing)
        $techData = [];
        foreach ($workOrders as $wo) {
            foreach ($wo->technicians as $tech) {
                $techData[$tech->name] = ($techData[$tech->name] ?? 0) + 1;
            }
        }
        arsort($techData);
        $stats['chartTechLabels'] = collect($techData)->keys();
        $stats['chartTechValues'] = collect($techData)->values();

        // Gantt Data (Reuse logic via method to avoid duplication)
        // Kita panggil method getGanttChartData secara internal tapi pass data yg sudah di-load
        // agar tidak query ulang
        $stats['ganttData'] = $this->formatGanttData($workOrders)['data'];

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
        // Cari email Admin/Manager FH
        $recipients = User::whereIn('role', ['fh.admin', 'fh.manager', 'super.admin'])
            ->orWhere('divisi', 'Facility')
            ->pluck('email')
            ->unique(); // Mencegah duplikat

        foreach ($recipients as $email) {
            $this->safeMail($email, new FacilityNotification($ticket, $type));
        }
    }

    private function notifyApprovers($ticket, $plantName)
    {
        $targetAliases = $this->getPlantAliases($plantName);

        // Cari User dengan Jabatan Boss DAN Divisi sesuai mapping
        $approvers = User::whereIn('job_level', ['supervisor', 'manager', 'director'])
            ->where(function ($q) use ($targetAliases) {
                foreach ($targetAliases as $alias) {
                    $q->orWhere('divisi', 'LIKE', '%' . $alias . '%');
                }
            })
            ->get();

        foreach ($approvers as $approver) {
            $this->safeMail($approver->email, new FacilityNotification($ticket, 'need_approval'));
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
}
