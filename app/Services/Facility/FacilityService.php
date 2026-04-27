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
     * =========================================================================
     * HELPER: Generate URL tiket untuk dikirim via WA
     * Format: https://domain.com/facility?open={id}
     * =========================================================================
     */
    private function ticketUrl($ticketId): string
    {
        return route('fh.index') . '?open=' . $ticketId;
    }

    /**
     * =========================================================================
     * HELPER: Format pesan WA standar
     * Profesional, tanpa icon berlebihan
     * =========================================================================
     */
    private function formatWaMessage(string $title, array $rows, string $footer = '', string $url = ''): string
    {
        $msg  = "*WORK ORDER FACILITY*\n";
        $msg .= str_repeat('-', 30) . "\n";
        $msg .= strtoupper($title) . "\n";
        $msg .= str_repeat('-', 30) . "\n\n";

        foreach ($rows as $label => $value) {
            $msg .= "*{$label}:* {$value}\n";
        }

        if ($footer) {
            $msg .= "\n{$footer}\n";
        }

        if ($url) {
            $msg .= "\nDetail tiket: {$url}";
        }

        return $msg;
    }

    /**
     * =========================================================================
     * LOGIC 1: CREATE TICKET
     * Alur notifikasi:
     * - Admin    → tidak ada notif ke approver (langsung pending)
     * - SPV      → notif ke admin facility + notif ke manager divisi SPV
     * - Manager  → notif ke admin facility
     * - Staff    → notif ke SPV dan Manager divisi requester
     * =========================================================================
     */
    public function createTicket(array $data, $file = null)
    {
        return DB::transaction(function () use ($data, $file) {
            $user      = auth()->user();
            $userLevel = strtoupper(trim($user->job_level ?? ''));

            // --- Tentukan role user ---
            $isAdmin = in_array($user->role, ['fh.admin', 'super.fh.admin', 'super.admin']);

            $isBoss = str_contains($userLevel, 'SUPERVISOR') ||
                str_contains($userLevel, 'SPV') ||
                str_contains($userLevel, 'MANAGER') ||
                str_contains($userLevel, 'HEAD') ||
                str_contains($userLevel, 'MGR');

            // --- Tentukan status awal ---
            $initialStatus = match (true) {
                $isAdmin => 'pending',
                $isBoss  => 'waiting_facility_approval',
                default  => 'waiting_approval',
            };

            // --- Ambil nama plant ---
            $plantName = Plant::where('id', $data['plant_id'])->value('name') ?? '-';

            // --- Handle file upload ---
            $photoPath = $file ? $file->storeAs(
                'wo_facilities',
                time() . '_' . str_replace(' ', '_', $file->getClientOriginalName()),
                'public'
            ) : null;

            // --- Generate nomor tiket ---
            $dateCode   = date('Ymd');
            $prefix     = 'FAC-' . $dateCode . '-';
            $lastTicket = WorkOrderFacilities::where('ticket_num', 'like', $prefix . '%')
                ->orderBy('id', 'desc')->lockForUpdate()->first();
            $newSeq     = $lastTicket ? ((int)substr($lastTicket->ticket_num, -3) + 1) : 1;
            $ticketNum  = $prefix . sprintf('%03d', $newSeq);

            // --- Logika mesin ---
            $machineId = $machineName = null;
            if ($data['category'] == 'Pemasangan Mesin' && !empty($data['new_machine_name'])) {
                $newMachine  = Machine::create([
                    'plant_id' => $data['plant_id'],
                    'name'     => $data['new_machine_name'],
                    'code'     => 'NEW-' . strtoupper(Str::random(5)),
                ]);
                $machineId   = $newMachine->id;
                $machineName = $newMachine->name;
            } elseif (!empty($data['machine_id'])) {
                $m           = Machine::find($data['machine_id']);
                $machineId   = $m->id ?? null;
                $machineName = $m->name ?? null;
            }

            // --- Simpan tiket ---
            $ticket = WorkOrderFacilities::create([
                'ticket_num'             => $ticketNum,
                'requester_id'           => Auth::id(),
                'requester_name'         => Auth::user()->name,
                'plant'                  => $plantName,
                'machine_id'             => $machineId,
                'machine_name'           => $machineName,
                'location_details'       => $data['location_detail'] ?? '-',
                'report_date'            => isset($data['report_date']) ? Carbon::parse($data['report_date']) : now(),
                'report_time'            => $data['report_time'] ?? now()->format('H:i'),
                'shift'                  => $data['shift'] ?? '-',
                'description'            => $data['description'],
                'category'               => $data['category'],
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'photo_path'             => $photoPath,
                'status'                 => $initialStatus,
            ]);

            // --- Kirim notifikasi sesuai role ---
            if ($isAdmin) {
                Log::info("FACILITY: Admin {$user->name} membuat tiket {$ticket->ticket_num} (bypass approval).");
            } elseif ($isBoss) {
                // Notif ke admin facility
                $this->notifyAdmins($ticket, 'fh_new');

                /**
                 * Jika yang buat tiket adalah SPV (bukan Manager/Head),
                 * kirim juga notif ke manager di divisi yang sama
                 */
                $isSpvOnly = (str_contains($userLevel, 'SUPERVISOR') || str_contains($userLevel, 'SPV')) &&
                    !str_contains($userLevel, 'MANAGER') &&
                    !str_contains($userLevel, 'HEAD') &&
                    !str_contains($userLevel, 'MGR');

                if ($isSpvOnly) {
                    $this->notifySpvManager($ticket, $user);
                }
            } else {
                // Staff → notif ke SPV dan Manager divisi requester
                $this->notifyApprovers($ticket, $plantName);
            }

            // Notif konfirmasi ke requester sendiri
            $this->safeMail($user->email, new FacilityNotification($ticket, 'created_info'));

            return [
                'success' => true,
                'message' => 'Tiket berhasil dibuat.',
                'data'    => $ticket,
            ];
        });
    }

    /**
     * =========================================================================
     * LOGIC 2: APPROVE TICKET
     * =========================================================================
     */
    public function approveTicket($id)
    {
        $ticket  = WorkOrderFacilities::find($id);
        if (!$ticket) return ['success' => false, 'message' => 'Tiket tidak ditemukan.'];

        $user    = auth()->user();
        $isAdmin = in_array($user->role, ['fh.admin', 'super.admin', 'super.fh.admin']) ||
            $user->divisi === 'FACILITY';

        if ($ticket->status === 'pending') {
            return ['success' => false, 'message' => 'Tiket sudah disetujui sebelumnya.'];
        }

        if ($ticket->requester_id == $user->id && !$isAdmin) {
            return ['success' => false, 'message' => 'Anda tidak dapat menyetujui tiket yang Anda buat sendiri.'];
        }

        if ($ticket->status == 'waiting_facility_approval') {
            if ($isAdmin) {
                $ticket->update(['status' => 'pending', 'updated_at' => now()]);
                $this->notifyRequesterWa($ticket, 'pending');
                $this->notifyRequester($ticket, 'status_update');
                Log::info("FACILITY: {$user->name} memverifikasi tiket {$ticket->ticket_num}.");
                return ['success' => true, 'message' => 'Tiket diverifikasi. Status: Pending.'];
            }
            return ['success' => false, 'message' => 'Hanya Admin Facility yang dapat memverifikasi tiket ini.'];
        }

        if ($ticket->status == 'waiting_approval') {
            if ($isAdmin) {
                $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);
                $this->notifyRequesterWa($ticket, 'waiting_facility_approval');
                $this->notifyRequester($ticket, 'status_update');
                $this->notifyAdmins($ticket, 'fh_new');
                return ['success' => true, 'message' => 'Disetujui (bypass admin). Menunggu verifikasi facility.'];
            }
            if ($this->checkApprovalMatrix($ticket->plant, $user)) {
                $ticket->update(['status' => 'waiting_facility_approval', 'updated_at' => now()]);
                $this->notifyRequesterWa($ticket, 'waiting_facility_approval');
                $this->notifyRequester($ticket, 'status_update');
                $this->notifyAdmins($ticket, 'fh_new');
                Log::info("FACILITY: {$user->name} menyetujui tiket {$ticket->ticket_num}.");
                return ['success' => true, 'message' => 'Tiket disetujui. Notifikasi dikirim ke Tim Facility.'];
            }
            Log::warning("FACILITY: {$user->name} gagal approve tiket {$ticket->plant}.");
            return ['success' => false, 'message' => 'Anda tidak memiliki wewenang untuk menyetujui tiket area ini.'];
        }

        return ['success' => false, 'message' => 'Status tiket tidak valid untuk proses ini.'];
    }

    /**
     * =========================================================================
     * LOGIC 3: REJECT TICKET
     * =========================================================================
     */
    public function rejectTicket($id, $reason)
    {
        $ticket = WorkOrderFacilities::findOrFail($id);

        $ticket->update([
            'status'                 => 'rejected',
            'rejection_reason'       => $reason . ' (Ditolak oleh: ' . Auth::user()->name . ')',
            'actual_completion_date' => null,
        ]);

        // Notif email ke requester
        $this->notifyRequester($ticket, 'status_update');

        // Notif WA ke requester
        $requester = User::find($ticket->requester_id);
        if ($requester && $requester->no_hp) {
            $msg = $this->formatWaMessage(
                'Tiket Ditolak',
                [
                    'Nomor Tiket'  => $ticket->ticket_num,
                    'Lokasi'       => $ticket->plant,
                    'Kategori'     => $ticket->category,
                    'Ditolak oleh' => Auth::user()->name,
                    'Alasan'       => $reason,
                ],
                'Silakan ajukan tiket baru jika masalah masih berlanjut.',
                $this->ticketUrl($ticket->id)
            );
            $this->safeWa($requester->no_hp, $msg, "REJECT to {$requester->name}");
        }

        Log::info("FACILITY: Tiket {$ticket->ticket_num} ditolak oleh " . Auth::user()->name);
        return ['success' => true, 'message' => 'Tiket berhasil ditolak.'];
    }

    /**
     * =========================================================================
     * LOGIC 4: UPDATE STATUS
     * =========================================================================
     */
    public function updateStatus($id, array $data)
    {
        $wo        = WorkOrderFacilities::findOrFail($id);
        $oldStatus = $wo->status;
        $wo->status = $data['status'];

        // Sync teknisi
        if (isset($data['facility_tech_ids'])) {
            $ids = $data['facility_tech_ids'];
            if (!is_array($ids)) $ids = explode(',', (string)$ids);
            $ids = array_filter($ids, fn($v) => is_numeric($v) && $v > 0);
            $wo->technicians()->sync($ids);
        }

        /**
         * Reload relasi technicians setelah sync
         * agar nama teknisi di notif WA sudah up-to-date
         */
        $wo->load('technicians');

        // Update tanggal start
        if (!empty($data['start_date'])) {
            $wo->start_date = $data['start_date'];
        }

        // Update tanggal selesai
        $wo->actual_completion_date = ($data['status'] === 'completed')
            ? ($wo->actual_completion_date ?? now())
            : null;

        // Set processed_by jika belum ada
        if (!$wo->processed_by) {
            $wo->processed_by      = Auth::id();
            $wo->processed_by_name = Auth::user()->name;
        }

        $wo->save();

        /**
         * FYI ke manager saat facility approve tiket
         * (status berubah dari waiting_facility_approval ke pending/in_progress)
         */
        if ($oldStatus === 'waiting_facility_approval' && in_array($data['status'], ['pending', 'in_progress'])) {
            $this->notifyManagersFyi($wo);
        }

        // Notif WA ke requester berdasarkan status baru
        $this->notifyRequesterWa($wo, $data['status']);

        // Notif email ke requester
        $this->notifyRequester($wo, 'status_update');

        return $wo;
    }

    /**
     * =========================================================================
     * HELPER: Notif WA ke requester berdasarkan status
     * Dipanggil dari approveTicket() dan updateStatus()
     * =========================================================================
     */
    private function notifyRequesterWa($wo, string $status): void
    {
        $requester = User::find($wo->requester_id);
        if (!$requester || !$requester->no_hp) {
            Log::warning("FACILITY WA SKIP: Requester {$wo->requester_name} tidak memiliki No HP.");
            return;
        }

        $url = $this->ticketUrl($wo->id);

        $msg = match ($status) {
            'waiting_facility_approval' => $this->formatWaMessage(
                'Tiket Disetujui Atasan',
                [
                    'Nomor Tiket' => $wo->ticket_num,
                    'Lokasi'      => $wo->plant,
                    'Kategori'    => $wo->category,
                    'Status'      => 'Menunggu Verifikasi Tim Facility',
                ],
                'Tiket Anda telah disetujui atasan dan sedang menunggu verifikasi Tim Facility.',
                $url
            ),

            'pending' => $this->formatWaMessage(
                'Tiket Diverifikasi',
                [
                    'Nomor Tiket' => $wo->ticket_num,
                    'Lokasi'      => $wo->plant,
                    'Kategori'    => $wo->category,
                    'Status'      => 'Pending - Menunggu Pengerjaan',
                ],
                'Tiket Anda telah diverifikasi oleh Tim Facility dan akan segera dijadwalkan.',
                $url
            ),

            'in_progress' => $this->formatWaMessage(
                'Tiket Sedang Dikerjakan',
                [
                    'Nomor Tiket'     => $wo->ticket_num,
                    'Lokasi'          => $wo->plant,
                    'Kategori'        => $wo->category,
                    'Status'          => 'In Progress',
                    'Dikerjakan oleh' => $wo->technicians->pluck('name')->join(', ') ?: 'Belum ditentukan',
                ],
                'Permintaan Anda sedang dikerjakan. Mohon menunggu hingga selesai.',
                $url
            ),

            'completed' => $this->formatWaMessage(
                'Tiket Selesai',
                [
                    'Nomor Tiket'     => $wo->ticket_num,
                    'Lokasi'          => $wo->plant,
                    'Kategori'        => $wo->category,
                    'Status'          => 'Completed',
                    'Tanggal Selesai' => now()->format('d/m/Y H:i'),
                ],
                'Pekerjaan telah selesai dilaksanakan. Terima kasih.',
                $url
            ),

            'cancelled' => $this->formatWaMessage(
                'Tiket Dibatalkan',
                [
                    'Nomor Tiket' => $wo->ticket_num,
                    'Lokasi'      => $wo->plant,
                    'Status'      => 'Cancelled',
                ],
                'Tiket Anda telah dibatalkan. Hubungi Tim Facility jika ada pertanyaan.',
                $url
            ),

            default => null,
        };

        if ($msg) {
            $this->safeWa($requester->no_hp, $msg, "STATUS [{$status}] to {$requester->name}");
        }
    }

    /**
     * =========================================================================
     * HELPER: FYI ke manager saat facility approve tiket
     * Hanya dipanggil dari updateStatus() saat status berubah dari
     * waiting_facility_approval ke pending/in_progress
     * =========================================================================
     */
    private function notifyManagersFyi($wo): void
    {
        $plantUpper = strtoupper(trim($wo->plant));

        $managers = User::where('is_active', 1)
            ->where(function ($q) {
                $q->where('job_level', 'LIKE', '%MANAGER%')
                    ->orWhere('job_level', 'LIKE', '%MGR%');
            })->get();

        // Filter manager yang tidak relevan dengan plant tiket
        if (str_contains($plantUpper, 'PLANT A') && !str_contains($plantUpper, 'AUTOWIRE')) {
            $managers = $managers->reject(fn($m) => str_contains(strtoupper($m->jabatan ?? ''), 'AUTOWIRE'));
        } elseif (str_contains($plantUpper, 'PLANT D') && !str_contains($plantUpper, 'CCV')) {
            $managers = $managers->reject(fn($m) => str_contains(strtoupper($m->jabatan ?? ''), 'CCV'));
        }

        foreach ($managers as $manager) {
            if (!$manager->no_hp) continue;

            $msg = $this->formatWaMessage(
                'Informasi Work Order Facility',
                [
                    'Nomor Tiket' => $wo->ticket_num,
                    'Pelapor'     => $wo->requester_name,
                    'Lokasi'      => $wo->plant,
                    'Kategori'    => $wo->category,
                    'Deskripsi'     => $wo->description,
                    'Status'      => 'Disetujui - Akan Segera Dikerjakan',
                ],
                'Pesan ini dikirim sebagai tembusan informasi.',
                $this->ticketUrl($wo->id)
            );

            $this->safeWa($manager->no_hp, $msg, "FYI MANAGER to {$manager->name}");
        }
    }

    /**
     * =========================================================================
     * HELPER: Notif ke manager dari SPV yang membuat tiket
     * Dipanggil dari createTicket() saat SPV (bukan Manager) membuat tiket
     * =========================================================================
     */
    private function notifySpvManager($ticket, $spvUser): void
    {
        $managers = User::where('is_active', 1)
            ->where('divisi', $spvUser->divisi) // Divisi sama dengan SPV
            ->where(function ($q) {
                $q->where('job_level', 'LIKE', '%MANAGER%')
                    ->orWhere('job_level', 'LIKE', '%MGR%')
                    ->orWhere('job_level', 'LIKE', '%HEAD%');
            })
            ->where('id', '!=', $ticket->requester_id) // Jangan notif ke diri sendiri
            ->get();

        foreach ($managers as $manager) {
            // Email
            $this->safeMail($manager->email, new FacilityNotification($ticket, 'need_approval'));

            // WA
            if (!$manager->no_hp) {
                Log::warning("FACILITY WA SKIP: Manager {$manager->name} tidak memiliki No HP.");
                continue;
            }

            $msg = $this->formatWaMessage(
                'Informasi Tiket Baru dari SPV Anda',
                [
                    'Nomor Tiket' => $ticket->ticket_num,
                    'Dibuat oleh' => $spvUser->name . ' (' . $spvUser->jabatan . ')',
                    'Lokasi'      => $ticket->plant,
                    'Kategori'    => $ticket->category,
                    'Deskripsi'     => $ticket->description,
                    'Status'      => 'Menunggu Verifikasi Tim Facility',
                ],
                'Pesan ini dikirim sebagai tembusan informasi.',
                $this->ticketUrl($ticket->id)
            );

            $this->safeWa($manager->no_hp, $msg, "SPV MANAGER NOTIFY to {$manager->name}");
        }
    }

    /**
     * =========================================================================
     * NOTIFY ADMINS: Kirim notif ke admin/manager facility
     * Dipanggil saat: SPV/Manager buat tiket, atau tiket sudah diapprove SPV
     * =========================================================================
     */
    private function notifyAdmins($ticket, $type): void
    {
        $recipients = User::where('is_active', 1)
            ->where('jabatan', 'NOT LIKE', '%HSE%')
            ->where(function ($q) {
                $q->whereIn('role', ['fh.admin', 'fh.manager', 'super.admin'])
                    ->orWhere(function ($sub) {
                        $sub->where('divisi', 'FACILITY')
                            ->where(function ($lvl) {
                                $lvl->where('job_level', 'LIKE', '%MANAGER%')
                                    ->orWhere('job_level', 'LIKE', '%SUPERVISOR%')
                                    ->orWhere('job_level', 'LIKE', '%MGR%');
                            });
                    });
            })->get();

        foreach ($recipients as $admin) {
            if ($admin->id == $ticket->requester_id) continue;

            // Email
            $this->safeMail($admin->email, new FacilityNotification($ticket, $type));

            // WA
            if (!$admin->no_hp) continue;

            $msg = $this->formatWaMessage(
                $type === 'fh_new' ? 'Tiket Perlu Verifikasi' : 'Tiket Baru',
                [
                    'Nomor Tiket' => $ticket->ticket_num,
                    'Pelapor'     => $ticket->requester_name,
                    'Lokasi'      => $ticket->plant,
                    'Kategori'    => $ticket->category,
                    'Deskripsi'     => $ticket->description,
                    'Status'      => $type === 'fh_new'
                        ? 'Sudah disetujui atasan, menunggu verifikasi Facility'
                        : 'Menunggu persetujuan atasan',
                ],
                'Mohon segera ditindaklanjuti.',
                $this->ticketUrl($ticket->id)
            );

            $this->safeWa($admin->no_hp, $msg, "ADMIN NOTIFY [{$type}] to {$admin->name}");
        }
    }

    /**
     * =========================================================================
     * NOTIFY APPROVERS: Kirim notif ke SPV dan Manager divisi requester
     * Dipanggil saat staff biasa membuat tiket baru
     * Notif dikirim ke SPV dan Manager sekaligus (tidak dibedakan level requester)
     * =========================================================================
     */
    private function notifyApprovers($ticket, $plantName): void
    {
        $matrix = $this->getFacilityMatrix();
        $config = $matrix[strtoupper(trim($plantName))] ?? null;
        $url    = $this->ticketUrl($ticket->id);

        $query = User::where('is_active', 1);

        if ($config) {
            $query->where(function ($q) use ($config) {
                // Cari SPV yang sesuai divisi
                $q->where(function ($spv) use ($config) {
                    $spv->where(function ($lvl) {
                        $lvl->where('job_level', 'LIKE', '%SUPERVISOR%')
                            ->orWhere('job_level', 'LIKE', '%SPV%');
                    });
                    foreach ($config['spv'] as $keyword) {
                        $spv->where(function ($k) use ($keyword) {
                            $k->where('divisi', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('jabatan', 'LIKE', '%' . $keyword . '%');
                        });
                    }
                });

                // ATAU cari Manager yang sesuai divisi
                $q->orWhere(function ($mgr) use ($config) {
                    $mgr->where(function ($lvl) {
                        $lvl->where('job_level', 'LIKE', '%MANAGER%')
                            ->orWhere('job_level', 'LIKE', '%MGR%')
                            ->orWhere('job_level', 'LIKE', '%HEAD%');
                    });
                    foreach ($config['mgr'] as $keyword) {
                        $mgr->where(function ($k) use ($keyword) {
                            $k->where('divisi', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('jabatan', 'LIKE', '%' . $keyword . '%');
                        });
                    }
                });
            });
        } else {
            // Fallback: exact match divisi jika plant tidak ada di matrix
            $query->where('divisi', strtoupper(trim($plantName)))
                ->where(function ($q) {
                    $q->where('job_level', 'LIKE', '%SUPERVISOR%')
                        ->orWhere('job_level', 'LIKE', '%SPV%')
                        ->orWhere('job_level', 'LIKE', '%MANAGER%')
                        ->orWhere('job_level', 'LIKE', '%MGR%')
                        ->orWhere('job_level', 'LIKE', '%HEAD%');
                });
        }

        $approvers  = $query->get();
        $plantUpper = strtoupper(trim($plantName));

        // Exclude Autowire dari Plant A biasa dan CCV dari Plant D biasa
        if ($plantUpper === 'PLANT A') {
            $approvers = $approvers->reject(fn($u) => str_contains(strtoupper($u->jabatan ?? ''), 'AUTOWIRE'));
        } elseif ($plantUpper === 'PLANT D') {
            $approvers = $approvers->reject(fn($u) => str_contains(strtoupper($u->jabatan ?? ''), 'CCV'));
        }

        foreach ($approvers as $approver) {
            // Email
            $this->safeMail($approver->email, new FacilityNotification($ticket, 'need_approval'));

            // WA
            if (!$approver->no_hp) {
                Log::warning("FACILITY WA SKIP: Approver {$approver->name} tidak memiliki No HP.");
                continue;
            }

            $msg = $this->formatWaMessage(
                'Permintaan Persetujuan',
                [
                    'Kepada'      => $approver->name,
                    'Nomor Tiket' => $ticket->ticket_num,
                    'Pelapor'     => $ticket->requester_name,
                    'Lokasi'      => $ticket->plant,
                    'Kategori'    => $ticket->category,
                    'Deskripsi'     => $ticket->description,
                ],
                'Mohon segera berikan persetujuan Anda.',
                $url
            );

            $this->safeWa($approver->no_hp, $msg, "APPROVER NOTIFY to {$approver->name}");
        }
    }

    /**
     * =========================================================================
     * CORE LOGIC: Cek Approval Matrix
     * Menentukan apakah user berwenang approve tiket berdasarkan plant
     * =========================================================================
     */
    private function checkApprovalMatrix($ticketPlant, $user)
    {
        $userDivisi  = strtoupper(trim($user->divisi ?? ''));
        $userLevel   = strtoupper(trim($user->job_level ?? ''));
        $userJabatan = strtoupper(trim($user->jabatan ?? ''));
        $plantTarget = strtoupper(trim($ticketPlant));

        Log::info("FACILITY APPROVAL CHECK: User={$user->name}, Divisi={$userDivisi}, Plant={$plantTarget}");

        // Strict blocking untuk CCV dan Autowire
        if ($plantTarget === 'PLANT D' && (str_contains($userDivisi, 'CCV') || str_contains($userJabatan, 'CCV'))) {
            Log::warning("FACILITY BLOCKED: User CCV {$user->name} mencoba approve Plant D.");
            return false;
        }
        if ($plantTarget === 'PLANT A' && (str_contains($userDivisi, 'AUTOWIRE') || str_contains($userJabatan, 'AUTOWIRE'))) {
            Log::warning("FACILITY BLOCKED: User Autowire {$user->name} mencoba approve Plant A.");
            return false;
        }

        $matrix = $this->getFacilityMatrix();
        $config  = $matrix[$plantTarget] ?? null;

        if ($config) {
            // Cek SPV
            if (str_contains($userLevel, 'SUPERVISOR') || str_contains($userLevel, 'SPV')) {
                foreach ($config['spv'] as $keyword) {
                    $key = strtoupper($keyword);
                    if (str_contains($userDivisi, $key) || str_contains($userJabatan, $key)) {
                        return true;
                    }
                }
            }
            // Cek Manager
            if (str_contains($userLevel, 'MANAGER') || str_contains($userLevel, 'HEAD') || str_contains($userLevel, 'MGR')) {
                foreach ($config['mgr'] as $keyword) {
                    $key = strtoupper($keyword);
                    if (str_contains($userDivisi, $key) || str_contains($userJabatan, $key)) {
                        return true;
                    }
                }
            }
            // Ada di matrix tapi tidak match → tolak
            return false;
        }

        // Fallback: exact match divisi = plant
        return $userDivisi === $plantTarget;
    }

    /**
     * =========================================================================
     * FACILITY MATRIX
     * Mapping plant → divisi SPV dan Manager yang berwenang
     * Key harus UPPERCASE agar konsisten dengan normalisasi di checkApprovalMatrix()
     *
     * Disesuaikan dengan nilai kolom 'divisi' yang benar-benar ada di database:
     * - PLANT A, PLANT A - AUTOWIRE, PLANT B, PLANT C, PLANT D, PLANT D - CCV,
     *   PLANT E, PP (Production Planning), SS (Commercial & Supply Chain), MT (Maintenance)
     * =========================================================================
     */
    private function getFacilityMatrix(): array
    {
        return [
            'PLANT A' => [
                'spv' => ['PLANT A'],
                'mgr' => ['PLANT A'],
            ],
            'PLANT A - AUTOWIRE' => [
                'spv' => ['PLANT A - AUTOWIRE'],
                'mgr' => ['PLANT A - AUTOWIRE', 'PLANT A'],
            ],
            'PLANT B' => [
                'spv' => ['PLANT B'],
                'mgr' => ['PLANT B'],
            ],
            'PLANT C' => [
                'spv' => ['PLANT C'],
                'mgr' => ['PLANT C'],
            ],
            'PLANT D' => [
                'spv' => ['PLANT D'],
                'mgr' => ['PLANT D'],
            ],
            'PLANT D - CCV' => [
                'spv' => ['PLANT D - CCV'],
                'mgr' => ['PLANT D - CCV', 'PLANT D'],
            ],
            'PLANT E' => [
                'spv' => ['PLANT E'],
                'mgr' => ['PLANT E'],
            ],
            'PP' => [
                'spv' => ['PRODUCTION PLANNING'],
                'mgr' => ['PRODUCTION PLANNING'],
            ],
            'SS' => [
                'spv' => ['SALES SUPPORT'],
                'mgr' => ['SALES SUPPORT'],
            ],
            'MT' => [
                'spv' => ['MAINTENANCE'],
                'mgr' => ['MAINTENANCE'],
            ],
            'PROCUREMENT' => [
                'spv' => ['PROCUREMENT'],
                'mgr' => ['PROCUREMENT'],
            ],
        ];
    }

    /**
     * =========================================================================
     * NOTIFICATION HELPERS
     * =========================================================================
     */

    /**
     * Kirim email dengan try-catch agar tidak interrupt flow utama
     */
    private function safeMail(?string $to, $mailable): void
    {
        if (empty($to)) return;
        try {
            Mail::to($to)->send($mailable);
            Log::info("FACILITY EMAIL SENT to: {$to}");
        } catch (\Exception $e) {
            Log::error("FACILITY EMAIL FAILED to {$to}: " . $e->getMessage());
        }
    }

    /**
     * Kirim WA dengan try-catch dan logging standar
     */
    private function safeWa(string $phone, string $message, string $context = ''): void
    {
        try {
            GaWhatsappService::send($phone, $message);
            Log::info("FACILITY WA SENT [{$context}] to {$phone}");
        } catch (\Exception $e) {
            Log::error("FACILITY WA FAILED [{$context}]: " . $e->getMessage());
        }
    }

    /**
     * Notif email ke requester
     */
    private function notifyRequester($ticket, $type): void
    {
        $requester = User::find($ticket->requester_id);
        if ($requester && $requester->email) {
            $this->safeMail($requester->email, new FacilityNotification($ticket, $type));
        }
    }

    /**
     * =========================================================================
     * DASHBOARD & GANTT (Tidak ada perubahan)
     * =========================================================================
     */
    public function getDashboardStats($request)
    {
        $query = WorkOrderFacilities::where('status', '!=', 'cancelled');

        if ($request->filled('month')) {
            try {
                $start = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()->format('Y-m-d');
                $end   = Carbon::createFromFormat('Y-m', $request->month)->endOfMonth()->format('Y-m-d');
                $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
            } catch (\Exception $e) {
            }
        }

        $workOrders      = $query->with(['technicians', 'machine', 'user'])->latest()->get();
        $categoryGrouped = $workOrders->groupBy('category');
        $statusGrouped   = $workOrders->groupBy('status');
        $plantGrouped    = $workOrders->groupBy('plant');

        $stats = [
            'workOrders'        => $workOrders,
            'countTotal'        => $workOrders->count(),
            'countPending'      => $workOrders->where('status', 'pending')->count(),
            'countProgress'     => $workOrders->where('status', 'in_progress')->count(),
            'countDone'         => $workOrders->where('status', 'completed')->count(),
            'chartCatLabels'    => $categoryGrouped->keys()->values()->toArray(),
            'chartCatValues'    => $categoryGrouped->map->count()->values()->toArray(),
            'chartStatusLabels' => $statusGrouped->keys()->values()->toArray(),
            'chartStatusValues' => $statusGrouped->map->count()->values()->toArray(),
            'chartPlantLabels'  => $plantGrouped->keys()->values()->toArray(),
            'chartPlantValues'  => $plantGrouped->map->count()->values()->toArray(),
        ];

        $techData = [];
        foreach ($workOrders as $wo) {
            foreach ($wo->technicians as $tech) {
                $techData[$tech->name] = ($techData[$tech->name] ?? 0) + 1;
            }
        }
        arsort($techData);
        $stats['chartTechLabels'] = array_keys($techData);
        $stats['chartTechValues'] = array_values($techData);
        $stats['ganttData']       = $this->formatGanttData($workOrders);
        $stats['selectedMonth']   = $request->input('month', date('Y-m'));

        return $stats;
    }

    public function getGanttChartData()
    {
        $tickets = WorkOrderFacilities::with(['technicians'])->latest()->limit(100)->get();
        return $this->formatGanttData($tickets);
    }

    private function formatGanttData($collection)
    {
        $ganttData = [];
        foreach ($collection as $wo) {
            $startObj = $wo->created_at ?? now();
            $start    = $startObj->format('Y-m-d H:i:s');

            if ($wo->status == 'completed' && $wo->actual_completion_date) {
                $endObj = Carbon::parse($wo->actual_completion_date);
            } else {
                $endObj = $wo->target_completion_date ? Carbon::parse($wo->target_completion_date) : now();
            }

            if ($endObj->lt($startObj)) {
                $endObj = $startObj->copy()->addHours(1);
            }

            $duration    = max(1, $startObj->diffInDays($endObj) + 1);
            $ganttData[] = [
                'id'          => $wo->id,
                'text'        => $wo->ticket_num . ' - ' . Str::limit($wo->description, 30),
                'start_date'  => $start,
                'duration'    => $duration,
                'progress'    => ($wo->status == 'completed') ? 1 : (($wo->status == 'in_progress') ? 0.5 : 0),
                'color'       => $this->getStatusColor($wo->status),
                'open'        => true,
                'plant'       => $wo->plant ?? '-',
                'status'      => strtoupper($wo->status),
                'technician'  => $wo->technicians->pluck('name')->join(', ') ?: 'Unassigned',
                'description' => Str::limit($wo->description, 100),
            ];
        }

        return ['data' => $ganttData, 'links' => []];
    }

    private function getStatusColor($status): string
    {
        return match ($status) {
            'completed'   => '#10b981',
            'in_progress' => '#3b82f6',
            'pending'     => '#f59e0b',
            'rejected'    => '#ef4444',
            default       => '#6b7280',
        };
    }
}
