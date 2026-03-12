<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Engineering\Plant;
use App\Models\Engineering\EngCompoundCheck;
use App\Models\Engineering\EngCompoundStandard;
use App\Models\Engineering\Machine;
use App\Services\Engineering\CompoundCheckService;
use App\Services\Engineering\CompoundExportService;

class EngCompoundCheckController extends Controller
{
    protected $compoundService;
    protected $exportService;

    // Inject Service melalui Constructor
    public function __construct(CompoundCheckService $compoundService, CompoundExportService $exportService)
    {
        $this->compoundService = $compoundService;
        $this->exportService = $exportService;
    }

    public function storeCompound(Request $request)
    {
        $request->validate(['plant' => 'required|in:Plant A,Autowire']);

        $plantName = $request->plant;
        $plantId = ($plantName === 'Plant A') ? 1 : 2;
        $diperiksaOleh = $this->compoundService->getPemeriksaName($request->nama_pemeriksa, auth()->user()->name);
        $diketahuiOleh = auth()->user()->name;

        // Base atribut yang sama untuk semua data
        $baseAttributes = [
            'plant_id'       => $plantId,
            'keterangan'     => $request->keterangan,
            'diperiksa_oleh' => $diperiksaOleh,
            'diketahui_oleh' => $diketahuiOleh,
            'status'         => 'waiting_approval',
        ];

        DB::beginTransaction();
        try {
            if ($plantName === 'Plant A') {
                $machineMap = $this->compoundService->getPlantAMachineMap();

                foreach ($request->plant_a as $bakKey => $data) {
                    if (!$this->compoundService->hasInput($data)) continue;

                    $formattedData = $this->compoundService->prepareData($data);

                    EngCompoundCheck::create(array_merge($baseAttributes, $formattedData, [
                        'machine_id'  => $machineMap[$bakKey] ?? null,
                        'tanggal_cek' => $request->plant_a_tanggal,
                    ]));
                }
            } elseif ($plantName === 'Autowire') {
                $tanggalCek = $request->autowire_tanggal;
                if (!$tanggalCek) throw new \Exception("Tanggal pengecekan Autowire wajib diisi.");

                if (EngCompoundCheck::where('plant_id', $plantId)->where('machine_id', 52)->where('tanggal_cek', $tanggalCek)->exists()) {
                    throw new \Exception("Data pengecekan untuk tanggal " . \Carbon\Carbon::parse($tanggalCek)->format('d-m-Y') . " sudah ada.");
                }

                if ($this->compoundService->hasInput($request->autowire)) {
                    $formattedData = $this->compoundService->prepareData($request->autowire);

                    EngCompoundCheck::create(array_merge($baseAttributes, $formattedData, [
                        'machine_id'  => 52,
                        'tanggal_cek' => $tanggalCek,
                    ]));
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data Pengecekan Compound berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error Store Compound: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function editCompound($plant_id, $tanggal)
    {
        $plant = Plant::findOrFail($plant_id);
        $plantName = ($plant->name === 'Plant A - Autowire' || str_contains(strtolower($plant->name), 'autowire')) ? 'Autowire' : 'Plant A';
        $checksData = EngCompoundCheck::where('plant_id', $plant_id)
            ->whereDate('tanggal_cek', $tanggal)
            ->get();
        if ($plantName === 'Autowire') {
            $checks = $checksData->values();
        } else {
            $checks = $checksData->keyBy('machine_id');
        }

        $firstCheck   = $checksData->first();
        $operatorName = $firstCheck ? $firstCheck->diperiksa_oleh : '';
        $foremanName  = $firstCheck ? $firstCheck->diketahui_oleh : '';
        $keterangan   = $firstCheck ? $firstCheck->keterangan : '';
        $status       = $firstCheck ? $firstCheck->status : 'waiting_approval';

        $machineMap = [
            'bak_1' => 1,
            'bak_2' => 3,
            'bak_3' => 226,
            'bak_4' => 228,
            'bak_5' => 227,
            'bak_6' => 2,
        ];

        $allStandards = EngCompoundStandard::all();
        $stdPlantA = $allStandards->where('plant', 'Plant A')->groupBy('kode_mesin');
        $stdAutowire = $allStandards->where('plant', 'Autowire')->groupBy('kode_mesin');

        return view('Division.Engineering.partials.modals.edit-compound', compact(
            'plant',
            'plantName',
            'tanggal',
            'checks',
            'machineMap',   // <--- Variabel baru untuk memetakan ID mesin ke Bak
            'stdPlantA',
            'stdAutowire',
            'operatorName',
            'foremanName',
            'keterangan',   // <--- Kirim ke Blade agar textarea keterangan terisi
            'status'
        ));
    }

    public function updateCompound(Request $request, $plant_id, $tanggal)
    {
        $request->validate(['plant' => 'required|in:Plant A,Autowire']);

        $plantName = $request->plant;
        $diperiksaOleh = $this->compoundService->getPemeriksaName($request->nama_pemeriksa, auth()->user()->name);
        $diketahuiOleh = auth()->user()->name;

        DB::beginTransaction();
        try {
            if ($plantName === 'Plant A' && $request->plant_a) {
                $machineMap = $this->compoundService->getPlantAMachineMap();
                $tglCek = $request->plant_a_tanggal ?? $tanggal;

                foreach ($request->plant_a as $bakKey => $data) {
                    $machineId = $machineMap[$bakKey] ?? null;

                    if ($this->compoundService->hasInput($data)) {
                        $formattedData = $this->compoundService->prepareData($data);

                        EngCompoundCheck::updateOrCreate(
                            ['plant_id' => $plant_id, 'machine_id' => $machineId, 'tanggal_cek' => $tglCek],
                            array_merge($formattedData, [
                                'diperiksa_oleh' => $diperiksaOleh,
                                'diketahui_oleh' => $diketahuiOleh,
                                'keterangan'     => $request->keterangan,
                            ])
                        );
                    } else {
                        // Jika inputan dikosongkan saat edit, hapus datanya
                        EngCompoundCheck::where('plant_id', $plant_id)->where('machine_id', $machineId)->whereDate('tanggal_cek', $tglCek)->delete();
                    }
                }
            } elseif ($plantName === 'Autowire' && $request->autowire) {
                if ($this->compoundService->hasInput($request->autowire)) {
                    $formattedData = $this->compoundService->prepareData($request->autowire);

                    EngCompoundCheck::updateOrCreate(
                        ['plant_id' => $plant_id, 'machine_id' => 52, 'tanggal_cek' => $request->autowire_tanggal],
                        array_merge($formattedData, [
                            'diperiksa_oleh' => $diperiksaOleh,
                            'diketahui_oleh' => $diketahuiOleh,
                            'keterangan'     => $request->keterangan,
                        ])
                    );
                }
            }

            DB::commit();
            return redirect()->route('eng.index')->with('success', 'Data Compound berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error Update Compound: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat mengupdate data.');
        }
    }

    public function report(Request $request)
    {
        $plantId = $request->input('plant_id');
        $bulan = (int) $request->input('bulan', date('n'));
        $tahun = (int) $request->input('tahun', date('Y'));

        $dataChecks = collect();
        $standards = collect();
        $baksMap = [];
        $plantName = '';
        $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');

        if ($plantId) {
            if ($plantId == 1) {
                $baksMap = [
                    1 => ['id_mesin' => 1, 'nama' => 'BAK 1 (HD 10C)', 'is_bak_6' => false],
                    2 => ['id_mesin' => 3, 'nama' => 'BAK 2 (MD 1)', 'is_bak_6' => false],
                    3 => ['id_mesin' => 226, 'nama' => 'BAK 3 (QDMD Deyang)', 'is_bak_6' => false],
                    4 => ['id_mesin' => 228, 'nama' => 'BAK 4 (Multi 2 Samp)', 'is_bak_6' => false],
                    5 => ['id_mesin' => 227, 'nama' => 'BAK 5 (Multi 1 Samp)', 'is_bak_6' => false],
                    6 => ['id_mesin' => 2, 'nama' => 'BAK 6 (Twin RBD Cu)', 'is_bak_6' => true],
                ];
                $plantName = 'Plant A';
            } else {
                $baksMap = [
                    1 => ['id_mesin' => 52, 'nama' => 'Multi Drawing 3 HONTA', 'is_bak_6' => false],
                ];
                $plantName = 'Autowire (Multi 3 HONTA)';
            }

            $dataChecks = EngCompoundCheck::with(['pemeriksa'])
                ->where('plant_id', $plantId)
                ->whereMonth('tanggal_cek', $bulan)
                ->whereYear('tanggal_cek', $tahun)
                ->orderBy('tanggal_cek', 'asc')
                ->get()
                ->groupBy('machine_id');

            // =========================================================
            // 3. Ambil Data Standar (SUDAH DIPERBAIKI)
            // =========================================================
            if ($plantId == 1) {
                // Logika pencarian standar Plant A (menggunakan machine_id)
                $machineIds = collect($baksMap)->pluck('id_mesin');
                $standards = DB::table('eng_compound_standards')
                    ->whereIn('machine_id', $machineIds)
                    ->get()
                    ->groupBy('machine_id');
            } else {
                // Logika pencarian standar Autowire (menggunakan nama plant)
                $autowireStandards = DB::table('eng_compound_standards')
                    ->where('plant', 'Autowire')
                    ->get();
                $standards = collect([52 => $autowireStandards]);
            }
        }

        return view('Division.Engineering.compound.report', compact(
            'dataChecks',
            'baksMap',
            'standards',
            'bulan',
            'tahun',
            'namaBulan',
            'plantName',
            'plantId'
        ));
    }

    public function statistics(Request $request)
    {
        if (auth()->user()->role !== 'eng.admin') {
            abort(403, 'Akses Ditolak. Halaman ini khusus untuk Engineering Admin.');
        }
        $filter = $request->query('filter', 'monthly');
        $mode = $request->query('mode', 'avg');
        $plant = $request->query('plant', 'Plant A');
        $plantId = ($plant === 'Autowire') ? 2 : 1;
        $machineId = $request->query('machine', 'all');

        $query = \App\Models\Engineering\EngCompoundCheck::where('plant_id', $plantId)
            ->where(function ($q) {
                // Tambahkan pengecekan ann_ph_2 agar query tetap aman
                $q->whereNotNull('draw_ph')
                    ->orWhereNotNull('ann_ph')
                    ->orWhereNotNull('ann_ph_2');
            });

        if ($plant === 'Plant A' && $machineId !== 'all') {
            $query->where('machine_id', $machineId);
        }

        if ($mode === 'raw') {
            $groupBy = 'tanggal_cek';
            $selectLabel = "CONCAT('Minggu ', WEEK(MIN(tanggal_cek), 1), ' - ', YEAR(MIN(tanggal_cek))) as label";
        } else {
            switch ($filter) {
                case 'weekly':
                    $groupBy = 'YEARWEEK(tanggal_cek, 1)';
                    $selectLabel = "CONCAT('Minggu ', WEEK(MIN(tanggal_cek), 1), ' - ', YEAR(MIN(tanggal_cek))) as label";
                    break;
                case 'quarterly':
                    $groupBy = 'CONCAT(YEAR(tanggal_cek), "-", QUARTER(tanggal_cek))';
                    $selectLabel = "CONCAT('Q', QUARTER(MIN(tanggal_cek)), ' ', YEAR(MIN(tanggal_cek))) as label";
                    break;
                case 'semester':
                    $groupBy = 'CONCAT(YEAR(tanggal_cek), "-", IF(MONTH(tanggal_cek)<=6, 1, 2))';
                    $selectLabel = "CONCAT('Semester ', IF(MONTH(MIN(tanggal_cek))<=6, 1, 2), ' ', YEAR(MIN(tanggal_cek))) as label";
                    break;
                case 'yearly':
                    $groupBy = 'YEAR(tanggal_cek)';
                    $selectLabel = "YEAR(MIN(tanggal_cek)) as label";
                    break;
                case 'monthly':
                default:
                    $groupBy = 'DATE_FORMAT(tanggal_cek, "%Y-%m")';
                    $selectLabel = "DATE_FORMAT(MIN(tanggal_cek), '%M %Y') as label";
                    break;
            }
        }

        $stats = $query->selectRaw("
                $selectLabel,
                AVG(draw_ph) as avg_draw_ph,
                AVG(ann_ph) as avg_ann_ph,
                AVG(ann_ph_2) as avg_ann_ph_2,
                AVG(CAST(REPLACE(draw_konsentrasi, '%', '') AS DECIMAL(10,2))) as avg_draw_kons,
                AVG(CAST(REPLACE(ann_konsentrasi, '%', '') AS DECIMAL(10,2))) as avg_ann_kons,
                AVG(CAST(REPLACE(ann_konsentrasi_2, '%', '') AS DECIMAL(10,2))) as avg_ann_kons_2,
                AVG(CAST(REPLACE(draw_temp, '°C', '') AS DECIMAL(10,2))) as avg_draw_temp,
                AVG(CAST(REPLACE(ann_temp, '°C', '') AS DECIMAL(10,2))) as avg_ann_temp,
                AVG(CAST(REPLACE(ann_temp_2, '°C', '') AS DECIMAL(10,2))) as avg_ann_temp_2
            ")
            ->groupByRaw($groupBy)
            ->orderByRaw('MIN(tanggal_cek) ASC')
            ->get();

        $labels = $stats->pluck('label');

        $drawPhData = $stats->pluck('avg_draw_ph')->map(fn($val) => round($val, 2));
        $annPhData  = $stats->pluck('avg_ann_ph')->map(fn($val) => round($val, 2));
        $annPhData2 = $stats->pluck('avg_ann_ph_2')->map(fn($val) => round($val, 2)); // DATA BARU

        $drawKonsData = $stats->pluck('avg_draw_kons')->map(fn($val) => round($val, 2));
        $annKonsData  = $stats->pluck('avg_ann_kons')->map(fn($val) => round($val, 2));
        $annKonsData2 = $stats->pluck('avg_ann_kons_2')->map(fn($val) => round($val, 2)); // DATA BARU

        $drawTempData = $stats->pluck('avg_draw_temp')->map(fn($val) => round($val, 2));
        $annTempData  = $stats->pluck('avg_ann_temp')->map(fn($val) => round($val, 2));
        $annTempData2 = $stats->pluck('avg_ann_temp_2')->map(fn($val) => round($val, 2)); // DATA BARU

        $stdDraw = null;
        $stdAnn = null;

        if ($plant === 'Autowire') {
            $stdDraw = \App\Models\Engineering\EngCompoundStandard::where('plant', 'Autowire')->where('proses', 'drawing')->first();
            $stdAnn = \App\Models\Engineering\EngCompoundStandard::where('plant', 'Autowire')->where('proses', 'annealing')->first();
        } elseif ($plant === 'Plant A' && $machineId !== 'all') {
            $stdDraw = \App\Models\Engineering\EngCompoundStandard::where('plant', 'Plant A')->where('kode_mesin', 'bak_' . $machineId)->where('proses', 'drawing')->first();
            $stdAnn = \App\Models\Engineering\EngCompoundStandard::where('plant', 'Plant A')->where('kode_mesin', 'bak_' . $machineId)->where('proses', 'annealing')->first();
        }

        $parseStdRange = function ($val) {
            if (!$val) return ['min' => null, 'max' => null];
            preg_match_all('/[0-9]+(?:\.[0-9]+)?/', $val, $matches);
            if (empty($matches[0])) return ['min' => null, 'max' => null];
            $numbers = array_map('floatval', $matches[0]);
            if (count($numbers) >= 2) {
                return ['min' => min($numbers), 'max' => max($numbers)];
            } else {
                return ['min' => $numbers[0], 'max' => null];
            }
        };

        $stdValues = [
            'draw_ph'   => $stdDraw ? $parseStdRange($stdDraw->std_ph) : ['min' => null, 'max' => null],
            'ann_ph'    => $stdAnn ? $parseStdRange($stdAnn->std_ph) : ['min' => null, 'max' => null],
            'draw_kons' => $stdDraw ? $parseStdRange($stdDraw->std_konsentrasi) : ['min' => null, 'max' => null],
            'ann_kons'  => $stdAnn ? $parseStdRange($stdAnn->std_konsentrasi) : ['min' => null, 'max' => null],
            'draw_temp' => $stdDraw ? $parseStdRange($stdDraw->std_temp) : ['min' => null, 'max' => null],
            'ann_temp'  => $stdAnn ? $parseStdRange($stdAnn->std_temp) : ['min' => null, 'max' => null],
        ];

        $plantAMachines = [
            'all' => 'Gabungan (Semua BAK)',
            1 => 'BAK 1 (HD 10 C)',
            3 => 'BAK 2 (MD 1)',
            226 => 'BAK 3 (QDMD)',
            228 => 'BAK 4 (Multi 2 Samp)',
            227 => 'BAK 5 (Multi 1 Samp)',
            2 => 'BAK 6 (Twin RBD Cu)',
        ];

        return view('Division.Engineering.compound-stats', compact(
            'labels',
            'filter',
            'drawPhData',
            'annPhData',
            'annPhData2',     // DATA BARU
            'drawKonsData',
            'annKonsData',
            'annKonsData2',   // DATA BARU
            'drawTempData',
            'annTempData',
            'annTempData2',   // DATA BARU
            'mode',
            'plant',
            'machineId',
            'plantAMachines',
            'stdValues'
        ));
    }
    public function export(Request $request)
    {
        $request->validate([
            'plant_id' => 'required',
            'bulan' => 'required|numeric|min:1|max:12',
            'tahun' => 'required|numeric',
        ]);

        try {
            // Kita cukup memanggil ExportService! Satu baris selesai.
            return $this->exportService->exportData(
                (int) $request->plant_id,
                (int) $request->bulan,
                (int) $request->tahun
            );
        } catch (\Exception $e) {
            \Log::error("Error Export Compound: " . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }
}
