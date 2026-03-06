<?php

namespace App\Services\Engineering;

use App\Models\Engineering\EngCompoundCheck;
use App\Models\Engineering\Machine;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class CompoundExportService
{
    public function exportData(int $plantId, int $bulan, int $tahun)
    {
        // 1. Penentuan Template
        if ($plantId == 1) {
            $templatePath = storage_path('app/templates/template_compound.xlsx');
            $plantLabel = 'Plant_A';
        } else {
            $templatePath = storage_path('app/templates/template_compound_autowire.xlsx');
            $plantLabel = 'Autowire';
        }

        if (!file_exists($templatePath)) {
            throw new \Exception("File template tidak ditemukan di: " . $templatePath);
        }

        $spreadsheet = IOFactory::load($templatePath);

        // 2. Ambil Data Transaksi
        $dataChecks = EngCompoundCheck::where('plant_id', $plantId)
            ->whereMonth('tanggal_cek', $bulan)
            ->whereYear('tanggal_cek', $tahun)
            ->orderBy('tanggal_cek', 'asc')
            ->get();

        $groupedData = $dataChecks->groupBy('machine_id');

        // 3. Isi Data ke Sheet
        foreach ($groupedData as $machineId => $checks) {
            $machine = Machine::find($machineId);
            $rawName = $machine ? strtoupper($machine->name) : '';
            $cleanDbName = preg_replace('/[^A-Z0-9]/', '', $rawName);

            $targetKeyword = $this->getTargetKeyword($cleanDbName);

            $sheet = null;
            foreach ($spreadsheet->getSheetNames() as $templateSheetName) {
                $cleanTemplateName = preg_replace('/[^A-Z0-9]/', '', strtoupper($templateSheetName));
                if ($targetKeyword !== '' && str_contains($cleanTemplateName, $targetKeyword)) {
                    $sheet = $spreadsheet->getSheetByName($templateSheetName);
                    break;
                }
            }

            if ($sheet) {
                $this->fillSheetData($sheet, $machineId, $checks, $targetKeyword);
            }
        }

        // 4. Proses Download
        $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');
        $fileName = 'Hasil_Cek_Compound_' . $plantLabel . '_' . $namaBulan . '_' . $tahun . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function getTargetKeyword(string $cleanDbName): string
    {
        if (str_contains($cleanDbName, 'HD10')) return 'BAK1';
        if (str_contains($cleanDbName, 'MD1')) return 'BAK2';
        if (str_contains($cleanDbName, 'QDMD')) return 'BAK3';
        if (str_contains($cleanDbName, 'MULTI2')) return 'BAK4';
        if (str_contains($cleanDbName, 'MULTI1')) return 'BAK5';
        if (str_contains($cleanDbName, 'TWIN') || str_contains($cleanDbName, 'RBD')) return 'BAK6';
        if (str_contains($cleanDbName, 'HONTA') || str_contains($cleanDbName, 'AUTOWIRE') || str_contains($cleanDbName, 'MULTIDRAWING3')) return 'HONTA';
        return '';
    }

    private function fillSheetData($sheet, $machineId, $checks, $targetKeyword)
    {
        // A. SUNTIKKAN NILAI STANDAR KE HEADER (Baris ke-6)
        $stdDraw = DB::table('eng_compound_standards')->where('machine_id', $machineId)->where('proses', 'drawing')->first();
        $stdAnn = DB::table('eng_compound_standards')->where('machine_id', $machineId)->where('proses', 'annealing')->first();

        $rowStd = 6;
        $formatStd = fn($val) => "Standard :\n" . ($val ?? '-');

        $sheet->setCellValue('C' . $rowStd, $formatStd($stdDraw->std_tipe ?? null));
        $sheet->setCellValue('D' . $rowStd, $formatStd($stdDraw->std_supplier ?? null));
        $sheet->setCellValue('E' . $rowStd, $formatStd($stdDraw->std_warna ?? null));
        $sheet->setCellValue('F' . $rowStd, $formatStd($stdDraw->std_konsentrasi ?? null));
        $sheet->setCellValue('G' . $rowStd, $formatStd($stdDraw->std_ph ?? null));
        $sheet->setCellValue('H' . $rowStd, $formatStd($stdDraw->std_temp ?? null));

        $sheet->setCellValue('I' . $rowStd, $formatStd($stdAnn->std_tipe ?? null));
        $sheet->setCellValue('J' . $rowStd, $formatStd($stdAnn->std_supplier ?? null));
        $sheet->setCellValue('K' . $rowStd, $formatStd($stdAnn->std_warna ?? null));
        $sheet->setCellValue('L' . $rowStd, $formatStd($stdAnn->std_konsentrasi ?? null));
        $sheet->setCellValue('M' . $rowStd, $formatStd($stdAnn->std_ph ?? null));
        $sheet->setCellValue('N' . $rowStd, $formatStd($stdAnn->std_temp ?? null));

        if ($targetKeyword === 'BAK6') {
            $sheet->setCellValue('O' . $rowStd, $formatStd($stdAnn->std_tipe ?? null));
            $sheet->setCellValue('P' . $rowStd, $formatStd($stdAnn->std_supplier ?? null));
            $sheet->setCellValue('Q' . $rowStd, $formatStd($stdAnn->std_warna ?? null));
            $sheet->setCellValue('R' . $rowStd, $formatStd($stdAnn->std_konsentrasi ?? null));
            $sheet->setCellValue('S' . $rowStd, $formatStd($stdAnn->std_ph ?? null));
            $sheet->setCellValue('T' . $rowStd, $formatStd($stdAnn->std_temp ?? null));
        }

        // B. TULIS DATA AKTUAL KE BAWAHNYA (Mulai Baris ke-7)
        $rowData = 7;
        foreach ($checks as $check) {
            $sheet->setCellValue('B' . $rowData, Carbon::parse($check->tanggal_cek)->format('d-m-Y'));
            $sheet->setCellValue('C' . $rowData, $check->draw_type);
            $sheet->setCellValue('D' . $rowData, $check->draw_supplier);
            $sheet->setCellValue('E' . $rowData, $check->draw_warna);
            $sheet->setCellValue('F' . $rowData, $check->draw_konsentrasi);
            $sheet->setCellValue('G' . $rowData, $check->draw_ph);
            $sheet->setCellValue('H' . $rowData, $check->draw_temp);

            $sheet->setCellValue('I' . $rowData, $check->ann_type);
            $sheet->setCellValue('J' . $rowData, $check->ann_supplier);
            $sheet->setCellValue('K' . $rowData, $check->ann_warna);
            $sheet->setCellValue('L' . $rowData, $check->ann_konsentrasi);
            $sheet->setCellValue('M' . $rowData, $check->ann_ph);
            $sheet->setCellValue('N' . $rowData, $check->ann_temp);

            if ($targetKeyword === 'BAK6') {
                $sheet->setCellValue('O' . $rowData, $check->ann_type_2 ?? '-');
                $sheet->setCellValue('P' . $rowData, $check->ann_supplier_2 ?? '-');
                $sheet->setCellValue('Q' . $rowData, $check->ann_warna_2 ?? '-');
                $sheet->setCellValue('R' . $rowData, $check->ann_konsentrasi_2 ?? '-');
                $sheet->setCellValue('S' . $rowData, $check->ann_ph_2 ?? '-');
                $sheet->setCellValue('T' . $rowData, $check->ann_temp_2 ?? '-');
                $sheet->setCellValue('U' . $rowData, $check->diperiksa_oleh);
                $sheet->setCellValue('V' . $rowData, $check->keterangan);
            } else {
                $sheet->setCellValue('O' . $rowData, $check->diperiksa_oleh);
                $sheet->setCellValue('P' . $rowData, $check->keterangan);
            }
            $rowData++;
        }
    }
}
