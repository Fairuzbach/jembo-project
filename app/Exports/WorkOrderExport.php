<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class WorkOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithDrawings
{
    protected $data;
    protected $collection;
    public function __construct($data)
    {
        $this->data = $data;
        $this->collection = $data->get();
    }

    public function collection()
    {
        return $this->data->get();
    }

    // 1. HEADER KOLOM (Total 14 Kolom)
    public function headings(): array
    {
        return [
            'ID TIKET',
            'PEMOHON',
            'DIVISI PELAPOR',
            'LOKASI',
            'DEPARTEMEN TUJUAN',
            'PARAMETER',
            'URAIAN PEKERJAAN',
            'STATUS',
            'PIC', // Kolom Baru
            'STATUS PERMINTAAN',
            'BOBOT PEKERJAAN',
            'TANGGAL DIBUAT',
            'TANGGAL TARGET',
            'TANGGAL SELESAI',
            'FOTO BEFORE',
            'FOTO AFTER',
        ];
    }

    // 2. MAPPING DATA
    public function map($ticket): array
    {
        $user = $ticket->user;
        $namaPemohon = $user ? $user->name : ($ticket->requester_name ?? '-');
        $divisiPemohon = $user ? ($user->divisi ?? '-') : '-';

        $tglTarget  = $ticket->target_completion_date ? Carbon::parse($ticket->target_completion_date)->locale('id')->isoFormat('DD MMMM YYYY') : '-';
        $tglSelesai = $ticket->actual_completion_date ? Carbon::parse($ticket->actual_completion_date)->locale('id')->isoFormat('DD MMMM YYYY') : '-';
        $tglDibuat  = $ticket->created_at ? Carbon::parse($ticket->created_at)->locale('id')->isoFormat('DD MMMM YYYY') : '-';

        if ($ticket->plantInfo) {
            $namaPlant = $ticket->plantInfo->name;
        } else {
            $namaPlant = $ticket->plant ? 'Unknown Plant (ID: ' . $ticket->plant . ')' : '-';
        }

        return [
            $ticket->ticket_num,
            $namaPemohon,
            $divisiPemohon,
            $namaPlant,
            $ticket->department,
            $ticket->parameter_permintaan ?? $ticket->category,
            $ticket->description ?? '-',
            strtoupper(str_replace('_', ' ', $ticket->status)),
            $ticket->processed_by_name ?? '-', // DATA PIC / TEKNISI
            $ticket->status_permintaan,
            $ticket->category,
            $tglDibuat,
            $tglTarget,
            $tglSelesai,
            '',
            '',
        ];
    }

    public function drawings()
    {
        $drawings = [];
        // Daftar ekstensi yang diizinkan oleh Excel
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

        foreach ($this->collection as $index => $ticket) {
            $rowIndex = $index + 2;

            // ==========================================================
            // 1. FOTO BEFORE (Laporan)
            // ==========================================================
            if ($ticket->photo_path) {
                $path1 = public_path('storage/' . $ticket->photo_path);

                // Ambil ekstensi file (misal: jpg, pdf, png)
                $ext1 = strtolower(pathinfo($path1, PATHINFO_EXTENSION));

                // CEK: File harus ADA di folder DAN formatnya harus GAMBAR
                if (file_exists($path1) && in_array($ext1, $validExtensions)) {
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setName('Foto Before');
                    $drawing->setDescription('Foto Before');
                    $drawing->setPath($path1);
                    $drawing->setHeight(150);
                    $drawing->setCoordinates('O' . $rowIndex);
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(10);
                    $drawings[] = $drawing;
                }
            }

            // ==========================================================
            // 2. FOTO AFTER (Penyelesaian)
            // ==========================================================
            if ($ticket->photo_completed_path) {
                $path2 = public_path('storage/' . $ticket->photo_completed_path);

                // Ambil ekstensi file
                $ext2 = strtolower(pathinfo($path2, PATHINFO_EXTENSION));

                // CEK: File harus ADA di folder DAN formatnya harus GAMBAR
                if (file_exists($path2) && in_array($ext2, $validExtensions)) {
                    $drawing2 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing2->setName('Foto After');
                    $drawing2->setDescription('Foto After');
                    $drawing2->setPath($path2);
                    $drawing2->setHeight(150);
                    $drawing2->setCoordinates('P' . $rowIndex);
                    $drawing2->setOffsetX(10);
                    $drawing2->setOffsetY(10);
                    $drawings[] = $drawing2;
                }
            }
        }

        return $drawings;
    }

    // 3. REGISTER EVENTS
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // Style Dasar
                $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);
                $sheet->freezePane('A2');
                $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setWrapText(true);

                // [PENTING] Set Tinggi Baris agar Gambar Muat
                // Loop dari baris 2 sampai akhir
                for ($i = 2; $i <= $lastRow; $i++) {
                    // Set tinggi baris jadi 90 (sedikit lebih besar dari tinggi gambar 80)
                    $sheet->getRowDimension($i)->setRowHeight(160);
                }

                // Set Lebar Kolom Foto biar rapi
                $sheet->getColumnDimension('O')->setWidth(70);
                $sheet->getColumnDimension('P')->setWidth(70);
            },
        ];
    }

    // 4. STYLING
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFF00'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'A1:' . $lastColumn . $lastRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
