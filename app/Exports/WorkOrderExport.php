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

class WorkOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
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
        ];
    }

    // 3. REGISTER EVENTS
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);
                $sheet->freezePane('A2');

                // Wrap Text untuk Uraian Pekerjaan (Kolom G)
                $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setWrapText(true);
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
