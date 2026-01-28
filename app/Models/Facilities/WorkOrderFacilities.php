<?php

namespace App\Models\Facilities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Engineering\Plant;
use App\Models\FacilityTech;
use App\Models\Engineering\Machine;

class WorkOrderFacilities extends Model
{
    use HasFactory;

    protected $table = 'work_order_facilities';
    protected $guarded = ['id'];

    // 1. Relasi ke User (Requester)
    public function user()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // 2. Relasi ke Teknisi (PERBAIKAN PIVOT)
    public function technicians()
    {
        return $this->belongsToMany(
            FacilityTech::class,
            'facility_tech_work_order', // <--- Nama Tabel Pivot Asli Bapak
            'work_order_facility_id',   // <--- Nama Kolom FK untuk WO (Cek database, apakah ini atau 'work_order_id'?)
            'facility_tech_id'          // <--- Nama Kolom FK untuk Teknisi (Cek database, apakah ini atau 'tech_id'?)
        ); // Hapus ->withTimestamps() jika tabel pivot Bapak tidak punya kolom created_at/updated_at
    }

    // 3. Relasi ke Mesin
    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id');
    }
    /**
     * Cek apakah User boleh approve tiket ini (Untuk View Blade)
     */
    public function canApproveBy($user)
    {
        // 1. Cek Status
        if (!in_array($this->status, ['waiting_approval', 'pending'])) return false;

        $userRole    = strtolower(trim($user->role));
        $ticketPlant = trim($this->plant);
        $userDivisi  = strtoupper(trim($user->divisi ?? ''));
        $userLevel   = strtoupper(trim($user->job_level ?? ''));
        $userJabatan = strtoupper(trim($user->jabatan ?? ''));

        // [FIX 1] Hapus 'mv.admin' dari sini!
        // Hanya 'fh.admin' (Facility) dan 'super.admin' yang boleh bypass segalanya.
        if (in_array($userRole, ['fh.admin', 'super.admin']) || $user->divisi === 'Facility') {
            return true;
        }

        // Cek Jabatan (Harus Boss)
        $isSpv = str_contains($userLevel, 'SUPERVISOR') || str_contains($userLevel, 'SPV');
        $isMgr = str_contains($userLevel, 'MANAGER') || str_contains($userLevel, 'HEAD') || str_contains($userLevel, 'MGR');

        if (!$isSpv && !$isMgr) return false;

        // 2. LOGIC MATRIX (STRICT)
        $matrix = [
            'Plant D - CCV' => [
                'spv' => ['CCV Line', 'SUPERVISOR CCV', ''],
                'mgr' => ['MV D', 'Medium Voltage']
            ],
            'Plant D' => [
                'spv' => ['MV D', 'Medium Voltage', 'PLANT D'],
                'mgr' => ['MV D', 'Medium Voltage']
            ],
            'Plant A - Autowire' => [
                'spv' => ['SUPERVISOR AUTOWIRE'],
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

        $config = $matrix[$ticketPlant] ?? null;

        // JIKA PLANT ADA DI MATRIX (Strict Mode)
        if ($config) {
            // Cek SPV
            if ($isSpv) {
                foreach ($config['spv'] as $keyword) {
                    $key = strtoupper($keyword);
                    // Cek di DIVISI atau JABATAN
                    if (str_contains($userDivisi, $key) || str_contains($userJabatan, $key)) {
                        return true;
                    }
                }
            }
            // Cek Manager
            if ($isMgr) {
                foreach ($config['mgr'] as $keyword) {
                    $key = strtoupper($keyword);
                    if (str_contains($userDivisi, $key) || str_contains($userJabatan, $key)) {
                        return true;
                    }
                }
            }

            // [FIX 2] PENTING: RETURN FALSE DISINI
            // Artinya: "Saya kenal Plant ini (ada di matrix), tapi user ini tidak punya izin. TITIK."
            // Jangan biarkan dia lanjut ke Fallback!
            return false;
        }

        // 3. FALLBACK (Hanya jika plant TIDAK ADA di Matrix)
        // [FIX 3] Hapus logika terbalik "str_contains($ticketPlant, $userDivisi)"
        // Logika itu yang membuat "Plant D" bisa approve "Plant D - CCV"
        return str_contains($userDivisi, strtoupper($ticketPlant));
    }
}
