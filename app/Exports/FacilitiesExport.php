<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery; // Ganti FromCollection jadi FromQuery
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FacilitiesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $query;

    // Terima Query Builder dari Controller
    public function __construct($query)
    {
        $this->query = $query;
    }

    // Method Wajib untuk FromQuery
    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Ticket No',
            'Date',
            'Requester',
            'Plant',
            'Machine',
            'Category',
            'Description',
            'Status',
            'Technicians (PIC)', // Kolom baru
        ];
    }

    public function map($wo): array
    {

        $techNames = $wo->technicians->pluck('name')->implode(', ');

        return [
            $wo->ticket_num,
            $wo->report_date ? \Carbon\Carbon::parse($wo->report_date)->format('d-m-Y') : '-',
            $wo->requester_name,
            $wo->plant,
            $wo->machine->name ?? '-',
            $wo->category,
            $wo->description,
            strtoupper(str_replace('_', ' ', $wo->status)),
            $techNames ?: '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // 1. Define Style Array
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $headerStyle = array_merge($borderStyle, [
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A5F'], // Warna Navy Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $dataStyle = array_merge($borderStyle, [
            'font' => ['size' => 11],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        // 2. Apply Header Style
        $sheet->getStyle('1:1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // 3. Apply Data Styles (Zebra Striping)
        $highestRow = $sheet->getHighestRow();

        // Loop dari baris 2 sampai baris terakhir
        if ($highestRow > 1) {
            for ($row = 2; $row <= $highestRow; $row++) {
                // Default style
                $currentStyle = $dataStyle;

                // Jika baris genap, kasih warna abu-abu muda
                if ($row % 2 == 0) {
                    $currentStyle['fill'] = [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ];
                }

                $sheet->getStyle('A' . $row . ':' . 'I' . $row)->applyFromArray($currentStyle);
            }
        }

        // 4. Atur Lebar Kolom Manual (Override ShouldAutoSize agar lebih rapi)
        // Description biasanya panjang, jadi kita limit.
        $sheet->getColumnDimension('A')->setWidth(15); // Ticket
        $sheet->getColumnDimension('B')->setWidth(15); // Date
        $sheet->getColumnDimension('C')->setWidth(20); // Requester
        $sheet->getColumnDimension('D')->setWidth(20); // Plant
        $sheet->getColumnDimension('E')->setWidth(20); // Machine
        $sheet->getColumnDimension('F')->setWidth(15); // Category
        $sheet->getColumnDimension('G')->setWidth(50); // Description (Lebar)
        $sheet->getColumnDimension('H')->setWidth(20); // Status
        $sheet->getColumnDimension('I')->setWidth(25); // Tech
        return [];
    }

    public function title(): string
    {
        return 'Facilities Work Orders';
    }
}
