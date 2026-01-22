<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GeneralAffair\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $categories = [
            [
                'name'        => 'Kebersihan',
                'description' => 'Permintaan cleaning service, pembuangan sampah, toilet kotor, atau pembersihan area khusus.',
                'color'       => 'green',
                'status'      => 'active',
            ],
            [
                'name'        => 'Pemeliharaan',
                'description' => 'Jadwal maintenance berkala (AC, Lift, Genset, Taman) atau pengecekan rutin fasilitas.',
                'color'       => 'blue',
                'status'      => 'active',
            ],
            [
                'name'        => 'Perbaikan',
                'description' => 'Laporan kerusakan fasilitas seperti lampu mati, kran bocor, pintu rusak, atau ubin pecah.',
                'color'       => 'red',
                'status'      => 'active',
            ],
            [
                'name'        => 'Pembuatan Baru',
                'description' => 'Permintaan instalasi baru, pembuatan partisi, pemasangan jaringan, atau renovasi ruangan.',
                'color'       => 'purple',
                'status'      => 'active',
            ],
            [
                'name'        => 'Perizinan',
                'description' => 'Pengurusan dokumen izin kerja, surat jalan barang, atau legalitas operasional lainnya.',
                'color'       => 'yellow',
                'status'      => 'active',
            ],
            [
                'name'        => 'Reservasi',
                'description' => 'Booking ruang meeting, peminjaman kendaraan operasional, atau pemakaian aula.',
                'color'       => 'indigo',
                'status'      => 'active',
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name']],
                $cat
            );
        }
    }
}
