<?php

namespace App\Services\GeneralAffair;

use App\Models\User;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use App\Models\GeneralAffair\Category;
use App\Mail\WorkOrderNotification;
use App\Jobs\SendWorkOrderNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class WorkOrderService
{
    /**
     * Membuat Tiket Work Order Baru
     */
    public function createWorkOrder(array $data, ?UploadedFile $filePhoto = null): array
    {
        return DB::transaction(function () use ($data, $filePhoto) {
            $user = Auth::user();
            $employee = User::where('nik', $data['requester_nik'])->first();

            $isAdminGA = $user->divisi === 'General Affair' || $user->role === 'ga.admin';

            // Determine initial status
            if ($isAdminGA) {
                // GA Admin creates directly → status pending (ready to work)
                $statusAwal = 'pending';
            } else {
                // Regular user or other admin → needs SPV/Dept Head approval first
                $statusAwal = 'waiting_approval_spv';
            }

            $fixName = $employee?->name ?? $data['requester_name'] ?? $user->name;
            $fixDept = $employee?->divisi ?? $data['requester_department'] ?? $user->divisi;

            $photoPath = $filePhoto ? $filePhoto->store('wo_ga', 'public') : null;

            // Jika GA admin membuat untuk divisi lain, set approval tracking
            $approvalData = [];
            if ($isAdminGA && $statusAwal === 'pending') {
                $approvalData['approved_ga_by'] = $user->id;
                $approvalData['approved_ga_at'] = now();
            }

            $wo = WorkOrderGeneralAffair::create([
                'ticket_num'           => $this->generateTicketNum(),
                'requester_id'         => $user->id,
                'requester_nik'        => $data['requester_nik'],
                'requester_name'       => $fixName,
                'requester_department' => $fixDept,
                'plant'                => $data['plant_id'],
                'department'           => $data['department'],
                'category'             => $data['category'],
                'description'          => $data['description'],
                'parameter_permintaan' => $data['parameter_permintaan'] ?? '-',
                'status_permintaan'    => 'OPEN',
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'status'               => $statusAwal,
                'photo_path'           => $photoPath,
                ...$approvalData // Merge approval data jika ada
            ]);


            $this->sendNotifications($wo, $employee, $user, $statusAwal, $data['department']);

            return [
                'ticket' => $wo,
                'message' => $isAdminGA
                    ? 'Permintaan Berhasil Dibuat (Status: Pending). Anda dapat langsung mengedit dan mengerjakan tiket ini.'
                    : 'Permintaan Berhasil Dibuat! Silahkan hubungi Manager Dept Anda untuk Approve tiket ini!'
            ];
        });
    }
    public function updateStatus($id, array $data, ?UploadedFile $completionPhoto = null): void
    {
        $ticket = WorkOrderGeneralAffair::findOrFail($id);

        $updateData = [
            'status' => $data['status'],
            'processed_by_name' => $data['processed_by_name'],
            'category' => $data['category']
        ];

        if (!empty($data['start_date'])) {
            $updateData['actual_start_date'] = $data['start_date'];
        } else if ($data['status'] === 'in_progress' && is_null($ticket->actual_start_date)) {
            $updateData['actual_start_date'] = now();
        }

        if ($data['status'] === 'completed') {
            if ($completionPhoto) {
                $updateData['photo_completion_path'] = $completionPhoto->store('wo_ga_completed', 'public');
            }
            $updateData['actual_completion_date'] = $data['actual_completion_date'];
            $updateData['completion_note'] = $data['completion_note'] ?? null;
            $updateData['cancellation_note'] = null;
        }

        if ($data['status'] === 'cancelled') {
            $updateData['cancellation_note'] = $data['cancellation_note'] ?? null;
            $updateData['actual_completion_date'] = null;
            $updateData['completion_note'] = null;
            $updateData['photo_completion_path'] = null;
        }

        if (!empty($data['department'])) $updateData['department'] = $data['department'];
        if (!empty($data['target_date'])) $updateData['target_completion_date'] = $data['target_date'];

        $ticket->update($updateData);

        $this->sendStatusChangeEmail($ticket, $data['status']);
        $this->logHistory($ticket->id, 'Status Update', 'Status diubah menjadi: ' . ucfirst($data['status']));
    }

    public function processTicket($id, string $action, ?string $reason): array
    {
        $ticket = WorkOrderGeneralAffair::findOrFail($id);
        $user = \Illuminate\Support\Facades\Auth::user();

        // 1. BERSIHKAN ROLE
        $cleanRole = strtolower(trim($user->role));

        $alertData = null;
        $updateData = [];
        $emailType = null; // Penanda jenis email yang akan dikirim

        if ($action == 'reject') {
            $newStatus = 'rejected';
            $desc = "Ditolak. Alasan: $reason";
            $emailType = 'rejected'; // Tandai kirim email reject
        } else {
            $adminRoles = ['ga.admin', 'admin_ga', 'ga_admin'];
            $isGAAdmin = in_array($cleanRole, $adminRoles);

            if ($ticket->status === 'waiting_approval_spv') {
                // TAHAP 1: Approval dari Supervisor/Manager
                if ($isGAAdmin) {
                    // GA Bypass
                    $newStatus = 'pending';
                    $desc = "Tiket diterima langsung oleh General Affair.";
                    $updateData['approved_ga_by'] = $user->id;
                    $updateData['approved_ga_at'] = now();
                    $emailType = 'ga_approved'; // Langsung approved GA
                } else {
                    // Normal Manager Approval
                    $newStatus = 'waiting_approval_ga';
                    $desc = "Disetujui oleh Manager ({$user->divisi}). Menunggu GA.";
                    $updateData['processed_by'] = $user->id;
                    $updateData['processed_by_name'] = $user->name;
                    $emailType = 'manager_approved'; // Approved Manager -> Notif ke GA
                }
            } elseif ($ticket->status === 'waiting_approval_ga') {
                // TAHAP 2: Approval dari GA Admin
                if ($isGAAdmin) {
                    $newStatus = 'pending';
                    $desc = "Disetujui oleh General Affair. Masuk antrian pending.";
                    $updateData['approved_ga_by'] = $user->id;
                    $updateData['approved_ga_at'] = now();
                    $alertData = [
                        'type' => 'warning',
                        'message' => 'Tiket berhasil disetujui (Status: Pending).',
                        'instruction' => 'Tiket sekarang dapat dikerjakan oleh tim General Affair!'
                    ];
                    $emailType = 'ga_approved'; // Approved GA -> Notif ke User
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Hanya GA Admin yang bisa approve di tahap ini!'
                    ];
                }
            } else {
                // Status Lain (Fallback)
                if ($isGAAdmin) {
                    $newStatus = 'pending';
                    $desc = "Tiket diterima GA.";
                    $updateData['approved_ga_by'] = $user->id;
                    $updateData['approved_ga_at'] = now();
                    $emailType = 'ga_approved';
                } else {
                    $newStatus = 'waiting_ga_approval'; // Sesuaikan dengan ENUM database Anda (pakai 'waiting_approval_ga' jika itu yg benar)
                    $desc = "Disetujui oleh Admin Divisi.";
                    $emailType = 'manager_approved';
                }
            }
        }

        // 3. UPDATE DATABASE
        $updateData = array_merge($updateData, [
            'status' => $newStatus,
            'rejection_reason' => ($action === 'reject') ? $reason : null,
            'processed_by' => $updateData['processed_by'] ?? $user->id,
            'processed_by_name' => $updateData['processed_by_name'] ?? $user->name,
            'updated_at' => now()
        ]);

        $ticket->update($updateData);
        $this->logHistory($ticket->id, ucfirst($newStatus), $desc);

        // dd([
        //     'Check 1: Status Config Queue' => config('queue.default'), // Harus 'database'
        //     'Check 2: Email Type' => $emailType, // Tidak boleh NULL
        //     'Check 3: Status Tiket Sekarang' => $ticket->status,
        //     'Check 4: Action' => $action
        // ]);
        // --- 4. KIRIM EMAIL NOTIFIKASI ---
        // Ambil Email Requester
        $requester = \App\Models\User::where('nik', $ticket->requester_nik)->first();
        $requesterEmail = $requester ? $requester->email : null;

        // List Email Admin GA
        $gaAdminEmails = $this->getApproversForDeptLevel('General Affair', 'ga.admin');

        if ($gaAdminEmails->isEmpty()) {
            $gaAdminEmails = \App\Models\User::where('role', 'ga.admin')->pluck('email');
        }
        $gaAdminEmails = $gaAdminEmails->toArray();

        // LOGIKA PENGIRIMAN
        if ($emailType === 'manager_approved') {

            if (count($gaAdminEmails) > 0) {
                // KITA PAKSA KIRIM DISINI
                \Illuminate\Support\Facades\Mail::to($gaAdminEmails)
                    ->send(new \App\Mail\WorkOrderNotification($ticket, 'ga_new'));
            }

            if ($requesterEmail) {
                \Illuminate\Support\Facades\Mail::to($requesterEmail)
                    ->send(new \App\Mail\WorkOrderNotification($ticket, 'status_update'));
            }
        } elseif ($emailType === 'ga_approved') {
            if ($requesterEmail) {
                \Illuminate\Support\Facades\Mail::to($requesterEmail)
                    ->send(new \App\Mail\WorkOrderNotification($ticket, 'status_update'));
            }
        } elseif ($emailType === 'rejected') {
            if ($requesterEmail) {
                \Illuminate\Support\Facades\Mail::to($requesterEmail)
                    ->send(new \App\Mail\WorkOrderNotification($ticket, 'rejected'));
            }
        }

        return [
            'status' => 'success',
            'message' => ($action === 'approve' ? 'Tiket Disetujui' : 'Tiket Ditolak'),
            'alert' => $alertData
        ];
    }

    public function getWorkOrders($request, $user)
    {
        $query = WorkOrderGeneralAffair::query();

        $this->applyAccessControl($query, $user);
        $this->applyFilters($query, $request);

        $data = $query->with(['user', 'histories.user', 'plantInfo'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $data->getCollection()->transform(function ($ticket) {
            $ticket->approver_divisi = null;
            if ($ticket->processed_by_name) {
                $approver = User::where('name', $ticket->processed_by_name)->first();
                $ticket->approver_divisi = $approver ? $approver->divisi : null;
            }
            return $ticket;
        });
        return $data;
    }

    public function getIndexStats($user)
    {
        $query = WorkOrderGeneralAffair::query();
        $this->applyAccessControl($query, $user);

        // Cloning query dasar agar filter user/role tetap berlaku
        $baseQuery = clone $query;

        // 1. Hitung Delayed (Logika: Hanya tiket yang OVERDUE DAN status IN_PROGRESS)
        $countDelayed = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->whereNotNull('target_completion_date')
            ->where('target_completion_date', '<', now())
            ->count();

        return [
            'countTotal'              => (clone $baseQuery)->count(),
            // Pending = Tiket yang sudah di-approve GA tapi belum dikerjakan
            'countPending'            => (clone $baseQuery)->where('status', 'pending')->count(),
            // Waiting Approval SPV = Menunggu approval Supervisor/Dept Head
            'countWaitingApprovalSpv' => (clone $baseQuery)->where('status', 'waiting_approval_spv')->count(),
            // Waiting Approval GA = Menunggu approval General Affairs Admin
            'countWaitingApprovalGA'  => (clone $baseQuery)->where('status', 'waiting_approval_ga')->count(),
            // Total semua approval yang menunggu (untuk compatibility dengan stats-card)
            'countWaitingApproval'    => (clone $baseQuery)->whereIn('status', ['waiting_approval_spv', 'waiting_approval_ga'])->count(),
            'countInProgress'         => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'countCompleted'          => (clone $baseQuery)->where('status', 'completed')->count(),
            'countRejected'           => (clone $baseQuery)->where('status', 'rejected')->count(),
            'countDelayed'            => $countDelayed,
        ];
    }


    public function applyAccessControl(Builder $query, $user)
    {
        if (!$user) return;

        // 1. LOGIKA ADMIN GA (Melihat semua tiket untuk GA)
        if ($user->role === User::ROLE_GA_ADMIN || $user->role === 'admin_ga') {
            $query->where(function ($q) {
                // Admin GA melihat tiket dengan berbagai status
                $q->whereIn('status', [
                    'pending',
                    'approved',
                    'in_progress',
                    'completed',
                    'OPEN',
                    'waiting_approval_ga',
                    'waiting_approval_spv',
                    'rejected'
                ]);

                // ATAU tiket yang TUJUANNYA ke departemen GA (meski status masih waiting)
                $q->orWhere(function ($sub) {
                    $sub->whereIn('status', ['waiting_approval_spv', 'waiting_approval_ga'])
                        ->whereIn('department', ['GA', 'General Affair']);
                });
            });
        } else {
            // 2. LOGIKA ADMIN DIVISI LAIN (MT, ENG, LV, dll) dan SPV/Manager
            $roleMap = $this->getRoleMapping();

            if (array_key_exists($user->role, $roleMap)) {
                // Ambil daftar divisi yang DIKELOLA oleh admin ini
                $managedDepts = $roleMap[$user->role];

                $query->where(function ($q) use ($user, $managedDepts) {
                    // A. ADMIN MELIHAT TIKET YANG "DITUJUKAN" KE DIVISINYA
                    $q->whereIn('department', $managedDepts)
                        // B. ATAU TIKET YANG DIA BUAT SENDIRI (Sebagai Requester)
                        ->orWhere('requester_id', $user->id);
                });
            } else {
                // 3. LOGIKA USER BIASA & SPV/MANAGER (Hanya tiket milik divisi mereka)
                // Bisa lihat tiket yang mereka buat dan tiket menunggu approval dari divisi mereka
                $query->where(function ($q) use ($user) {
                    $q->where('requester_department', $user->divisi)
                        ->orWhere(function ($sub) use ($user) {
                            // Jika status waiting_approval_spv dan department sama, SPV bisa lihat
                            $sub->where('status', 'waiting_approval_spv')
                                ->where('department', $user->divisi);
                        });
                });
            }
        }
    }
    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================
    private function applyFilters(Builder $query, $request)
    {

        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('ticket_num', 'LIKE', "%{$request->search}%")
                    ->orWhere('requester_name', 'LIKE', "%{$request->search}%")
                    ->orWhere('description', 'LIKE', "%{$request->search}%")
                    ->orWhere('category', 'like', "%{$request->search}%")
                    ->orWhere('processed_by_name', 'like', "%{$request->search}%");
            });
        });


        $query->when($request->filled('status') && $request->status !== 'all', fn($q) => $q->where('status', $request->status));
        $query->when($request->filled('category') && $request->category !== 'all', fn($q) => $q->where('category', $request->category));
        $query->when($request->filled('parameter') && $request->parameter !== 'all', fn($q) => $q->where('parameter_permintaan', $request->parameter));
        $query->when($request->filled('plant_id') && $request->plant_id !== 'all', fn($q) => $q->where('plant', $request->plant_id));


        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->end_date);
    }
    /**
     * Generate Nomor Tiket Otomatis (GA-YYYYMMDD-XXXX)
     
     */
    private function generateTicketNum(): string
    {
        $prefix = 'GA-' . date('Ymd');
        $lastTicket = WorkOrderGeneralAffair::where('ticket_num', 'like', $prefix . '%')
            ->latest('id')->first();

        $number = $lastTicket ? sprintf('%04d', intval(substr($lastTicket->ticket_num, -4)) + 1) : '0001';
        return $prefix . '-' . $number;
    }

    private function logHistory($woId, $action, $desc)
    {
        WorkOrderGaHistory::create([
            'work_order_id' => $woId,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $desc
        ]);
    }

    private function sendStatusChangeEmail($ticket, $status)
    {
        $pelapor = User::find($ticket->requester_id);
        if (!$pelapor || empty($pelapor->email)) return;

        $type = match ($status) {
            'completed' => 'completed',
            'cancelled', 'rejected' => 'rejected',
            'approved', 'in_progress' => 'approved',
            default => null
        };

        if ($type) {
            $this->safeMail($pelapor->email, new WorkOrderNotification($ticket, $type));
        }
    }


    /**
     * Mengatur Pengiriman Notifikasi
     */
    private function sendNotifications($wo, $employee, $user, string $statusAwal, string $targetDept): void
    {
        // 1. Email ke Pelapor
        $pelaporEmail = $employee?->email ?? $user->email;
        if ($pelaporEmail) {
            $this->safeMail($pelaporEmail, new WorkOrderNotification($wo, 'created_info'));
        }

        // 2. Email ke Approver (Jika butuh approval)
        if ($statusAwal === 'waiting_approval_spv') {
            // Cari Supervisor/Manager divisi target untuk approval tahap 1
            $approvers = $this->getApproversForDeptLevel($targetDept, 'manager');

            if ($approvers->isEmpty()) {
                Log::warning("WO GA: Tidak ada Manager ditemukan untuk dept: $targetDept");
            }

            foreach ($approvers as $approver) {
                $this->safeMail($approver->email, new WorkOrderNotification($wo, 'need_approval'));
            }
        }
    }

    /**
     * Mencari User Approver berdasarkan Departemen Tujuan
     * Logic dari 
     */
    private function getApproversForDept(string $targetDept): Collection
    {
        $roleMap = $this->getRoleMapping();
        $targetRole = null;

        // 1. Cari Role berdasarkan Mapping
        foreach ($roleMap as $role => $departments) {
            if (in_array($targetDept, $departments)) {
                $targetRole = $role;
                break;
            }
        }

        // 2. Ambil User dengan Role tersebut
        if ($targetRole) {
            $approvers = User::where('role', $targetRole)->get();
            if ($approvers->isNotEmpty()) {
                return $approvers;
            }
        }

        // 3. Fallback: Cari Manager/SPV di Divisi tersebut jika Mapping tidak ketemu 
        return User::where('divisi', $targetDept)
            ->whereIn('role', 'manager')
            ->get();
    }

    /**
     * Dispatch email ke queue daripada send langsung
     * Ini memastikan email di-queue di database untuk reliability
     */
    private function safeMail(?string $to, $mailable): void
    {
        if (empty($to)) return;

        try {
            // Dispatch job ke queue 'emails'
            SendWorkOrderNotification::dispatch($to, $mailable)
                ->onConnection(config('queue.default'))
                ->onQueue('emails');

            Log::info("Email notification queued for: $to");
        } catch (\Exception $e) {
            Log::error('Queue Dispatch Error (WorkOrderService): ' . $e->getMessage());
            // Fallback: kirim langsung jika queue gagal
            try {
                Mail::to($to)->send($mailable);
                Log::info("Email sent directly (fallback): $to");
            } catch (\Exception $fallbackError) {
                Log::error('Mail Fallback Error: ' . $fallbackError->getMessage());
            }
        }
    }

    /**
     * Definisi Mapping Role ke Departemen

     */
    private function getRoleMapping(): array
    {
        return [
            'eng.admin'       => ['Engineering', 'engineering', 'ENGINEERING', 'PE'],
            'fh.admin'        => ['Facility', 'FH', 'FACILITY'],
            'mt.admin'        => ['Maintenance', 'maintenance', 'MT', 'MAINTENANCE', 'mt'],
            'lv.admin'        => ['Low Voltage', 'LOW VOLTAGE', 'low voltage', 'LV', 'lv'],
            'mv.admin'        => ['Medium Voltage', 'medium voltage', 'MV', 'mv'],
            'qr.admin'        => ['QR', 'qr'],
            'sc.admin'        => ['SC', 'sc'],
            'fo.admin'        => ['FO', 'fo'],
            'ss.admin'        => ['SS', 'ss'],
            'fa.admin'        => ['FA', 'fa'],
            'it.admin'        => ['IT', 'it'],
            'hc.admin'        => ['HC', 'hc'],
            'sales.admin'     => ['Sales', 'sales'],
            'marketing.admin' => ['Marketing', 'marketing'],
            'ga.admin'        => ['GA', 'General Affair']
        ];
    }
    private function getApproversForDeptLevel($departmentName, $targetRoles)
    {
        // Pastikan targetRoles selalu berbentuk Array agar mudah diproses
        $roles = is_array($targetRoles) ? $targetRoles : [$targetRoles];

        return \App\Models\User::where('is_active', 1)
            ->where(function ($q) use ($roles, $departmentName) {

                // 1. LOGIKA MANAGER: Wajib cek Kesamaan Divisi
                if (in_array('MANAGER', $roles)) {
                    $q->orWhere(function ($sub) use ($departmentName) {
                        $sub->where('role', 'MANAGER')
                            ->where('divisi', $departmentName);
                    });
                }

                // 2. LOGIKA ADMIN: Ambil semua role selain MANAGER
                // (Misal: eng.admin, admin, ga.admin, dll)
                // Admin dianggap punya hak akses lintas divisi (Global)
                $adminRoles = array_diff($roles, ['MANAGER']);

                if (!empty($adminRoles)) {
                    $q->orWhereIn('role', $adminRoles);
                }
            })
            ->pluck('email'); // Return Collection (jangan toArray)
    }
}
