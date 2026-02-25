<?php

namespace App\Services\Engineering;

class CompoundCheckService
{
    // Mapping ID Mesin Plant A agar tidak diulang-ulang di Store dan Update
    public function getPlantAMachineMap()
    {
        return [
            'bak_1' => 1,
            'bak_2' => 3,
            'bak_3' => 52,
            'bak_4' => 53,
            'bak_5' => 54,
            'bak_6' => 2,
        ];
    }

    // Fungsi cerdas untuk mengatur format persen dan suhu
    public function formatValue($value, $unit)
    {
        if (empty($value)) return null;

        if ($unit === '%') {
            return str_contains($value, '%') ? $value : $value . '%';
        }

        if ($unit === 'C') {
            return str_contains($value, 'C') ? $value : $value . '°C';
        }

        return $value;
    }

    // Cek apakah ada inputan data teknis
    public function hasInput($data)
    {
        return collect($data)->only([
            'draw_type',
            'draw_supplier',
            'draw_warna',
            'draw_konsentrasi',
            'draw_ph',
            'draw_temp',
            'ann_type',
            'ann_supplier',
            'ann_warna',
            'ann_konsentrasi',
            'ann_ph',
            'ann_temp'
        ])->filter(fn($val) => $val !== null && $val !== '')->isNotEmpty();
    }
}
