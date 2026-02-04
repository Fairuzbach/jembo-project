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
            'INFORMATION TECHNOLOGY'      => 'INFORMATION TECHNOLOGY',
            'PROCESS ENGINEERING'         => 'PROCESS ENGINEERING',
            'QUALITY ASSURANCE & R D'     => 'QUALITY ASSURANCE',
            'SALES SUPPORT'               => 'SALES SUPPORT',
            'COMMERCIAL & SUPPLY CHAIN'   => 'COMMERCIAL & SUPPLY CHAIN',
            'HUMAN CAPITAL'               => 'HUMAN CAPITAL',
            'PRODUCTION PLANNING'         => 'PRODUCTION PLANNING',
            'GENERAL AFFAIR'              => 'GENERAL AFFAIR',
            'GA'                          => 'GENERAL AFFAIR',
            'FACILITY'                    => 'FACILITY',
            'MAINTENANCE'                 => 'MAINTENANCE',
            'MARKETING'                   => 'MARKETING',
            'ENGINEERING'                 => 'ENGINEERING',
            'PROCUREMENT'                 => 'SUPPLY CHAIN',
            'RESEARCH & DEVELOPMENT' => 'RESEARCH & DEVELOPMENT',
            'FINANCE' => 'FINANCE',
        ];

        $this->info("🚀 Memulai proses import (Supervisor+, Admin, & ALL General Affair)...");

        $reader = SimpleExcelReader::create($filePath);
        $this->output->progressStart(100);

        $masuk = 0;
        $dilewati = 0;

        $reader->getRows()->each(function (array $row) use ($divisiMap, &$masuk, &$dilewati) {

            // 1. Ambil Data Raw
            $rawJabatan = strtoupper(trim($row['Job Position'] ?? ''));
            $rawDivisi  = strtoupper(trim($row['Organization'] ?? ''));

            // 2. LOGIKA FILTER IMPORT

            // KONDISI A: Jabatan "Petinggi" ATAU "Admin" (Di departemen manapun)
            $isTargetJabatan = str_contains($rawJabatan, 'MANAGER') ||
                str_contains($rawJabatan, 'DIRECTOR') ||
                str_contains($rawJabatan, 'SUPERVISOR') ||
                str_contains($rawJabatan, 'HEAD') ||
                str_contains($rawJabatan, 'MGR') ||
                str_contains($rawJabatan, 'SPV') ||
                str_contains($rawJabatan, 'FOREMAN') ||
                str_contains($rawJabatan, 'ADMIN'); // <--- Jabatan ADMIN masuk sini

            // KONDISI B: Divisi General Affair (Masuk Semua, jabatan apapun)
            $isGeneralAffair = str_contains($rawDivisi, 'GENERAL AFFAIR') ||
                $rawDivisi === 'GA';

            // JIKA (Bukan Target Jabatan) DAN (Bukan Orang GA) => SKIP
            if (!$isTargetJabatan && !$isGeneralAffair) {
                $dilewati++;
                return;
            }

            // --- PROSES SIMPAN DATA ---

            // A. Normalisasi Divisi
            $fixedDivisi = $rawDivisi;

            if (str_contains($rawJabatan, 'AUTOWIRE') || str_contains($rawDivisi, 'AUTOWIRE') || (str_contains($rawJabatan, 'AUTO WIRE') || str_contains($rawDivisi, 'AUTO WIRE'))) {
                $fixedDivisi = 'PLANT A - AUTOWIRE';
            } elseif (str_contains($rawJabatan, 'CCV') || str_contains($rawDivisi, 'CCV')) {
                $fixedDivisi = 'PLANT D - CCV';
            } else {
                if (isset($divisiMap[$rawDivisi])) {
                    $fixedDivisi = $divisiMap[$rawDivisi];
                } else {
                    $fixedDivisi = $rawDivisi;
                    if ($fixedDivisi == 'PE') $fixedDivisi = 'PE';
                }
            }

            // B. Fix NIK
            $nik = trim((string) $row['Employee ID']);
            if (ctype_digit($nik)) {
                if (strlen($nik) < 4) {
                    $nik = str_pad($nik, 4, '0', STR_PAD_LEFT);
                }
            }

            // C. Mapping Role
            // Logic: Divisi menentukan Role Admin-nya.
            // PENTING: Karena Staff GA lolos filter, mereka akan dapat role 'ga.admin' di sini.

            $roleOtomatis = match ($fixedDivisi) {
                'PRESIDENT DIRECTOR' => 'Super Admin',
                'GENERAL AFFAIR' => 'user', // Semua orang GA jadi admin GA
                'SALES 1' => 'user',
                'SALES 2' => 'user',
                'ACCOUNTING' => 'user',
                'INTERNAL CONTROL' => 'user',
                'INFORMATION TECHNOLOGY'             => 'user',
                'PROCESS ENGINEERING'             => 'user',
                'FACILITY'       => 'user',
                'MAINTENANCE'    => 'user',
                'MARKETING'      => 'user',
                'QUALITY ASSURANCE & R D'             => 'user',
                'SALES SUPPORT'             => 'user',
                'PROCUREMENT'    => 'user',
                'HUMAN CAPITAL'             => 'user',
                'PRODUCTION PLANNING'             => 'user',
                'RESEARCH & DEVELOPMENT'             => 'user',
                'FINANCE'             => 'user',
                'PLANT A' => 'user',
                'PLANT B' => 'user',
                'PLANT C' => 'user',
                'PLANT D' => 'user',
                'PLANT E' => 'user',
                'PLANT A - AUTOWIRE' => 'user',
                'PLANT D - CCV'      => 'user',
                default => 'user',
            };

            // D. Fix No HP
            $rawHp = (string) ($row['Mobile Phone'] ?? $row['Phone'] ?? '');
            $cleanHp = preg_replace('/[^0-9+]/', '', $rawHp);
            if (str_starts_with($cleanHp, '+62')) {
                $cleanHp = '0' . substr($cleanHp, 3);
            } elseif (str_starts_with($cleanHp, '62')) {
                $cleanHp = '0' . substr($cleanHp, 2);
            }
            if (strlen($cleanHp) < 9) {
                $cleanHp = null;
            }

            // E. Create Role & User
            Role::firstOrCreate(['name' => $roleOtomatis, 'guard_name' => 'web']);

            $user = User::updateOrCreate(
                ['nik' => $nik],
                [
                    'name'         => $row['Full Name'],
                    'email'        => $nik . '@jembo.com',
                    'divisi'       => $fixedDivisi,
                    'jabatan'      => $row['Job Position'] ?? null,
                    'no_hp'        => $cleanHp,
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
        $this->info("✅ IMPORT SELESAI : $masuk Karyawan");
        $this->info("   Criteria: (Jabatan 'Admin'/'Boss') ATAU (Divisi 'GA')");
        $this->info("⏩ DILEWATI : $dilewati Data");
        $this->info("------------------------------------------------");
    }
}
