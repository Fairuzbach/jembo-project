<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ImportEmployees extends Command
{
    protected $signature = 'employee:import {file}';
    protected $description = 'Import data karyawan dengan Divisi UPPERCASE & Auto-Role';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("❌ File tidak ditemukan di: $filePath");
            return;
        }

        // Mapping Divisi Standar (Singkatan ke Nama Baku UPPERCASE)
        $divisiMap = [
            'INFORMATION TECHNOLOGY'      => 'IT',
            'PROCESS ENGINEERING'         => 'PE',
            'QUALITY ASSURANCE & R D'     => 'QR',
            'SALES SUPPORT'               => 'SS',
            'COMMERCIAL & SUPPLY CHAIN'   => 'COMMERCIAL & SUPPLY CHAIN',
            'HUMAN CAPITAL'               => 'HC',
            'PRODUCTION PLANNING'         => 'PP',
            'GENERAL AFFAIR'              => 'GENERAL AFFAIR', // Fix Uppercase
            'GA'                          => 'GENERAL AFFAIR',
            'FACILITY'                    => 'FACILITY',       // Fix Uppercase
            'MAINTENANCE'                 => 'MAINTENANCE',    // Fix Uppercase
            'MARKETING'                   => 'MARKETING',      // Fix Uppercase
            'ENGINEERING'                 => 'ENGINEERING',
            'PROCUREMENT'                 => 'SC',
        ];

        $this->info("🚀 Memulai proses import (Mode: UPPERCASE)...");

        $reader = SimpleExcelReader::create($filePath);
        $this->output->progressStart(100);

        $masuk = 0;

        $reader->getRows()->each(function (array $row) use ($divisiMap, &$masuk) {

            // ==========================================================
            // A. LOGIKA PENENTUAN DIVISI (UPPERCASE MODE)
            // ==========================================================

            // 1. Ambil Raw Data (Pastikan Upper)
            $rawDivisi  = strtoupper(trim($row['Organization']));
            $rawJabatan = strtoupper(trim($row['Job Position'] ?? ''));

            $fixedDivisi = $rawDivisi; // Default sudah UPPERCASE

            // 2. [LOGIC BARU] Cek Spesifik Autowire & CCV (UPPERCASE)
            if (str_contains($rawJabatan, 'AUTOWIRE') || str_contains($rawDivisi, 'AUTOWIRE') || (str_contains($rawJabatan, 'AUTO WIRE') || str_contains($rawDivisi, 'AUTO WIRE'))) {
                $fixedDivisi = 'PLANT A - AUTOWIRE';
            } elseif (str_contains($rawJabatan, 'CCV') || str_contains($rawDivisi, 'CCV')) {
                $fixedDivisi = 'PLANT D - CCV';
            } else {
                // 3. Logic Standar
                // Cek Mapping (Misal: 'GA' -> 'GENERAL AFFAIR')
                if (isset($divisiMap[$rawDivisi])) {
                    $fixedDivisi = $divisiMap[$rawDivisi];
                } else {
                    // Jika tidak ada di map, gunakan nama aslinya (PLANT A, PLANT B, dll)
                    // Pastikan tetap UPPERCASE
                    $fixedDivisi = $rawDivisi;

                    // Normalisasi Singkatan Manual (Jaga-jaga)
                    if ($fixedDivisi == 'PE') $fixedDivisi = 'PE'; // Tetap PE
                    // Tambahkan normalisasi lain jika nama plant aneh-aneh
                }
            }

            // ==========================================================
            // B. FIX NIK
            // ==========================================================
            $nik = trim((string) $row['Employee ID']);
            if (ctype_digit($nik)) {
                if (strlen($nik) < 4) {
                    $nik = str_pad($nik, 4, '0', STR_PAD_LEFT);
                }
            }

            // ==========================================================
            // C. LOGIKA AUTO-ROLE
            // ==========================================================
            $roleOtomatis = 'user'; // Default

            // Cek Kata Kunci Boss
            $isBoss = str_contains($rawJabatan, 'MANAGER') ||
                str_contains($rawJabatan, 'DIRECTOR') ||
                str_contains($rawJabatan, 'SUPERVISOR') ||
                str_contains($rawJabatan, 'HEAD') ||
                str_contains($rawJabatan, 'MGR') ||
                str_contains($rawJabatan, 'SPV');

            if ($isBoss) {
                // Gunakan match dengan kunci UPPERCASE
                $roleOtomatis = match ($fixedDivisi) {
                    'PRESIDENT DIRECTOR' => 'Super Admin',

                    // Admin Dept (UPPERCASE KEYS)
                    'GENERAL AFFAIR' => 'ga.admin',
                    'SALES 1' => 'sales1.admin',
                    'SALES 2' => 'sales2.admin',
                    'ACCOUNTING' => 'accounting.admin',
                    'INTERNAL CONTROL' => 'ic.admin',
                    'IT'             => 'it.admin',
                    'PE'             => 'eng.admin',
                    'FACILITY'       => 'fh.admin',      // <-- Ini pasti cocok sekarang
                    'MAINTENANCE'    => 'mt.admin',
                    'MARKETING'      => 'marketing.admin',
                    'QR'             => 'qr.admin',
                    'SS'             => 'ss.admin',
                    'PROCUREMENT'    => 'sc.admin',
                    'HC'             => 'hc.admin',
                    'PP'             => 'pp.admin',

                    // Plant General (Admin Role)
                    'PLANT A' => 'lv.admin',
                    'PLANT B' => 'mv.admin',
                    'PLANT C' => 'lv.admin',
                    'PLANT D' => 'mv.admin', // Plant D Murni
                    'PLANT E' => 'fo.admin',

                    // Plant Spesifik -> User Biasa
                    'PLANT A - AUTOWIRE' => 'autowire.admin',
                    'PLANT D - CCV'      => 'ccv.admin',

                    default => 'user',
                };
            }
            $rawHp = (string) ($row['Mobile Phone'] ?? $row['Phone'] ?? '');

            // 2. Hapus spasi, strip (-), atau karakter non-angka lainnya (kecuali +)
            // Contoh: "0812-3456" jadi "08123456"
            $cleanHp = preg_replace('/[^0-9+]/', '', $rawHp);

            // 3. Ubah Format
            if (str_starts_with($cleanHp, '+62')) {
                // Jika diawali +62, hapus 3 karakter awal (+62), ganti dengan 0
                $cleanHp = '0' . substr($cleanHp, 3);
            } elseif (str_starts_with($cleanHp, '62')) {
                // Jika diawali 62 (tanpa plus), hapus 2 karakter awal (62), ganti dengan 0
                $cleanHp = '0' . substr($cleanHp, 2);
            }

            // Optional: Validasi panjang minimal (misal min 10 digit)
            if (strlen($cleanHp) < 9) {
                $cleanHp = null; // Anggap tidak valid jika terlalu pendek
            }
            Role::firstOrCreate(['name' => $roleOtomatis, 'guard_name' => 'web']);

            // ==========================================================
            // D. SIMPAN KE DATABASE
            // ==========================================================
            $user = User::updateOrCreate(
                ['nik' => $nik],
                [
                    'name'         => $row['Full Name'],
                    'email'        => $nik . '@jembo.com',
                    'divisi'       => $fixedDivisi, // <--- Hasilnya pasti UPPERCASE (misal: FACILITY)
                    'jabatan'      => $row['Job Position'] ?? null,
                    'no_hp' => $cleanHp,
                    'password'     => Hash::make('jembopass'),
                    'job_level'    => $row['Job Level'] ?? null,
                    'role'         => $roleOtomatis,
                    'is_active'    => true,
                ]
            );

            $user->syncRoles($roleOtomatis);

            $masuk++;
            $this->output->progressAdvance();
        });

        $this->output->progressFinish();
        $this->info("------------------------------------------------");
        $this->info("✅ BERHASIL DISIMPAN : $masuk Karyawan");
        $this->info("   Catatan: Semua nama divisi disimpan dalam HURUF BESAR.");
        $this->info("------------------------------------------------");
    }
}
