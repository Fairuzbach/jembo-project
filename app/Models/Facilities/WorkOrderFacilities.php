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
}
