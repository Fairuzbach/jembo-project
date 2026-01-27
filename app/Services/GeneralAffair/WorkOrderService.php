<?php

namespace App\Services\GeneralAffair;

use App\Models\User;
use App\Services\GeneralAffair\GaWhatsappService;
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
                $statusAwal = 'waiting_approval';
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

        // Logika Start Date
        if (!empty($data['start_date'])) {
            $updateData['actual_start_date'] = $data['start_date'];
        } else if ($data['status'] === 'in_progress' && is_null($ticket->actual_start_date)) {
            $updateData['actual_start_date'] = now();
        }

        // Logika Completed
        if ($data['status'] === 'completed') {
            if ($completionPhoto) {
                $updateData['photo_completion_path'] = $completionPhoto->store('wo_ga_completed', 'public');
            }
            $updateData['actual_completion_date'] = $data['actual_completion_date'];
            $updateData['completion_note'] = $data['completion_note'] ?? null;
            $updateData['cancellation_note'] = null;
        }

        // Logika Cancelled
        if ($data['status'] === 'cancelled') {
            $updateData['cancellation_note'] = $data['cancellation_note'] ?? null;
            $updateData['actual_completion_date'] = null;
            $updateData['completion_note'] = null;
            $updateData['photo_completion_path'] = null;
        }

        // Update Optional Fields
        if (!empty($data['department'])) $updateData['department'] = $data['department'];
        if (!empty($data['target_date'])) $updateData['target_completion_date'] = $data['target_date'];

        // EKSEKUSI UPDATE
        $ticket->update($updateData);

        // Kirim Email (Existing)
        $this->sendStatusChangeEmail($ticket, $data['status']);

        // Log History
        $this->logHistory($ticket->id, 'Status Update', 'Status diubah menjadi: ' . ucfirst($data['status']));

        // ==========================================================
        // [BARU] LOGIKA NOTIFIKASI WHATSAPP STATUS UPDATE
        // ==========================================================

        // 1. Ambil Data Requester
        $requester = \App\Models\User::where('nik', $ticket->requester_nik)->first();
        $requesterPhone = $requester ? ($requester->no_hp ?? $requester->phone) : null;

        // 2. Siapkan Link & Pesan
        $ticketLink = url('/wo-ga/' . $ticket->id); // Sesuaikan URL
        $waMessage = "";

        switch ($data['status']) {
            case 'in_progress':
                $waMessage = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "Halo *{$requester->name}* 👋\n\n" .
                    "🛠️ *STATUS UPDATE: ON PROGRESS*\n\n" .
                    "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                    "📊 Status: *Sedang Dikerjakan*\n\n" .
                    "⚙️ Tim teknisi sedang menangani pekerjaan Anda.\n" .
                    "Kami akan memberikan update progress selanjutnya.\n\n" .
                    "🔗 *Track Progress:*\n{$ticketLink}\n\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n" .
                    "_Terima kasih atas kesabaran Anda_ ⏳";
                break;

            case 'completed':
                $note = $data['completion_note'] ?? '-';
                $waMessage = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "Halo *{$requester->name}* 👋\n\n" .
                    "✅ *STATUS UPDATE: COMPLETED*\n\n" .
                    "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                    "📊 Status: *Selesai Dikerjakan*\n\n" .
                    "🎉 Pekerjaan telah selesai!\n\n" .
                    "📝 *Catatan Penyelesaian:*\n" .
                    "_{$note}_\n\n" .
                    "🔗 *Detail Pekerjaan:*\n{$ticketLink}\n\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n" .
                    "_Mohon cek hasil pekerjaan di link di atas_ 🔍\n" .
                    "_Terima kasih atas kerjasamanya!_ 🙏";
                break;

            case 'cancelled':
                $reason = $data['cancellation_note'] ?? '-';
                $waMessage = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "Halo *{$requester->name}* 👋\n\n" .
                    "🚫 *STATUS UPDATE: CANCELLED*\n\n" .
                    "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                    "📊 Status: *Dibatalkan*\n\n" .
                    "📝 *Alasan Pembatalan:*\n" .
                    "_{$reason}_\n\n" .
                    "🔗 *Detail Tiket:*\n{$ticketLink}\n\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n" .
                    "_Untuk informasi lebih lanjut, silakan hubungi tim terkait_ 💬\n" .
                    "_Mohon maaf atas ketidaknyamanannya_ 🙏";
                break;

            case 'pending':
                $waMessage = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "Halo *{$requester->name}* 👋\n\n" .
                    "⏳ *STATUS UPDATE: PENDING*\n\n" .
                    "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                    "📊 Status: *Dalam Antrian*\n\n" .
                    "📌 Tiket Anda telah masuk dalam antrian pengerjaan.\n" .
                    "Tim teknisi akan segera menangani sesuai prioritas.\n\n" .
                    "🔗 *Monitor Status:*\n{$ticketLink}\n\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n" .
                    "_Anda akan menerima notifikasi saat pekerjaan dimulai_ 🔔";
                break;
        }

        // 3. Eksekusi Kirim WA
        if (!empty($waMessage) && !empty($requesterPhone)) {
            try {
                GaWhatsappService::send($requesterPhone, $waMessage);
                \Log::info("WA Status Update ({$data['status']}) terkirim ke {$requester->name}");
            } catch (\Exception $e) {
                \Log::error("Gagal kirim WA Status Update: " . $e->getMessage());
            }
        }
    }

    public function processTicket($id, string $action, ?string $reason): array
    {
        $ticket = WorkOrderGeneralAffair::findOrFail($id);
        $user = Auth::user();

        // Ambil Data Requester untuk Notif (WA)
        $requester = \App\Models\User::where('nik', $ticket->requester_nik)->first();
        $requesterPhone = $requester ? ($requester->no_hp ?? $requester->phone) : null;

        $ticketLink = url('/wo-ga');

        // 1. BERSIHKAN ROLE
        $cleanRole = strtolower(trim($user->role));

        $alertData = null;
        $updateData = [];
        $emailType = null; // Penanda jenis notif

        $isGaAdmin = in_array($cleanRole, ['ga.admin', 'super.ga.admin']);

        // --- SKENARIO 1: GA ADMIN BYPASS ---
        if ($ticket->status === 'waiting_approval' && $action === 'approve' && $isGaAdmin) {
            $ticket->status       = 'waiting_approval_ga';
            $ticket->approved_ga_at = now();
            $ticket->save();

            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id'       => $user->id,
                'action'        => 'bypass_approve',
                'description'   => 'GA Admin melakukan bypass approval manager.',
            ]);

            // [WA Bypass - Requester Only]
            if ($requesterPhone) {
                $msg = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "Halo *{$requester->name}* 👋\n\n" .
                    "⚡ *FAST TRACK APPROVAL*\n\n" .
                    "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                    "📊 Status: *Menunggu Verifikasi Akhir GA*\n\n" .
                    "✅ Tiket Anda telah di-approve oleh GA Admin.\n" .
                    "Proses verifikasi akhir sedang berlangsung.\n\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n" .
                    "_Mohon menunggu proses selanjutnya_ ⏳";

                GaWhatsappService::send($requesterPhone, $msg);
            }

            return [
                'status' => 'success',
                'message' => '✅ Tiket berhasil di-bypass (Approve) oleh Admin.',
                'alert' => '⚠️ Segera tentukan PIC untuk tiket ini.'
            ];
        }

        // --- SKENARIO 2: NORMAL FLOW ---
        if ($action == 'reject') {
            $newStatus = 'rejected';
            $desc = "Ditolak. Alasan: $reason";
            $emailType = 'rejected';
        } else {
            $adminRoles = ['ga.admin', 'admin_ga', 'ga_admin', 'super.ga.admin'];
            $isGAAdmin = in_array($cleanRole, $adminRoles);

            if ($ticket->status === 'waiting_approval') {
                // TAHAP 1: Approval dari Supervisor/Manager
                if ($isGAAdmin) {
                    $newStatus = 'pending';
                    $desc = "Tiket diterima langsung oleh *General Affair*.";
                    $updateData['approved_ga_by'] = $user->id;
                    $updateData['approved_ga_at'] = now();
                    $emailType = 'ga_approved';
                } else {
                    // Normal Manager Approval
                    $newStatus = 'waiting_approval_ga';
                    $desc = "Disetujui oleh *Manager ({$user->divisi})*. Menunggu General Affair.";
                    $updateData['processed_by'] = $user->id;
                    $updateData['processed_by_name'] = $user->name;
                    $emailType = 'manager_approved'; // <--- Trigger notif ke GA Admin
                }
            } elseif ($ticket->status === 'waiting_approval_ga') {
                // TAHAP 2: Approval dari GA Admin
                if ($isGAAdmin) {
                    $newStatus = 'pending';
                    $desc = "Disetujui oleh *General Affair*. Masuk antrian pending.";
                    $updateData['approved_ga_by'] = $user->id;
                    $updateData['approved_ga_at'] = now();
                    $alertData = [
                        'type' => 'warning',
                        'message' => 'Tiket berhasil disetujui (Status: Pending).',
                        'instruction' => 'Tiket sekarang dapat dikerjakan oleh tim General Affair!'
                    ];
                    $emailType = 'ga_approved';
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Hanya GA Admin yang bisa approve di tahap ini!'
                    ];
                }
            } else {
                // Fallback Status
                if ($isGAAdmin) {
                    $newStatus = 'pending';
                    $desc = "Tiket diterima General Affair.";
                    $updateData['approved_ga_by'] = $user->id;
                    $updateData['approved_ga_at'] = now();
                    $emailType = 'ga_approved';
                } else {
                    $newStatus = 'waiting_approval_ga';
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

        // ==========================================================
        // 4. LOGIKA NOTIFIKASI WHATSAPP (MULTI-TARGET)
        // ==========================================================

        Log::info("DEBUG WA: Memulai proses notifikasi untuk Tiket #{$ticket->ticket_num}");
        Log::info("DEBUG WA: Status Action: {$action}, EmailType: {$emailType}");

        // A. SIAPKAN PESAN UNTUK REQUESTER
        $msgRequester = "";

        if ($emailType === 'manager_approved') {
            Log::info("DEBUG WA: Masuk kondisi 'manager_approved'");

            $msgRequester = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "Halo *{$requester->name}* 👋\n\n" .
                "✅ *APPROVED BY MANAGER*\n\n" .
                "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                "📊 Status: *Menunggu Approval GA*\n\n" .
                "⏳ Tiket Anda telah disetujui oleh Manager Divisi.\n" .
                "Mohon menunggu verifikasi dari tim General Affair.\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n" .
                "_Terima kasih atas kesabaran Anda_ 🙏";
        } elseif ($emailType === 'ga_approved') {
            Log::info("DEBUG WA: Masuk kondisi 'ga_approved'");

            $msgRequester = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "Halo *{$requester->name}* 👋\n\n" .
                "✅ *APPROVED BY GA*\n\n" .
                "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                "📊 Status: *Pending (Siap Dikerjakan)*\n\n" .
                "🔧 Tiket Anda telah disetujui!\n" .
                "Tim teknisi akan segera menindaklanjuti pekerjaan Anda.\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n" .
                "_Mohon menunggu teknisi menghubungi Anda_ 📞";
        } elseif ($emailType === 'rejected') {
            Log::info("DEBUG WA: Masuk kondisi 'rejected'");

            $msgRequester = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "Halo *{$requester->name}* 👋\n\n" .
                "❌ *REJECTED*\n\n" .
                "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                "📊 Status: *Ditolak*\n\n" .
                "📝 *Alasan Penolakan:*\n" .
                "_{$reason}_\n\n" .
                "💬 Silakan hubungi tim terkait untuk informasi lebih lanjut.\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n" .
                "_Mohon maaf atas ketidaknyamanannya_ 🙏";
        } else {
            Log::warning("DEBUG WA: Tidak masuk kondisi manapun. EmailType: " . ($emailType ?? 'NULL'));
        }

        // B. EKSEKUSI KIRIM KE REQUESTER
        if (!empty($msgRequester) && !empty($requesterPhone)) {
            try {
                GaWhatsappService::send($requesterPhone, $msgRequester);
                Log::info("DEBUG WA: Sukses kirim ke Requester ({$requester->name})");
            } catch (\Exception $e) {
                Log::error("DEBUG WA: Gagal kirim ke Requester: " . $e->getMessage());
            }
        } else {
            Log::warning("DEBUG WA: Skip kirim Requester. Msg kosong atau No HP kosong.");
        }

        // C. LOGIKA KIRIM KE GA ADMIN (Next Approver)
        // Hanya jalan jika Manager baru saja Approve
        if ($emailType === 'manager_approved') {

            Log::info("DEBUG WA: Mencari GA Admin untuk notifikasi approval...");

            // Cek data GA Admin di Database
            $gaAdmins = \App\Models\User::whereIn('role', ['ga.admin', 'super.ga.admin'])
                ->whereNotNull('no_hp')
                ->where('no_hp', '!=', '')
                ->get();

            Log::info("DEBUG WA: Ditemukan " . $gaAdmins->count() . " GA Admin.");

            if ($gaAdmins->isEmpty()) {
                Log::error("DEBUG WA: GAGAL! Tidak ada GA Admin yang memiliki No HP/Role yang sesuai.");
                // Cek apakah ada admin meski tanpa no hp (untuk diagnosa)
                $adminsTanpaHp = \App\Models\User::whereIn('role', ['ga.admin', 'super.ga.admin'])->count();
                Log::info("DEBUG WA: Total GA Admin di DB (termasuk yg tanpa HP): " . $adminsTanpaHp);
            }

            foreach ($gaAdmins as $admin) {
                $msgAdmin = "*WORK ORDER GENERAL AFFAIR*\n" .
                    "Halo Admin *{$admin->name}*,\n\n" .
                    "🔔 *Task Baru Butuh Approval*\n" .
                    "Tiket: *#{$ticket->ticket_num}*\n" .
                    "Requester: {$requester->name}\n" .
                    "Divisi: {$ticket->department}\n" .
                    "Status: *Menunggu Approval GA*\n\n" .
                    "🔗 *Link Approve:* $ticketLink";

                try {
                    GaWhatsappService::send($admin->no_hp, $msgAdmin);
                    Log::info("DEBUG WA: Sukses kirim ke GA Admin: {$admin->name}");
                } catch (\Exception $e) {
                    Log::error("DEBUG WA: Gagal kirim ke GA Admin {$admin->name}: " . $e->getMessage());
                }
            }
        }
        // [END] WHATSAPP LOGIC

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
            'countWaitingApprovalSpv' => (clone $baseQuery)->where('status', 'waiting_approval')->count(),
            // Waiting Approval GA = Menunggu approval General Affairs Admin
            'countWaitingApprovalGA'  => (clone $baseQuery)->where('status', 'waiting_approval_ga')->count(),
            // Total semua approval yang menunggu (untuk compatibility dengan stats-card)
            'countWaitingApproval'    => (clone $baseQuery)->whereIn('status', ['waiting_approval', 'waiting_approval_ga'])->count(),
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
        if ($user->role === User::ROLE_GA_ADMIN || $user->role === 'ga.admin') {
            $query->where(function ($q) {
                // Admin GA melihat tiket dengan berbagai status
                $q->whereIn('status', [
                    'pending',
                    'approved',
                    'in_progress',
                    'completed',
                    'OPEN',
                    'waiting_approval_ga',
                    'waiting_approval',
                    'rejected'
                ]);

                // ATAU tiket yang TUJUANNYA ke departemen GA (meski status masih waiting)
                $q->orWhere(function ($sub) {
                    $sub->whereIn('status', ['waiting_approval', 'waiting_approval_ga'])
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
                            $sub->where('status', 'waiting_approval')
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
    /**
     * Mengatur Pengiriman Notifikasi (Email & WhatsApp)
     */
    private function sendNotifications($wo, $employee, $user, string $statusAwal, string $targetDept): void
    {
        // 1. Email ke Pelapor (Tetap Pertahankan)
        $pelaporEmail = $employee?->email ?? $user->email;
        if ($pelaporEmail) {
            $this->safeMail($pelaporEmail, new WorkOrderNotification($wo, 'created_info'));
        }

        // 2. Notifikasi ke Approver (Manager)
        if ($statusAwal === 'waiting_approval') {

            // Log untuk memastikan sistem mencari divisi yang benar
            Log::info("Mencari Manager untuk Dept: $targetDept");

            // --- CARI MANAGER ---
            // Langsung pakai $targetDept karena di DB sudah sama-sama "PE"
            // Pastikan parameter kedua 'MANAGER' (sesuai job_level di DB)
            $approvers = $this->getApproversForDeptLevel($targetDept, 'MANAGER');

            if ($approvers->isEmpty()) {
                Log::warning("WO GA: Tidak ada Manager ditemukan untuk dept: $targetDept");
            }

            // --- LOOPING KIRIM NOTIF ---
            // Buat Link Login/Approval untuk di WA
            $link = url('/wo-ga' . $wo->id);

            foreach ($approvers as $approver) {

                // A. Kirim Email (Existing)
                $this->safeMail($approver->email, new WorkOrderNotification($wo, 'need_approval'));

                // B. [BARU] Kirim WhatsApp ke Manager
                // Bagian inilah yang sebelumnya HILANG, makanya WA tidak masuk.
                if (!empty($approver->no_hp)) {
                    $msg = "*WORK ORDER GENERAL AFFAIR*\n" .
                        "Halo Manager *{$approver->name}*,\n\n" .
                        "🔔 *Permintaan Approval Baru*\n" .
                        "Nomor Tiket: *#{$wo->ticket_num}*\n" .
                        "Requester: {$user->name} ({$user->divisi})\n" .
                        "*Divisi*: {$wo->department}\n" .
                        "*Kategori*: {$wo->category}\n" .
                        "*Deskripsi*: {$wo->description}\n\n" .
                        "Mohon segera ditinjau melalui link berikut:\n" .
                        "$link";

                    try {
                        GaWhatsappService::send($approver->no_hp, $msg);
                        Log::info("WA sent to Manager {$approver->name}");
                    } catch (\Exception $e) {
                        Log::error("Gagal kirim WA ke Manager {$approver->name}: " . $e->getMessage());
                    }
                } else {
                    Log::warning("Manager {$approver->name} tidak punya Nomor HP, WA skip.");
                }
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
            ->whereIn('job', 'manager')
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
    /**
     * Mencari Approver berdasarkan Divisi dan Level Jabatan
     * FIX: Menggunakan kolom 'job_level' untuk Manager
     */
    private function getApproversForDeptLevel($departmentName, $targetRoles)
    {
        // 1. Normalisasi Input (Jadikan Array & Huruf Besar)
        $roles = is_array($targetRoles) ? $targetRoles : [$targetRoles];

        // Debugging (Opsional, bisa dihapus nanti)
        Log::info("Mencari User...", ['divisi' => $departmentName, 'target' => $roles]);

        return \App\Models\User::query()
            ->where('is_active', 1) // Hanya user aktif
            ->where(function ($query) use ($roles, $departmentName) {

                foreach ($roles as $role) {
                    // Normalize role string comparison
                    $roleUpper = strtoupper($role);

                    // A. LOGIKA CARI MANAGER
                    // Jika target yang dicari adalah 'MANAGER'
                    if ($roleUpper === 'MANAGER') {
                        $query->orWhere(function ($sub) use ($departmentName) {
                            $sub->where('divisi', $departmentName) // Wajib divisi sama
                                ->where('job_level', 'MANAGER');   // Kolom yang benar
                        });
                    }

                    // B. LOGIKA CARI ADMIN (Global Access)
                    // Misal: ga.admin, super.ga.admin
                    else {
                        $query->orWhere('role', $role);
                    }
                }
            })->get();
    }
}
