<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\SimpleExcel\SimpleExcelReader;

class UpdatePhoneEmployees extends Command
{
    // Nama command yang akan diketik di terminal
    protected $signature = 'employee:update-phone {file}';

    protected $description = 'Update No HP karyawan berdasarkan NIK dari file Excel/CSV';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("❌ File tidak ditemukan di: $filePath");
            return;
        }

        $this->info("🚀 Memulai proses update No HP...");

        $reader = SimpleExcelReader::create($filePath);
        $this->output->progressStart(100);

        $updated = 0;
        $notFound = 0;

        $reader->getRows()->each(function (array $row) use (&$updated, &$notFound) {

            // 1. AMBIL NIK (Kunci Pencarian)
            // Pastikan nama kolom di CSV sesuai (misal: 'Employee ID' atau 'NIK')
            $rawNik = trim((string) ($row['Employee ID'] ?? $row['NIK'] ?? ''));

            // FIX: Tambahkan padding 0 di depan jika excel menghilangkannya (123 -> 0123)
            if (ctype_digit($rawNik) && strlen($rawNik) < 4) {
                $rawNik = str_pad($rawNik, 4, '0', STR_PAD_LEFT);
            }

            // 2. AMBIL NO HP & BERSIHKAN
            // Sesuaikan nama kolom di CSV Anda (misal: 'Mobile Phone', 'Phone', 'No HP')
            $rawHp = (string) ($row['Mobile Phone'] ?? $row['Phone'] ?? '');

            if (!empty($rawHp)) {
                // A. Hapus karakter aneh (spasi, strip, kurung), sisakan angka dan +
                $cleanHp = preg_replace('/[^0-9+]/', '', $rawHp);

                // B. Logika +62 menjadi 0
                if (str_starts_with($cleanHp, '+62')) {
                    $cleanHp = '0' . substr($cleanHp, 3);
                } elseif (str_starts_with($cleanHp, '62')) {
                    $cleanHp = '0' . substr($cleanHp, 2);
                }

                // 3. CARI USER DI DATABASE BERDASARKAN NIK
                $user = User::where('nik', $rawNik)->first();

                if ($user) {
                    // Update hanya kolom no_hp
                    $user->update(['no_hp' => $cleanHp]);
                    $updated++;
                } else {
                    $notFound++;
                    // Optional: Tampilkan NIK yang tidak ketemu
                    $this->warn("NIK tidak ditemukan: $rawNik");
                }
            }

            $this->output->progressAdvance();
        });

        $this->output->progressFinish();
        $this->info("------------------------------------------------");
        $this->info("✅ SELESAI!");
        $this->info("📞 Terupdate     : $updated User");
        $this->info("❓ NIK Tidak Ada : $notFound User (Dilewati)");
        $this->info("------------------------------------------------");
    }
}
