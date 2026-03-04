<?php

namespace App\Exports;

use App\Models\Engineering\EngCompoundCheck;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompoundCheckExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $plantId;
    protected $bulan;
    protected $tahun;

    public function __construct($plantId, $bulan, $tahun)
    {
        $this->plantId = $plantId;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function collection()
    {
        return EngCompoundCheck::with(['machine']) // pastikan relasi 'machine' sudah benar di model
            ->where('plant_id', $this->plantId)
            ->whereMonth('tanggal_cek', $this->bulan)
            ->whereYear('tanggal_cek', $this->tahun)
            ->orderBy('tanggal_cek', 'asc')
            ->orderBy('machine_id', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal Cek',
            'Nama Mesin / Bak',
            'Drawing Type',
            'Drawing Supplier',
            'Drawing Warna',
            'Drawing Konsentrasi (%)',
            'Drawing pH',
            'Drawing Temp (°C)',
            'Annealing Type',
            'Annealing Supplier',
            'Annealing Warna',
            'Annealing Konsentrasi (%)',
            'Annealing pH',
            'Annealing Temp (°C)',
            'Diperiksa Oleh',
            'Keterangan',
        ];
    }

    public function map($row): array
    {
        return [
            \Carbon\Carbon::parse($row->tanggal_cek)->format('d-m-Y'),
            $row->machine->name ?? 'Unknown Machine', // Sesuaikan jika nama kolom mesin berbeda
            $row->draw_type,
            $row->draw_supplier,
            $row->draw_warna,
            $row->draw_konsentrasi,
            $row->draw_ph,
            $row->draw_temp,
            $row->ann_type,
            $row->ann_supplier,
            $row->ann_warna,
            $row->ann_konsentrasi,
            $row->ann_ph,
            $row->ann_temp,
            $row->diperiksa_oleh,
            $row->keterangan,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1    => ['font' => ['bold' => true]],
        ];
    }
}
