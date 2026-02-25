<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Sesuaikan jika model Anda ada di folder lain
use Illuminate\Support\Facades\Log;

class UpdatePhoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $file = database_path('seeders/nomor_telfon.csv');

        if (!file_exists($file)) {
            $this->command->error("File CSV tidak ditemukan di: {$file}");
            return;
        }

        // Buka file CSV
        $handle = fopen($file, "r");

        // Lewati baris pertama jika file Anda memiliki header (Judul Kolom)
        $isHeader = true;

        $countSuccess = 0;
        $countNotFound = 0;

        $this->command->info("Memulai proses update nomor telepon dengan auto-format...");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            // 1. AMBIL DATA MENTAH
            $nik = trim($data[0]);
            $phone = trim($data[2]);

            if (empty($nik) || empty($phone)) continue;

            // ==========================================
            // 2. FORMATTING NIK
            // ==========================================
            // Jika NIK terbaca hanya 3 digit (karena 0 di depannya hilang oleh Excel), tambahkan '0'
            if (strlen($nik) === 3) {
                $nik = '0' . $nik;
            }

            // ==========================================
            // 3. FORMATTING NOMOR TELEPON
            // ==========================================
            // Bersihkan dulu jika ada karakter aneh (spasi, tanda +, strip)
            $phone = preg_replace('/[^0-9]/', '', $phone);

            // Jika diawali dengan '62', potong '62'-nya dan ganti dengan '0'
            if (str_starts_with($phone, '62')) {
                $phone = '0' . substr($phone, 2);
            }

            // Jika depannya ternyata bukan '0' (misal di Excel tertulis 812345...), tambahkan '0' di depannya
            if (!str_starts_with($phone, '0')) {
                $phone = '0' . $phone;
            }

            // ==========================================
            // 4. PROSES UPDATE KE DATABASE
            // ==========================================
            $user = User::where('nik', $nik)->first();

            if ($user) {
                $user->update([
                    'no_hp' => $phone, // Sesuaikan dengan nama kolom di database Anda (no_hp atau phone)
                ]);
                $countSuccess++;
            } else {
                $this->command->warn("User dengan NIK {$nik} tidak ditemukan.");
                $countNotFound++;
            }
        }

        fclose($handle);

        $this->command->info("Selesai! Berhasil update: {$countSuccess} user. Tidak ditemukan: {$countNotFound} user.");
    }
}
