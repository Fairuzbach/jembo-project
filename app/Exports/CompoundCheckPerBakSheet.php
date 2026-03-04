<?php

namespace App\Exports;

use App\Models\Engineering\EngCompoundCheck;
use App\Models\Engineering\Machine;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class CompoundCheckPerBakSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $plantId;
    protected $bulan;
    protected $tahun;
    protected $machineId;
    protected $machineName;

    // Variabel untuk menampung data standar
    protected $stdDraw;
    protected $stdAnn;

    public function __construct($plantId, $bulan, $tahun, $machineId)
    {
        $this->plantId = $plantId;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->machineId = $machineId;

        if ($machineId) {
            $machine = \App\Models\Engineering\Machine::find($machineId);
            $rawName = $machine ? $machine->name : null;

            $this->machineName = substr(str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $rawName ?? "Mesin_$machineId"), 0, 30);

            // -------------------------------------------------------
            // PENCARIAN BERDASARKAN MACHINE_ID (PASTI AKURAT)
            // -------------------------------------------------------

            // Mencari standar drawing untuk ID mesin ini
            $this->stdDraw = DB::table('eng_compound_standards')
                ->where('machine_id', $this->machineId)
                ->where('proses', 'drawing')
                ->first();

            // Mencari standar annealing untuk ID mesin ini
            $this->stdAnn = DB::table('eng_compound_standards')
                ->where('machine_id', $this->machineId)
                ->where('proses', 'annealing')
                ->first();
        }
    }

    public function collection()
    {
        if (!$this->machineId) {
            return collect([]);
        }

        return EngCompoundCheck::where('plant_id', $this->plantId)
            ->where('machine_id', $this->machineId)
            ->whereMonth('tanggal_cek', $this->bulan)
            ->whereYear('tanggal_cek', $this->tahun)
            ->orderBy('tanggal_cek', 'asc')
            ->get();
    }

    public function headings(): array
    {
        // HEADER BERTINGKAT 3 BARIS (Agar Rapi dan Padat)
        return [
            [
                'Tanggal Cek',
                'DRAWING COMPOUND',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '', // 12 kolom untuk Drawing
                'ANNEALING COMPOUND',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '', // 12 kolom untuk Annealing
                'Diperiksa Oleh',
                'Keterangan'
            ],
            [
                '', // Bawahnya Tanggal
                'Type',
                '',
                'Supplier',
                '',
                'Warna',
                '',
                'Konsentrasi (%)',
                '',
                'pH',
                '',
                'Temp (°C)',
                '',
                'Type',
                '',
                'Supplier',
                '',
                'Warna',
                '',
                'Konsentrasi (%)',
                '',
                'pH',
                '',
                'Temp (°C)',
                '',
                '', // Bawahnya Diperiksa
                ''  // Bawahnya Keterangan
            ],
            [
                '', // Bawahnya Tanggal
                'Actual',
                'Standards',
                'Actual',
                'Standards',
                'Actual',
                'Standards',
                'Actual',
                'Standards',
                'Actual',
                'Standards',
                'Actual',
                'Standards', // Sub-Drawing
                'Actual',
                'Standards',
                'Actual',
                'Standards',
                'Actual',
                'Standards',
                'Actual',
                'Standards',
                'Actual',
                'Standards',
                'Actual',
                'Standards', // Sub-Annealing
                '', // Bawahnya Diperiksa
                ''  // Bawahnya Keterangan
            ]
        ];
    }

    public function map($row): array
    {
        return [
            Carbon::parse($row->tanggal_cek)->format('d-m-Y'),

            // --- Kolom Drawing (Aktual & Standar) ---
            $row->draw_type,
            $this->stdDraw->std_tipe ?? '-',
            $row->draw_supplier,
            $this->stdDraw->std_supplier ?? '-',
            $row->draw_warna,
            $this->stdDraw->std_warna ?? '-',
            $row->draw_konsentrasi,
            $this->stdDraw->std_konsentrasi ?? '-',
            $row->draw_ph,
            $this->stdDraw->std_ph ?? '-',
            $row->draw_temp,
            $this->stdDraw->std_temp ?? '-',

            // --- Kolom Annealing (Aktual & Standar) ---
            $row->ann_type,
            $this->stdAnn->std_tipe ?? '-',
            $row->ann_supplier,
            $this->stdAnn->std_supplier ?? '-',
            $row->ann_warna,
            $this->stdAnn->std_warna ?? '-',
            $row->ann_konsentrasi,
            $this->stdAnn->std_konsentrasi ?? '-',
            $row->ann_ph,
            $this->stdAnn->std_ph ?? '-',
            $row->ann_temp,
            $this->stdAnn->std_temp ?? '-',

            $row->diperiksa_oleh,
            $row->keterangan,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'AA';

        // 1. MERGE CELLS
        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells('B1:M1');
        $sheet->mergeCells('N1:Y1');
        $sheet->mergeCells('Z1:Z3');
        $sheet->mergeCells('AA1:AA3');

        $merges = [
            'B2:C2',
            'D2:E2',
            'F2:G2',
            'H2:I2',
            'J2:K2',
            'L2:M2',
            'N2:O2',
            'P2:Q2',
            'R2:S2',
            'T2:U2',
            'V2:W2',
            'X2:Y2',
        ];
        foreach ($merges as $merge) {
            $sheet->mergeCells($merge);
        }

        // 2. LEBAR KOLOM
        $sheet->getColumnDimension('A')->setWidth(10);
        foreach (range('B', 'Y') as $columnID) {
            $sheet->getColumnDimension($columnID)->setWidth(5.5);
        }
        $sheet->getColumnDimension('Z')->setWidth(12);
        $sheet->getColumnDimension('AA')->setWidth(15);

        // 3. STYLE GENERAL
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFB0BEC5'], // border abu-abu lembut
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'font' => ['size' => 8.5],
        ]);

        // 4. BARIS DATA (baris 4 ke bawah) — putih bersih
        $sheet->getStyle("A4:{$lastCol}{$lastRow}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFFFF');

        // 5. HEADER ROW 1 — Navy gelap, teks putih (judul grup besar)
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A5F'], // navy
            ],
            'font' => [
                'bold'  => true,
                'size'  => 8,
                'color' => ['argb' => 'FFFFFFFF'], // putih
            ],
        ]);

        // 6. HEADER ROW 2 — Biru medium (sub-group ACT/STD)
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2563EB'], // biru
            ],
            'font' => [
                'bold'  => true,
                'size'  => 8,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
        ]);

        // 7. HEADER ROW 3 — Biru muda (label kolom ACT / STD)
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDBEAFE'], // biru sangat muda
            ],
            'font' => [
                'bold'  => true,
                'size'  => 8,
                'color' => ['argb' => 'FF1E3A5F'], // navy agar kontras
            ],
        ]);

        // 8. KOLOM STD (C, E, G, ...) — kuning gading tipis agar beda dari ACT
        $stdCols = ['C', 'E', 'G', 'I', 'K', 'M', 'O', 'Q', 'S', 'U', 'W', 'Y'];
        foreach ($stdCols as $col) {
            $sheet->getStyle("{$col}4:{$col}{$lastRow}")
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFFDE7'); // kuning gading sangat tipis
        }

        // 9. KOLOM ACT (B, D, F, ...) — putih bersih (sudah dari step 4, eksplisit)
        $actCols = ['B', 'D', 'F', 'H', 'J', 'L', 'N', 'P', 'R', 'T', 'V', 'X'];
        foreach ($actCols as $col) {
            $sheet->getStyle("{$col}4:{$col}{$lastRow}")
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFFFFF');
        }

        // 10. KOLOM TANGGAL (A), PEMERIKSA (Z), KETERANGAN (AA) — abu-abu sangat terang
        foreach (['A', 'Z', 'AA'] as $col) {
            $sheet->getStyle("{$col}4:{$col}{$lastRow}")
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF8FAFC');
        }

        // 11. BORDER HEADER lebih tegas (outer border tebal)
        $sheet->getStyle("A1:{$lastCol}3")->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color'       => ['argb' => 'FF1E3A5F'],
                ],
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF93C5FD'],
                ],
            ],
        ]);

        $sheet->freezePane('B4');

        return [];
    }

    public function title(): string
    {
        return $this->machineName;
    }
}
