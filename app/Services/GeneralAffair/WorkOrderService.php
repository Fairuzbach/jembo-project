<?php

namespace App\Services\GeneralAffair;

use App\Models\User;
use App\Services\GeneralAffair\GaWhatsappService;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use App\Mail\WorkOrderNotification;
use App\Jobs\SendWorkOrderNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
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

            $isAdminGA = $user->divisi === 'General Affair' || $user->role === 'ga.admin' || $user->role === 'super.ga.admin';

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
        // dd($id);
        $ticket = WorkOrderGeneralAffair::findOrFail($id);

        $updateData = [
            'status' => $data['status'],
            'processed_by_name' => $data['processed_by_name'],
            'category' => $data['category'],
        ];

        if (!empty($data['parameter_permintaan'])) {
            $updateData['parameter_permintaan'] = $data['parameter_permintaan'];
        }

        // 1. Logika Start Date
        if (!empty($data['start_date'])) {
            $updateData['actual_start_date'] = $data['start_date'];
        } else if ($data['status'] === 'in_progress' && is_null($ticket->actual_start_date)) {
            $updateData['actual_start_date'] = now();
        }

        // 2. Logika Completed
        if ($data['status'] === 'completed') {
            if ($completionPhoto) {

                if ($ticket->photo_completed_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($ticket->photo_completed_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($ticket->photo_completed_path);
                }
                $uniqueName = 'wo_' . $ticket->id . '_' . time() . '.' . $completionPhoto->getClientOriginalExtension();

                // 3. Simpan
                $updateData['photo_completed_path'] = $completionPhoto->storeAs('wo_ga_completed', $uniqueName, 'public');
            }

            $updateData['actual_completion_date'] = $data['actual_completion_date'];
            $updateData['completion_note'] = $data['completion_note'] ?? null;
            $updateData['cancellation_note'] = null;
        }

        // 3. Logika Cancelled
        if ($data['status'] === 'cancelled') {
            // [FIX] Hapus file fisik bukti penyelesaian jika ada (karena dibatalkan)
            if ($ticket->photo_completed_path && Storage::disk('public')->exists($ticket->photo_completed_path)) {
                Storage::disk('public')->delete($ticket->photo_completed_path);
            }

            $updateData['cancellation_note'] = $data['cancellation_note'] ?? null;
            $updateData['actual_completion_date'] = null;
            $updateData['completion_note'] = null;
            $updateData['photo_completed_path'] = null;
        }

        // 4. Update Optional Fields
        if (!empty($data['department'])) $updateData['department'] = $data['department'];
        if (!empty($data['target_date'])) {
            $updateData['target_completion_date'] = $data['target_date'];
        } elseif (!empty($data['target_completion_date'])) {
            $updateData['target_completion_date'] = $data['target_completion_date'];
        }

        // EKSEKUSI UPDATE
        $ticket->update($updateData);

        // Kirim Email
        $this->sendStatusChangeEmail($ticket, $data['status']);

        // Log History
        $this->logHistory($ticket->id, 'Status Update', 'Status diubah menjadi: ' . ucfirst($data['status']));

        // ==========================================================
        // NOTIFIKASI WHATSAPP
        // ==========================================================

        $requester = \App\Models\User::where('nik', $ticket->requester_nik)->first();

        // [FIX] Cek apakah requester ada untuk mencegah Error
        if ($requester) {
            $requesterPhone = $requester->no_hp ?? $requester->phone;
            $requesterName = $requester->name; // Simpan nama di variabel

            // [FIX] Link lebih spesifik (bisa tambahkan parameter search ID agar langsung ketemu)
            // Contoh: url/ga/index?search=WO-2024-001
            $ticketLink = route('ga.index', ['search' => $ticket->ticket_num]);

            $waMessage = "";

            switch ($data['status']) {
                case 'in_progress':
                    $waMessage = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                        "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "Halo *{$requesterName}* 👋\n\n" .
                        "🛠️ *STATUS UPDATE: ON PROGRESS*\n\n" .
                        "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                        "📊 Status: *Sedang Dikerjakan*\n\n" .
                        "⚙️ Tim teknisi sedang menangani pekerjaan Anda.\n" .
                        "Kami akan memberikan update progress selanjutnya.\n\n" .
                        "🔗 *Link Tiket:*\n$ticketLink\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━━\n" .
                        "_Terima kasih atas kesabaran Anda_ ⏳";
                    break;

                case 'completed':
                    $note = $data['completion_note'] ?? '-';
                    $waMessage = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                        "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "Halo *{$requesterName}* 👋\n\n" .
                        "✅ *STATUS UPDATE: COMPLETED*\n\n" .
                        "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                        "📊 Status: *Selesai Dikerjakan*\n\n" .
                        "🎉 Pekerjaan telah selesai!\n\n" .
                        "📝 *Catatan Penyelesaian:*\n" .
                        "_{$note}_\n\n" .
                        "🔗 *Link Tiket (Cek Hasil):*\n$ticketLink\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━━\n" .
                        "_Terima kasih atas kerjasamanya!_ 🙏";
                    break;

                case 'cancelled':
                    $reason = $data['cancellation_note'] ?? '-';
                    $waMessage = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                        "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "Halo *{$requesterName}* 👋\n\n" .
                        "🚫 *STATUS UPDATE: CANCELLED*\n\n" .
                        "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                        "📊 Status: *Dibatalkan*\n\n" .
                        "📝 *Alasan Pembatalan:*\n" .
                        "_{$reason}_\n\n" .
                        "🔗 *Link Tiket:*\n$ticketLink\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━━\n" .
                        "_Untuk informasi lebih lanjut, silakan hubungi tim terkait_ 💬";
                    break;

                case 'pending':
                    $waMessage = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                        "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "Halo *{$requesterName}* 👋\n\n" .
                        "⏳ *STATUS UPDATE: PENDING*\n\n" .
                        "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                        "📊 Status: *Dalam Antrian*\n\n" .
                        "📌 Tiket Anda telah masuk dalam antrian pengerjaan.\n" .
                        "Tim teknisi akan segera menangani sesuai prioritas.\n\n" .
                        "🔗 *Link Tiket:*\n$ticketLink\n\n" .
                        "━━━━━━━━━━━━━━━━━━━━━━\n" .
                        "_Anda akan menerima notifikasi saat pekerjaan dimulai_ 🔔";
                    break;
            }

            // Eksekusi Kirim WA
            if (!empty($waMessage) && !empty($requesterPhone)) {
                try {
                    GaWhatsappService::send($requesterPhone, $waMessage);
                    \Log::info("WA Status Update ({$data['status']}) terkirim ke {$requesterName}");
                } catch (\Exception $e) {
                    \Log::error("Gagal kirim WA Status Update: " . $e->getMessage());
                }
            }
        } else {
            \Log::warning("Requester dengan NIK {$ticket->requester_nik} tidak ditemukan, WA tidak terkirim.");
        }
    }


    public function processTicket($id, string $action, ?string $reason, array $data = []): array
    {
        // dd($data);
        $ticket = WorkOrderGeneralAffair::findOrFail($id);
        $user = Auth::user();
        \Log::info("Memproses Tiket #{$ticket->ticket_num}", [
            'action_received' => $action,
            'current_status' => $ticket->status,
            'user' => $user->name
        ]);
        // Ambil Data Requester untuk Notif (WA)
        $requester = \App\Models\User::where('nik', $ticket->requester_nik)->first();
        $requesterPhone = $requester ? ($requester->no_hp ?? $requester->phone) : null;

        // 1. BERSIHKAN ROLE
        $cleanRole = strtolower(trim($user->role));

        $alertData = null;
        $updateData = [];
        $emailType = null; // Penanda jenis notif

        $adminRoles = ['ga.admin', 'admin_ga', 'ga_admin', 'super.ga.admin'];
        $isGaAdmin = in_array($cleanRole, $adminRoles);

        // =========================================================================
        // [MODIFIKASI UTAMA] TANGKAP DATA FORM DI AWAL
        // =========================================================================
        if ($isGaAdmin && !empty($data) && $action === 'approve') {
            $updateData['category'] = $data['category'] ?? $ticket->category;
            $updateData['parameter_permintaan'] = $data['parameter_permintaan'] ?? $ticket->parameter_permintaan;
            $updateData['target_completion_date'] = $data['target_completion_date'] ?? $ticket->target_completion_date;

            // Set Approver & PIC ke GA Admin (Menimpa nama manager sebelumnya)
            $updateData['approved_ga_by'] = $user->id;
            $updateData['approved_ga_at'] = now();
            $updateData['processed_by'] = $user->id;
            $updateData['processed_by_name'] = $user->name;
        }

        // --- SKENARIO 1: GA ADMIN BYPASS ---
        if ($ticket->status === 'waiting_approval' && $action === 'approve' && $isGaAdmin) {
            $newStatus = 'pending';
            $desc = "Tiket diterima & diklasifikasikan langsung oleh GA Admin (Bypass Manager).";

            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id'       => $user->id,
                'action'        => 'bypass_approve',
                'description'   => 'GA Admin melakukan bypass approval manager.',
            ]);

            $alertData = [
                'type' => 'warning',
                'message' => 'Tiket di-bypass & disetujui (Status: Pending).',
                'instruction' => 'Segera kerjakan tiket ini.'
            ];

            $emailType = 'ga_approved';
        }
        // --- SKENARIO 2: NORMAL FLOW ---
        else {
            if ($action == 'reject') {
                $newStatus = 'rejected';
                $desc = "Ditolak. Alasan: $reason";
                $emailType = 'rejected';
            } else {
                if ($ticket->status === 'waiting_approval') {
                    // TAHAP 1: Approval dari Manager Divisi
                    $newStatus = 'waiting_approval_ga';
                    $desc = "Disetujui oleh Manager ({$user->divisi}). Menunggu General Affair.";

                    $updateData['processed_by'] = $user->id;
                    $updateData['processed_by_name'] = $user->name;

                    $emailType = 'manager_approved';
                } elseif ($ticket->status === 'waiting_approval_ga') {
                    // TAHAP 2: Approval dari GA Admin
                    if ($isGaAdmin) {
                        $newStatus = 'pending';
                        $desc = "Disetujui & Diklasifikasikan oleh GA. Masuk antrian pending.";

                        $alertData = [
                            'type' => 'warning',
                            'message' => 'Tiket berhasil disetujui (Status: Pending).',
                            'instruction' => 'Tiket sekarang dapat dikerjakan oleh tim General Affair!'
                        ];
                        $emailType = 'ga_approved';
                    } else {
                        return ['status' => 'error', 'message' => 'Hanya GA Admin yang bisa approve di tahap ini!'];
                    }
                } else {
                    // Fallback Status
                    if ($isGaAdmin) {
                        $newStatus = 'pending';
                        $desc = "Tiket diterima General Affair.";
                        $emailType = 'ga_approved';
                    } else {
                        $newStatus = 'waiting_approval_ga';
                        $desc = "Disetujui oleh Admin Divisi.";
                        $emailType = 'manager_approved';
                    }
                }
            }
        }

        // 3. UPDATE DATABASE FINAL
        $finalUpdate = array_merge($updateData, [
            'status' => $newStatus,
            'rejection_reason' => ($action === 'reject') ? $reason : null,
            'processed_by' => $updateData['processed_by'] ?? $user->id,
            'processed_by_name' => $updateData['processed_by_name'] ?? $user->name,
            'updated_at' => now()
        ]);

        $ticket->update($finalUpdate);
        $this->logHistory($ticket->id, ucfirst($newStatus), $desc);

        // ==========================================================
        // 4. LOGIKA NOTIFIKASI WHATSAPP (MULTI-TARGET)
        // ==========================================================

        Log::info("DEBUG WA: Memulai proses notifikasi untuk Tiket #{$ticket->ticket_num}");
        Log::info("DEBUG WA: Status Action: {$action}, EmailType: {$emailType}");

        // [TAMBAHAN LINK]
        $ticketLink = route('ga.index');

        // A. SIAPKAN PESAN UNTUK REQUESTER
        $msgRequester = "";

        if ($emailType === 'manager_approved') {
            Log::info("DEBUG WA: Masuk kondisi 'manager_approved'");

            $msgRequester = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "Halo *{$requester->name}* 👋\n\n" .
                "✅ *APPROVED BY MANAGER* ✅\n\n" .
                "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                "📊 Status: *Menunggu Approval GA*\n\n" .
                "⏳ Tiket Anda telah disetujui oleh Manager Divisi.\n" .
                "Mohon menunggu verifikasi dari tim General Affair.\n\n" .
                "🔗 *Link Tiket:*\n$ticketLink\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n" .
                "_Terima kasih atas kesabaran Anda_ 🙏";
        } elseif ($emailType === 'ga_approved') {
            Log::info("DEBUG WA: Masuk kondisi 'ga_approved'");

            $msgRequester = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "Halo *{$requester->name}* 👋\n\n" .
                "✅ *APPROVED BY GA* ✅\n\n" .
                "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                "📊 Status: *Pending (Siap Dikerjakan)*\n\n" .
                "🔧 Tiket Anda telah disetujui!\n" .
                "Tim teknisi akan segera menindaklanjuti pekerjaan Anda.\n\n" .
                "🔗 *Link Tiket:*\n$ticketLink\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n" .
                "_Mohon menunggu teknisi menghubungi Anda_ 📞";
        } elseif ($emailType === 'rejected') {
            Log::info("DEBUG WA: Masuk kondisi 'rejected'");

            $msgRequester = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "Halo *{$requester->name}* 👋\n\n" .
                "❌ *REJECTED* ❌\n\n" .
                "📋 Ticket: *#{$ticket->ticket_num}*\n" .
                "📊 Status: *Ditolak*\n\n" .
                "📝 *Alasan Penolakan:*\n" .
                "_{$reason}_\n\n" .
                "🔗 *Link Tiket:*\n$ticketLink\n\n" .
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
        if ($emailType === 'manager_approved') {
            Log::info("DEBUG WA: Mencari GA Admin untuk notifikasi approval...");

            $gaAdmins = \App\Models\User::whereIn('role', ['ga.admin', 'super.ga.admin'])
                ->whereNotNull('no_hp')
                ->where('no_hp', '!=', '')
                ->get();

            Log::info("DEBUG WA: Ditemukan " . $gaAdmins->count() . " GA Admin.");

            if ($gaAdmins->isEmpty()) {
                Log::error("DEBUG WA: GAGAL! Tidak ada GA Admin yang memiliki No HP/Role yang sesuai.");
            }

            foreach ($gaAdmins as $admin) {
                $msgAdmin = "🎫 *WORK ORDER GENERAL AFFAIR*\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "Halo Admin *{$admin->name}* 👋\n\n" .
                    "🔔 *NEW APPROVAL REQUEST*\n\n" .
                    "📋 *Detail Tiket:*\n" .
                    "• Ticket: *#{$ticket->ticket_num}*\n" .
                    "• Requester: *{$requester->name}*\n" .
                    "• Divisi: *{$ticket->department}*\n" .
                    "• Status: *Menunggu Approval GA*\n\n" .
                    "📝 *Deskripsi Pekerjaan:*\n" .
                    "_{$ticket->description}_\n\n" .
                    "🔗 *Link Approval:*\n$ticketLink\n\n" .
                    "━━━━━━━━━━━━━━━━━━━━━━\n" .
                    "_Mohon segera review dan approve tiket ini_ ✅";

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

        $data = $query->with(['user', 'histories.user', 'plantInfo', 'approverGa'])
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
        if ($user->role === User::ROLE_GA_ADMIN || $user->role === 'ga.admin' || $user->role === 'super.ga.admin') {
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
        // 1. Search Logic
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($sub) use ($search) {
                $sub->where('ticket_num', 'LIKE', "%{$search}%")
                    ->orWhere('requester_name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('category', 'LIKE', "%{$search}%")
                    ->orWhere('processed_by_name', 'LIKE', "%{$search}%");
            });
        });

        // 2. Filter Status & Category
        $query->when($request->filled('status'), fn($q) => $q->where('status', $request->status));
        $query->when($request->filled('category'), fn($q) => $q->where('category', $request->category));

        // 3. Filter Parameter (Jenis Permintaan)
        $query->when($request->filled('parameter'), fn($q) => $q->where('parameter_permintaan', $request->parameter));

        // 4. Filter Plant
        $query->when($request->filled('plant_id'), fn($q) => $q->where('plant', $request->plant_id));

        // 5. Filter Department
        $query->when($request->filled('department'), fn($q) => $q->where('department', $request->department));

        // 6. Date Range
        $query->when($request->filled('start_date'), fn($q) => $q->whereDate('created_at', '>=', $request->start_date));
        $query->when($request->filled('end_date'), fn($q) => $q->whereDate('created_at', '<=', $request->end_date));
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
            $targets = ['MANAGER', 'SUPERVISOR'];
            $approvers = $this->getApproversForDept($targetDept, $targets);

            if ($approvers->isEmpty()) {
                Log::warning("WO GA: Tidak ada Manager ditemukan untuk dept: $targetDept");
            }

            // --- LOOPING KIRIM NOTIF ---
            // Buat Link Login/Approval untuk di WA
            // $link = url('/wo-ga' . $wo->id);

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
                        "Mohon segera ditinjau.";

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
     * Mencari Approver (Manager/Supervisor) berdasarkan Keyword Departemen
     * Logika: Mencari kata kunci departemen di dalam kolom JABATAN user.
     */
    private function getApproversForDept(string $targetDept): \Illuminate\Support\Collection
    {
        // 1. Bersihkan Keyword (Hapus spasi berlebih & Ubah ke Huruf Besar)
        // Contoh: "Low Voltage " -> "LOW VOLTAGE"
        $keyword = strtoupper(trim($targetDept));

        // 2. [FIX OPTIK vs OPTIC] Sesuaikan ejaan input dengan database
        // Data User ID 21 pakai "OPTIC", Input Tiket mungkin "OPTIK"
        $keyword = str_replace('OPTIK', 'OPTIC', $keyword);

        \Log::info("🔍 Mencari Approver via Jabatan. Keyword: '{$keyword}'");

        // 3. Query Database User
        $approvers = \App\Models\User::query()
            // FILTER UTAMA: Cari User yang JABATAN-nya mengandung kata kunci
            // Logika: JABATAN LIKE '%LOW VOLTAGE%' akan menemukan "LOW VOLTAGE MANAGER (LV)"
            ->where('jabatan', 'LIKE', "%{$keyword}%")

            // FILTER KEDUA: Pastikan Level-nya Manager atau Supervisor
            // Ini untuk membuang Admin/Staff/Foreman (seperti ID 47, 76, 144 di JSON Anda)
            ->whereIn('job_level', ['MANAGER', 'SUPERVISOR'])

            // FILTER TAMBAHAN: Hindari User Quality Assurance jika yang dicari bukan QA
            // Karena ada jabatan "FOREMAN QC FIBER OPTIC" (ID 43, 132), kita harus hindari itu
            ->when($keyword !== 'QUALITY ASSURANCE' && $keyword !== 'QA', function ($q) {
                $q->where('divisi', '!=', 'QUALITY ASSURANCE');
            })
            ->get();

        // 4. Cek Hasil & Log
        if ($approvers->isEmpty()) {
            \Log::warning("⚠️ GAGAL: Tidak ditemukan Manager/SPV dengan jabatan mengandung: '{$keyword}'");

            // [OPSIONAL] Fallback Terakhir: Cari berdasarkan kolom 'divisi' (jika data user normal)
            // Berguna untuk departemen lain yang datanya rapi (misal: HRGA, SECURITY)
            $approvers = \App\Models\User::where('divisi', $keyword)
                ->whereIn('job_level', ['MANAGER', 'SUPERVISOR'])
                ->get();

            if ($approvers->isNotEmpty()) {
                \Log::info("✅ SUKSES (via Kolom Divisi): " . $approvers->pluck('name'));
            }
        } else {
            \Log::info("✅ SUKSES (via Kolom Jabatan): " . $approvers->pluck('name'));
        }

        return $approvers;
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
