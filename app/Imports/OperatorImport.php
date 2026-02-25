<?php

namespace App\Imports;

use App\Models\Operator;
use Maatwebsite\Excel\Concerns\ToModel;

class OperatorImport implements ToModel
{
    public function model(array $row)
    {
        // 1. Ambil data mentah (Sesuaikan indeks kolom: 1=B, 2=C, 4=E)
        $rawNik   = $row[0] ?? null;
        $nama     = $row[1] ?? null;
        $rawPlant = $row[2] ?? null;

        // 2. Bersihkan NIK & Tambahkan angka 0 di depan (misal panjang NIK harus 4 digit)
        // Kita bersihkan dulu kalau ada spasi atau karakter aneh
        $nikClean = preg_replace('/[^0-9]/', '', $rawNik);

        if (empty($nikClean) || $rawNik == 'NIK') {
            return null;
        }

        // Tambahkan nol di depan jika kurang dari 4 digit (sesuaikan angka 4 dengan kebutuhan Anda)
        $nikFinal = str_pad($nikClean, 4, '0', STR_PAD_LEFT);

        // 3. Filter ketat Plant A sampai Plant C
        $plantClean = strtoupper(trim($rawPlant));
        $allowedPlants = ['PLANT A', 'PLANT B', 'PLANT C'];

        if (!in_array($plantClean, $allowedPlants)) {
            return null; // Jika bukan A, B, atau C, jangan diimport
        }

        // 4. Masukkan ke Database
        return new Operator([
            'nik'   => $nikFinal,
            'name'  => $nama,
            'plant' => $rawPlant, // Menggunakan nama asli dari Excel (Plant A)
        ]);
    }
}
