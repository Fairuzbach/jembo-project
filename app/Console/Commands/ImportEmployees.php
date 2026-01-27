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
    protected $description = 'Import data karyawan dengan Fix NIK, Email Dummy & Auto-Role Jabatan';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("❌ File tidak ditemukan di: $filePath");
            return;
        }

        // 1. KAMUS MAPPING DIVISI
        $divisiMap = [
            'INFORMATION TECHNOLOGY'      => 'IT',
            'PROCESS ENGINEERING'         => 'PE',
            'QUALITY ASSURANCE & R D'     => 'QR',
            'SALES SUPPORT'               => 'SS',
            'COMMERCIAL & SUPPLY CHAIN'   => 'SC',
            'HUMAN CAPITAL'               => 'HC',
            'FACILITY'                    => 'FH'
        ];

        // 2. DAFTAR TARGET DIVISI (Gunakan Singkatan)
        $targetDivisi = [
            'PRESIDENT DIRECTOR',
            'GENERAL AFFAIR',
            'IT',
            'PE',
            'FH',
            'MAINTENANCE',
            'MARKETING',
            'PLANT A',
            'PLANT B',
            'PLANT C',
            'PLANT D',
            'PLANT E',
            'QR',
            'SALES 1',
            'SALES 2',
            'SS',
            'SC',
            'HC',
        ];

        $this->info("🚀 Memulai proses import...");

        $reader = SimpleExcelReader::create($filePath);
        $this->output->progressStart(100);

        $masuk = 0;
        $skip = 0;

        $reader->getRows()->each(function (array $row) use ($targetDivisi, $divisiMap, &$masuk, &$skip) {

            // A. LOGIKA DIVISI
            $rawDivisi = strtoupper(trim($row['Organization']));
            $fixedDivisi = $divisiMap[$rawDivisi] ?? $rawDivisi;

            if (!in_array($fixedDivisi, $targetDivisi)) {
                $skip++;
                return;
            }

            // B. FIX NIK
            $nik = trim((string) $row['Employee ID']);
            if (ctype_digit($nik)) {
                if (strlen($nik) < 4) {
                    $nik = str_pad($nik, 4, '0', STR_PAD_LEFT);
                }
            }

            // C. LOGIKA AUTO-ROLE (JABATAN OTOMATIS)
            // Default role adalah 'user'
            $roleOtomatis = 'user';

            // Ambil jabatan (uppercase biar mudah dicek)
            $jabatanUpper = strtoupper($row['Job Position'] ?? '');

            // Cek Kata Kunci Boss
            $isBoss = str_contains($jabatanUpper, 'MANAGER') ||
                str_contains($jabatanUpper, 'DIRECTOR') || str_contains($jabatanUpper, 'SUPERVISOR');

            if ($isBoss) {
                // Jika dia Boss, cek divisinya untuk menentukan role admin
                $roleOtomatis = match ($fixedDivisi) {
                    'PRESIDENT DIRECTOR' => 'Super Admin',
                    'GENERAL AFFAIR' => 'ga.admin',
                    'IT' => 'it.admin',
                    'PE' => 'eng.admin',
                    'FH' => 'fh.admin',
                    'MAINTENANCE' => 'mt.admin',
                    'MARKETING' => 'marketing.admin',
                    'PLANT A' => 'lv.admin',
                    'PLANT B' => 'mv.admin',
                    'PLANT C' => 'lv.admin',
                    'PLANT D' => 'mv.admin',
                    'PLANT E' => 'fo.admin',
                    'QR' => 'qr.admin',
                    'SALES 1' => 'sales1.admin',
                    'SALES 2' => 'sales2.admin',
                    'SS' => 'ss.admin',
                    'SC' => 'sc.admin',
                    'HC' => 'hc.admin',
                    default          => 'user',         // Boss divisi lain (Sales/Plant) tetap user biasa
                };
            }
            Role::firstOrCreate(['name' => $roleOtomatis, 'guard_name' => 'web']);
            // D. SIMPAN KE DATABASE
            $user = User::updateOrCreate(
                ['nik' => $nik],
                [
                    'name'         => $row['Full Name'],
                    'email'        => $nik . '@jembo.com', // Email Dummy dari NIK
                    'divisi'       => $fixedDivisi,
                    'jabatan'      => $row['Job Position'] ?? null,
                    'password'     => Hash::make('jembopass'),
                    'job_level'   => $row['Job Level'] ?? null,

                    // PENTING: Gunakan variabel roleOtomatis, JANGAN hardcode 'user'
                    'role'         => $roleOtomatis,

                    'is_active'    => true,
                ]
            );

            $masuk++;
            $user->syncRoles($roleOtomatis);
            $this->output->progressAdvance();
        });

        $this->output->progressFinish();
        $this->info("------------------------------------------------");
        $this->info("✅ BERHASIL DISIMPAN : $masuk Karyawan");
        $this->info("------------------------------------------------");
    }
}
