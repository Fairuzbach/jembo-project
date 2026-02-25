<?php

namespace App\Models\Engineering;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Engineering\Plant;
use App\Models\Engineering\Machine;

class EngCompoundCheck extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi ke tabel Plant
    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id');
    }

    // Relasi ke tabel Mesin (Sesuaikan path Model Machine Anda)
    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    // Relasi ke User (Diperiksa Oleh)
    public function pemeriksa()
    {
        return $this->belongsTo(User::class, 'diperiksa_oleh');
    }

    // Relasi ke User (Diketahui Oleh / Approver)
    public function approver()
    {
        return $this->belongsTo(User::class, 'diketahui_oleh');
    }
}
