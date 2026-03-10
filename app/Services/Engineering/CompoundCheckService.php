<?php

namespace App\Services\Engineering;

use App\Models\Engineering\EngCompoundCheck;
use Carbon\Carbon;

class CompoundCheckService
{
    // 1. Mapping ID Mesin Plant A
    public function getPlantAMachineMap()
    {
        return [
            'bak_1' => 1,
            'bak_2' => 3,
            'bak_3' => 226,
            'bak_4' => 228,
            'bak_5' => 227,
            'bak_6' => 2,
        ];
    }

    // 2. Fungsi cerdas format satuan
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

    // 3. Cek apakah ada inputan (Sudah ditambahkan field _2 untuk Bak 6)
    public function hasInput($data)
    {
        if (!$data) return false;

        return collect($data)->only([
            'draw_type',
            'draw_supplier',
            'draw_warna',
            'draw_konsentrasi',
            'draw_ph',
            'draw_temp',
            'hourmeter',
            'ann_type',
            'ann_supplier',
            'ann_warna',
            'ann_konsentrasi',
            'ann_ph',
            'ann_temp',
            'ann_type_2',
            'ann_supplier_2',
            'ann_warna_2',
            'ann_konsentrasi_2',
            'ann_ph_2',
            'ann_temp_2'
        ])->filter(fn($val) => $val !== null && $val !== '')->isNotEmpty();
    }

    // 4. Pembungkus Format Massal (Menggantikan puluhan baris ternary di Controller)
    public function prepareData(array $data)
    {
        return [
            'draw_type'        => $data['draw_type'] ?? null,
            'draw_supplier'    => $data['draw_supplier'] ?? null,
            'draw_warna'       => $data['draw_warna'] ?? null,
            'draw_konsentrasi' => $this->formatValue($data['draw_konsentrasi'] ?? null, '%'),
            'draw_ph'          => $data['draw_ph'] ?? null,
            'draw_temp'        => $this->formatValue($data['draw_temp'] ?? null, 'C'),
            'hourmeter' => (isset($data['hourmeter']) && $data['hourmeter'] !== '')
                ? round((float) str_replace(',', '.', $data['hourmeter']), 2)
                : null,

            'ann_type'         => $data['ann_type'] ?? null,
            'ann_supplier'     => $data['ann_supplier'] ?? null,
            'ann_warna'        => $data['ann_warna'] ?? null,
            'ann_konsentrasi'  => $this->formatValue($data['ann_konsentrasi'] ?? null, '%'),
            'ann_ph'           => $data['ann_ph'] ?? null,
            'ann_temp'         => $this->formatValue($data['ann_temp'] ?? null, 'C'),

            'ann_type_2'       => $data['ann_type_2'] ?? null,
            'ann_supplier_2'   => $data['ann_supplier_2'] ?? null,
            'ann_warna_2'      => $data['ann_warna_2'] ?? null,
            'ann_konsentrasi_2' => $this->formatValue($data['ann_konsentrasi_2'] ?? null, '%'),
            'ann_ph_2'         => $data['ann_ph_2'] ?? null,
            'ann_temp_2'       => $this->formatValue($data['ann_temp_2'] ?? null, 'C'),
        ];
    }

    // 5. Penentuan Nama Pemeriksa
    public function getPemeriksaName($requestName, $userName)
    {
        if (!$requestName || $requestName === '........................' || $requestName === 'DATA TIDAK DITEMUKAN') {
            return $userName;
        }
        return $requestName;
    }

    public function parseStandardRange($stdString)
    {
        if (!$stdString) return ['min' => null, 'max' => null];
        preg_match_all('/[0-9]+(?:\.[0-9]+)?/', $stdString, $matches);
        if (empty($matches[0])) return ['min' => null, 'max' => null];

        $nums = array_map('floatval', $matches[0]);
        if (count($nums) >= 2) return ['min' => min($nums), 'max' => max($nums)];
        return ['min' => $nums[0], 'max' => null];
    }

    public function checkIsOos($actualValue, $stdString)
    {
        if ($actualValue === null || $actualValue === '' || empty($stdString)) return false;
        $actualNum = floatval(preg_replace('/[^0-9.-]/', '', $actualValue));
        $std = $this->parseStandardRange($stdString);

        if ($std['min'] !== null && $actualNum < $std['min']) return true;
        if ($std['max'] !== null && $actualNum > $std['max']) return true;

        return false;
    }
}
