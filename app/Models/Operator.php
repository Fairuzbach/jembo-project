<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    // Tambahkan baris di bawah ini:
    protected $fillable = [
        'nik',
        'name',
        'plant',
    ];
}
