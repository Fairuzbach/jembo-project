<?php

namespace App\Exports;

use App\Models\Engineering\EngCompoundCheck;
use App\Exports\CompoundCheckPerBakSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CompoundCheckExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        $sheets = [];

        // 1. Cari mesin_id (Bak) apa saja yang ada datanya pada bulan & plant ini
        $machineIds = EngCompoundCheck::where('plant_id', $this->plantId)
            ->whereMonth('tanggal_cek', $this->bulan)
            ->whereYear('tanggal_cek', $this->tahun)
            ->select('machine_id')
            ->distinct()
            ->orderBy('machine_id', 'asc')
            ->pluck('machine_id');

        // 2. Jika tidak ada data sama sekali, buat 1 sheet kosong sebagai info
        if ($machineIds->isEmpty()) {
            return [new CompoundCheckPerBakSheet($this->plantId, $this->bulan, $this->tahun, null)];
        }

        // 3. Buat 1 Sheet untuk setiap Bak (Mesin)
        foreach ($machineIds as $machineId) {
            $sheets[] = new CompoundCheckPerBakSheet($this->plantId, $this->bulan, $this->tahun, $machineId);
        }

        return $sheets;
    }
}
