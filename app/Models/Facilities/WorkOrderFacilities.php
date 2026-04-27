<?php

namespace App\Models\Facilities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Engineering\Plant;
use App\Models\FacilityTech;
use App\Models\Engineering\Machine;

/**
 * =========================================================================
 * WorkOrderFacilities Model
 * =========================================================================
 * Model untuk tabel work_order_facilities.
 * Mengelola data Work Order dari divisi Facility, mulai dari pembuatan
 * tiket, proses approval, hingga penyelesaian pekerjaan.
 *
 * Alur status tiket:
 * waiting_approval → waiting_facility_approval → pending → in_progress → completed
 *                                              ↘ rejected
 *
 * Relations:
 * - user()        → User yang membuat tiket (requester)
 * - technicians() → FacilityTech yang mengerjakan (many-to-many)
 * - machine()     → Mesin yang terkait dengan tiket
 * - plant()       → Plant/lokasi tiket
 * =========================================================================
 */
class WorkOrderFacilities extends Model
{
    use HasFactory;

    protected $table = 'work_order_facilities';

    /**
     * Semua kolom bisa diisi kecuali 'id' (auto-increment).
     * Gunakan $fillable jika ingin lebih eksplisit di masa depan.
     */
    protected $guarded = ['id'];

    /**
     * Relasi ke User yang membuat tiket.
     * Menggunakan foreign key 'requester_id' (bukan default 'user_id').
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Relasi many-to-many ke FacilityTech (Teknisi).
     * Melalui tabel pivot: facility_tech_work_order
     *
     * Kolom pivot:
     * - work_order_facility_id → FK ke tabel work_order_facilities
     * - facility_tech_id       → FK ke tabel facility_teches
     */
    public function technicians()
    {
        return $this->belongsToMany(
            FacilityTech::class,
            'facility_tech_work_order',
            'work_order_facility_id',
            'facility_tech_id'
        );
    }

    /**
     * Relasi ke Mesin yang terkait dengan tiket.
     * Nullable — tidak semua tiket memiliki mesin terkait.
     */
    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    /**
     * Relasi ke Plant/lokasi tiket.
     * Catatan: kolom 'plant' di tabel menyimpan nama plant (string),
     * sedangkan relasi ini menggunakan 'plant_id' sebagai foreign key.
     */
    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id');
    }

    /**
     * =========================================================================
     * METHOD: canApproveBy($user)
     * =========================================================================
     * Menentukan apakah user tertentu berwenang untuk approve tiket ini.
     * Digunakan di Blade untuk mengontrol tampilan tombol Approve/Reject.
     *
     * Hierarki pengecekan:
     * 1. Cek status tiket — hanya waiting_approval & waiting_facility_approval
     * 2. Admin bypass — fh.admin, super.admin, super.fh.admin, divisi FACILITY
     * 3. Cek job level — harus SPV, Manager, Head, atau Director
     * 4. Strict matrix check — cocokkan divisi user dengan plant tiket
     * 5. Fallback — exact match divisi = plant (untuk plant yang tidak ada di matrix)
     *
     * Matrix disesuaikan dengan nilai kolom 'divisi' yang ada di database:
     * PLANT A, PLANT A - AUTOWIRE, PLANT B, PLANT C, PLANT D, PLANT D - CCV,
     * PLANT E, PP (Production Planning), SS (Commercial & Supply Chain),
     * MT (Maintenance), PROCUREMENT
     *
     * @param  \App\Models\User $user
     * @return bool
     * =========================================================================
     */
    public function canApproveBy($user): bool
    {
        // Hanya tiket dengan status berikut yang bisa di-approve
        if (!in_array($this->status, ['waiting_approval', 'waiting_facility_approval'])) {
            return false;
        }

        $userRole    = strtolower(trim($user->role));
        $userDivisi  = strtoupper(trim($user->divisi ?? ''));
        $userLevel   = strtoupper(trim($user->job_level ?? ''));
        $ticketPlant = strtoupper(trim($this->plant ?? ''));

        // Admin selalu bisa approve tiket apapun
        if (in_array($userRole, ['fh.admin', 'super.admin', 'super.fh.admin']) || $userDivisi === 'FACILITY') {
            return true;
        }

        // User biasa harus minimal SPV, Manager, Head, atau Director
        $isSpv = str_contains($userLevel, 'SUPERVISOR') || str_contains($userLevel, 'SPV');
        $isMgr = str_contains($userLevel, 'MANAGER')    || str_contains($userLevel, 'HEAD') ||
            str_contains($userLevel, 'MGR')         || str_contains($userLevel, 'DIRECTOR');

        if (!$isSpv && !$isMgr) return false;

        /**
         * Approval Matrix
         * ---------------
         * Mapping plant → divisi yang berwenang approve.
         * Key menggunakan UPPERCASE agar konsisten dengan normalisasi $ticketPlant di atas.
         *
         * Format:
         * 'NAMA_PLANT' => [
         *     'spv' => ['DIVISI_SPV_YANG_BERWENANG'],
         *     'mgr' => ['DIVISI_MANAGER_YANG_BERWENANG'],
         * ]
         *
         * Catatan Plant A - Autowire & Plant D - CCV:
         * Manager dari plant induk (PLANT A / PLANT D) juga berwenang
         * approve tiket sub-plant mereka.
         */
        $matrix = [
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

        $config = $matrix[$ticketPlant] ?? null;

        if ($config) {
            // Cek SPV — cocokkan divisi user dengan keyword di matrix
            if ($isSpv) {
                foreach ($config['spv'] as $keyword) {
                    if (str_contains($userDivisi, strtoupper($keyword))) return true;
                }
            }

            // Cek Manager/Head/Director — cocokkan divisi user dengan keyword di matrix
            if ($isMgr) {
                foreach ($config['mgr'] as $keyword) {
                    if (str_contains($userDivisi, strtoupper($keyword))) return true;
                }
            }

            /**
             * Plant ditemukan di matrix tapi divisi user tidak match.
             * Return false — jangan lanjut ke fallback agar tidak ada bypass.
             */
            return false;
        }

        /**
         * Fallback: untuk plant yang belum terdaftar di matrix.
         * Gunakan exact match antara divisi user dan nama plant.
         * Tambahkan plant ke matrix di atas jika sering digunakan.
         */
        return $userDivisi === $ticketPlant;
    }
}
