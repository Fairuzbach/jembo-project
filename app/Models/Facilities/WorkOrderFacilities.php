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
        if (!in_array($this->status, ['waiting_approval', 'waiting_facility_approval'])) return false;

        $userRole    = strtolower(trim($user->role));
        $userDivisi  = strtoupper(trim($user->divisi ?? ''));
        $userLevel   = strtoupper(trim($user->job_level ?? ''));
        $ticketPlant = strtoupper(trim($this->plant ?? ''));

        // Admin bypass
        if (in_array($userRole, ['fh.admin', 'super.admin', 'super.fh.admin']) || $userDivisi === 'FACILITY') {
            return true;
        }

        // Harus SPV/Manager/Director
        $isSpv = str_contains($userLevel, 'SUPERVISOR') || str_contains($userLevel, 'SPV');
        $isMgr = str_contains($userLevel, 'MANAGER') || str_contains($userLevel, 'HEAD') ||
            str_contains($userLevel, 'MGR') || str_contains($userLevel, 'DIRECTOR');

        if (!$isSpv && !$isMgr) return false;

        // Matrix — key pakai UPPERCASE agar konsisten
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
                'spv' => ['COMMERCIAL & SUPPLY CHAIN'],
                'mgr' => ['COMMERCIAL & SUPPLY CHAIN'],
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
            if ($isSpv) {
                foreach ($config['spv'] as $keyword) {
                    if (str_contains($userDivisi, strtoupper($keyword))) return true;
                }
            }
            if ($isMgr) {
                foreach ($config['mgr'] as $keyword) {
                    if (str_contains($userDivisi, strtoupper($keyword))) return true;
                }
            }
            return false; // Ada di matrix tapi tidak match → tolak
        }

        // Fallback: exact match divisi = plant
        return $userDivisi === $ticketPlant;
    }
}
