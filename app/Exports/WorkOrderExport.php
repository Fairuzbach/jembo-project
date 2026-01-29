<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

use Carbon\Carbon;

class WorkOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;


    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->get();
    }

    // 2. HEADER KOLOM
    public function headings(): array
    {
        return [
            'ID TIKET',
            'PEMOHON',
            'DIVISI PELAPOR',
            'LOKASI',
            'DEPARTEMEN',
            'PARAMETER',
            'STATUS',
            'STATUS PERMINTAAN',
            'BOBOT PEKERJAAN',
            'TANGGAL DIBUAT',
            'TANGGAL TARGET',
            'TANGGAL SELESAI',
        ];
    }


    public function map($ticket): array
    {
        // Handle User
        $user = $ticket->user;
        $namaPemohon = $user ? $user->name : ($ticket->requester_name ?? '-');
        $divisiPemohon = $user ? ($user->divisi ?? '-') : '-';

        // Format Tanggal Indonesia
        $tglTarget  = $ticket->target_completion_date ? Carbon::parse($ticket->target_completion_date)->locale('id')->isoFormat('DD MMMM YYYY') : '-';
        $tglSelesai = $ticket->actual_completion_date ? Carbon::parse($ticket->actual_completion_date)->locale('id')->isoFormat('DD MMMM YYYY') : '-';
        $tglDibuat  = $ticket->created_at ? Carbon::parse($ticket->created_at)->locale('id')->isoFormat('DD MMMM YYYY') : '-';
        $namaPlant = $ticket->plantInfo->name ?? $ticket->plant ?? '-';

        if ($ticket->plantInfo) {
            // Skenario 1: Relasi Ketemu (Normal)
            $namaPlant = $ticket->plantInfo->name;
        } else {
            // Skenario 2: Relasi NULL (Data Master hilang/terhapus)
            // Kita tampilkan ID aslinya biar ketahuan
            $namaPlant = 'Unknown Plant (ID: ' . ($ticket->plant ?? 'Kosong') . ')';
        }
        return [
            $ticket->ticket_num,
            $namaPemohon,
            $divisiPemohon,
            $namaPlant,
            $ticket->department,
            $ticket->parameter_permintaan ?? $ticket->category,
            strtoupper(str_replace('_', ' ', $ticket->status)), // Status Uppercase
            $ticket->status_permintaan,
            $ticket->category,
            $tglDibuat,
            $tglTarget,
            $tglSelesai,
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                // 1. Ambil Objek Worksheet Asli
                $sheet = $event->sheet->getDelegate();

                // 2. Cari Huruf Kolom Terakhir (Misal: 'K')
                $lastColumn = $sheet->getHighestColumn();

                // 3. Cari Nomor Baris Terakhir (Misal: 50)
                $lastRow = $sheet->getHighestRow();

                // 4. Buat String Range (Misal: "A1:K50")
                // Penting: Pastikan range mencakup Header (A1) sampai data terakhir
                $fullRange = 'A1:' . $lastColumn . $lastRow;

                // 5. Terapkan AutoFilter pada Range tersebut
                $sheet->setAutoFilter($fullRange);

                // Opsional: Freeze Pane (Bekukan Baris Header agar tetap terlihat saat scroll)
                $sheet->freezePane('A2');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        return [
            // Style Header (Baris 1)
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => '000000']], // Teks Hitam Bold
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFF00'], // Background Kuning 
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],


            'A1:K' . $lastRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
        ];
    }
}
