<?php

namespace App\Models\GeneralAffair;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    // Nama tabel (opsional jika nama tabelnya 'categories')
    protected $table = 'categories';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'name',
        'description',
        'color',  // Untuk menyimpan pilihan warna (blue, green, red, dll)
        'status', // Untuk status (active/inactive)
    ];
}
