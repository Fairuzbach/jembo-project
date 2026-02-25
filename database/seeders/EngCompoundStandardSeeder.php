<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Engineering\EngCompoundStandard;

class EngCompoundStandardSeeder extends Seeder
{
    public function run()
    {
        // Bersihkan tabel dulu supaya tidak dobel kalau dijalankan berkali-kali
        EngCompoundStandard::truncate();

        // ==========================================
        // 1. DATA STANDAR PLANT A (6 BAK)
        // ==========================================
        $plantABaks = [
            1 => ['nama' => 'BAK 1 (HD 10 C)', 'draw_type' => 'Lubricool 22 G', 'draw_kons' => '10% - 12%'],
            2 => ['nama' => 'BAK 2 (MD 1)', 'draw_type' => 'Lubricool 450', 'draw_kons' => '6% - 8%'],
            3 => ['nama' => 'BAK 3 (QDMD Deyang)', 'draw_type' => 'Lubricool 450', 'draw_kons' => '6% - 8%'],
            4 => ['nama' => 'BAK 4 (Multi 2 Samp)', 'draw_type' => 'Lubricool 450', 'draw_kons' => '6% - 8%'],
            5 => ['nama' => 'BAK 5 (Multi 1 Samp)', 'draw_type' => 'Lubricool 450', 'draw_kons' => '6% - 8%'],
            6 => ['nama' => 'BAK 6 (Twin RBD Cu)', 'draw_type' => 'Lubricool 22 G', 'draw_kons' => '10% - 12%'],
        ];

        foreach ($plantABaks as $key => $bak) {
            // Standar Drawing Plant A
            EngCompoundStandard::create([
                'plant'           => 'Plant A',
                'kode_mesin'      => 'bak_' . $key,
                'nama_mesin'      => $bak['nama'],
                'proses'          => 'drawing',
                'std_tipe'        => $bak['draw_type'],
                'std_supplier'    => 'METALUBE',
                'std_warna'       => 'Hijau Putih',
                'std_konsentrasi' => $bak['draw_kons'],
                'std_ph'          => '8 - 9',
                'std_temp'        => '35°C - 40°C',
            ]);

            // Standar Annealing Plant A
            EngCompoundStandard::create([
                'plant'           => 'Plant A',
                'kode_mesin'      => 'bak_' . $key,
                'nama_mesin'      => $bak['nama'],
                'proses'          => 'annealing',
                'std_tipe'        => 'Lubricool AC',
                'std_supplier'    => 'METALUBE',
                'std_warna'       => 'Putih',
                'std_konsentrasi' => '0.5% - 1%',
                'std_ph'          => '6.5 - 7.5',
                'std_temp'        => '35°C - 40°C',
            ]);
        }

        // ==========================================
        // 2. DATA STANDAR AUTOWIRE (4 PENGECEKAN)
        // ==========================================
        for ($i = 1; $i <= 4; $i++) {
            // Standar Drawing Autowire
            EngCompoundStandard::create([
                'plant'           => 'Autowire',
                'kode_mesin'      => 'cek_' . $i,
                'nama_mesin'      => 'Pengecekan ' . $i,
                'proses'          => 'drawing',
                'std_tipe'        => 'WT-2050D',
                'std_supplier'    => 'HOUSIN',
                'std_warna'       => 'Hijau Putih',
                'std_konsentrasi' => '6% - 8%',
                'std_ph'          => '8 - 9',
                'std_temp'        => '35°C - 40°C',
            ]);

            // Standar Annealing Autowire
            EngCompoundStandard::create([
                'plant'           => 'Autowire',
                'kode_mesin'      => 'cek_' . $i,
                'nama_mesin'      => 'Pengecekan ' . $i,
                'proses'          => 'annealing',
                'std_tipe'        => 'B-600',
                'std_supplier'    => 'HOUSIN',
                'std_warna'       => 'Putih',
                'std_konsentrasi' => '0.5% - 1%',
                'std_ph'          => '6.5 - 7.5',
                'std_temp'        => '35°C - 40°C',
            ]);
        }
    }
}
