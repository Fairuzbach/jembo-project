<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Engineering\Plant;
use App\Models\Engineering\EngCompoundCheck;
use App\Models\Engineering\EngCompoundStandard;
use App\Services\Engineering\CompoundCheckService;

class EngCompoundCheckController extends Controller
{
    protected $compoundService;

    // Inject Service melalui Constructor
    public function __construct(CompoundCheckService $compoundService)
    {
        $this->compoundService = $compoundService;
    }

    public function storeCompound(Request $request)
    {
        $request->validate([
            'plant' => 'required|in:Plant A,Autowire',
        ]);
        $plantName = $request->plant;
        $keterangan = $request->keterangan;
        $diperiksaOleh = $request->nama_pemeriksa;
        if (!$diperiksaOleh || $diperiksaOleh == '........................' || $diperiksaOleh == 'DATA TIDAK DITEMUKAN') {
            $diperiksaOleh = auth()->user()->name;
        }
        $namaForeman = auth()->user()->name;
        $plantId = ($plantName === 'Plant A') ? 1 : 2;

        DB::beginTransaction();
        try {
            if ($plantName === 'Plant A') {
                $machineMap = [
                    'bak_1' => 1,
                    'bak_2' => 3,
                    'bak_3' => 52,
                    'bak_4' => 53,
                    'bak_5' => 54,
                    'bak_6' => 2,
                ];
                $tanggal = $request->plant_a_tanggal;
                $dataBak = $request->plant_a;

                foreach ($dataBak as $bakKey => $data) {
                    $hasDrawing = collect($data)->only(['draw_type', 'draw_supplier', 'draw_warna', 'draw_konsentrasi', 'draw_ph', 'draw_temp'])->filter(fn($val) => $val !== null && $val !== '')->isNotEmpty();
                    $hasAnnealing = collect($data)->only(['ann_type', 'ann_supplier', 'ann_warna', 'ann_konsentrasi', 'ann_ph', 'ann_temp'])->filter(fn($val) => $val !== null && $val !== '')->isNotEmpty();
                    if (!$hasDrawing && !$hasAnnealing) continue;

                    // $dataYangAkanDisimpan = [
                    //     'plant_id'       => $plantId,
                    //     'machine_id'     => $machineMap[$bakKey] ?? 'NULL KARENA TIDAK COCOK',
                    //     'key_dari_html'  => $bakKey,
                    //     'tanggal_cek'    => $tanggal,
                    //     'diperiksa_oleh' => $diperiksaOleh,
                    //     'diketahui_oleh' => $namaForeman,
                    // ];

                    // // dd() akan menghentikan sistem dan menampilkan array di atas ke layar Anda
                    // dd("HASIL DEBUGGING SEBELUM SIMPAN:", $dataYangAkanDisimpan);

                    EngCompoundCheck::create([
                        'plant_id'       => $plantId,
                        'machine_id'     => $machineMap[$bakKey] ?? null,
                        'tanggal_cek'    => $tanggal,
                        'keterangan'     => $keterangan,
                        'diperiksa_oleh' => $diperiksaOleh,
                        'diketahui_oleh' => $namaForeman,
                        'status'         => 'waiting_approval',
                        'draw_type'      => $data['draw_type'] ?? null,
                        'draw_supplier'  => $data['draw_supplier'] ?? null,
                        'draw_warna'     => $data['draw_warna'] ?? null,
                        'draw_konsentrasi' => $data['draw_konsentrasi'] ? $data['draw_konsentrasi'] . '%' : null,
                        'draw_ph'        => $data['draw_ph'] ?? null,
                        'draw_temp'      => $data['draw_temp'] ? $data['draw_temp'] . '°C' : null,
                        'ann_type'       => $data['ann_type'] ?? null,
                        'ann_supplier'   => $data['ann_supplier'] ?? null,
                        'ann_warna'      => $data['ann_warna'] ?? null,
                        'ann_konsentrasi' => $data['ann_konsentrasi'] ? $data['ann_konsentrasi'] . '%' : null,
                        'ann_ph'         => $data['ann_ph'] ?? null,
                        'ann_temp'       => $data['ann_temp'] ? $data['ann_temp'] . '°C' : null,
                    ]);
                }
            } elseif ($plantName === 'Autowire') {
                $autowireMachineId = 55;
                $dataCek = $request->autowire;
                foreach ($dataCek as $cekKey => $data) {
                    if (empty($data['tanggal'])) continue;

                    EngCompoundCheck::create([
                        'plant_id'       => $plantId,
                        'machine_id'     => $autowireMachineId,
                        'tanggal_cek'    => $data['tanggal'],
                        'keterangan'     => $keterangan,
                        'diperiksa_oleh' => $diperiksaOleh,
                        'diketahui_oleh' => $namaForeman,
                        'status'         => 'waiting_approval',
                        'draw_type'      => $data['draw_type'] ?? null,
                        'draw_supplier'  => $data['draw_supplier'] ?? null,
                        'draw_warna'     => $data['draw_warna'] ?? null,
                        'draw_konsentrasi' => isset($data['draw_konsentrasi']) ? $data['draw_konsentrasi'] . '%' : null,
                        'draw_ph'        => $data['draw_ph'] ?? null,
                        'draw_temp'      => isset($data['draw_temp']) ? $data['draw_temp'] . '°C' : null,
                        'ann_type'       => $data['ann_type'] ?? null,
                        'ann_supplier'   => $data['ann_supplier'] ?? null,
                        'ann_warna'      => $data['ann_warna'] ?? null,
                        'ann_konsentrasi' => isset($data['ann_konsentrasi']) ? $data['ann_konsentrasi'] . '%' : null,
                        'ann_ph'         => $data['ann_ph'] ?? null,
                        'ann_temp'       => isset($data['ann_temp']) ? $data['ann_temp'] . '°C' : null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data Pengecekan Compound berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error Store Compound: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function editCompound($plant_id, $tanggal)
    {
        $plant = Plant::findOrFail($plant_id);
        // Ambil semua data pengecekan pada tanggal dan plant tersebut
        $checksData = EngCompoundCheck::where('plant_id', $plant_id)
            ->whereDate('tanggal_cek', $tanggal)
            ->get();

        // 1. Ambil Nama Operator, Foreman, dan Status dari salah satu baris data (karena dalam 1 submit namanya pasti sama)
        $firstCheck   = $checksData->first();
        $operatorName = $firstCheck ? $firstCheck->diperiksa_oleh : '';
        $foremanName  = $firstCheck ? $firstCheck->diketahui_oleh : '';
        $status       = $firstCheck ? $firstCheck->status : 'waiting_approval';

        // 2. Jadikan ID Mesin sebagai key array agar mudah dipanggil di Blade
        $checks = $checksData->keyBy('machine_id');

        // Tentukan nama Plant (untuk logika di Blade)
        $plantName = ($plant->name === 'Plant A - Autowire' || str_contains(strtolower($plant->name), 'autowire')) ? 'Autowire' : 'Plant A';

        $allStandards = EngCompoundStandard::all();

        $stdPlantA = $allStandards->where('plant', 'Plant A')->groupBy('kode_mesin');
        $stdAutowire = $allStandards->where('plant', 'Autowire')->groupBy('kode_mesin');

        return view('Division.Engineering.partials.modals.edit-compound', compact(
            'plant',
            'plantName',
            'tanggal',
            'checks',
            'stdPlantA',
            'stdAutowire',
            'operatorName', // Kirim ke Blade
            'foremanName',  // Kirim ke Blade
            'status'        // Kirim ke Blade (berguna jika mau buat tombol Approve)
        ));
    }

    public function updateCompound(Request $request, $plant_id, $tanggal)
    {
        // Validasi dasar
        $request->validate([
            'plant' => 'required|in:Plant A,Autowire',
        ]);

        $plantName = $request->plant;
        $keterangan = $request->keterangan;
        $diperiksaOleh = $request->nama_pemeriksa;
        if (!$diperiksaOleh || $diperiksaOleh == '........................' || $diperiksaOleh == 'DATA TIDAK DITEMUKAN') {
            $diperiksaOleh = auth()->user()->name;
        }

        $diketahuiOleh = auth()->user()->name;

        DB::beginTransaction();

        try {
            // LOGIKA UPDATE: PLANT A
            if ($plantName === 'Plant A') {

                // 1. PASTIKAN ID MESIN SAMA PERSIS DENGAN FUNGSI STORE
                $machineMap = [
                    'bak_1' => 1,
                    'bak_2' => 3,
                    'bak_3' => 52,
                    'bak_4' => 53,
                    'bak_5' => 54,
                    'bak_6' => 2,
                ];

                $plantA_data = $request->plant_a;

                if ($plantA_data) {
                    foreach ($plantA_data as $bakKey => $data) {
                        // Cek apakah ada data teknis yang diisi di Bak ini (selain null/string kosong)
                        $hasInput = collect($data)->only([
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

                        // Ambil tanggal dari input tanggal plant A (fallback ke parameter route $tanggal)
                        $tglCek = $request->plant_a_tanggal ?? $tanggal;

                        if ($hasInput) {
                            // 2. PENAMBAHAN SATUAN AMAN (Cek dulu apakah sudah ada % atau °C biar tidak dobel)
                            $draw_kons = !empty($data['draw_konsentrasi']) ? (str_contains($data['draw_konsentrasi'], '%') ? $data['draw_konsentrasi'] : $data['draw_konsentrasi'] . '%') : null;
                            $draw_temp = !empty($data['draw_temp'])        ? (str_contains($data['draw_temp'], 'C')        ? $data['draw_temp']        : $data['draw_temp'] . '°C') : null;
                            $ann_kons  = !empty($data['ann_konsentrasi'])  ? (str_contains($data['ann_konsentrasi'], '%')  ? $data['ann_konsentrasi']  : $data['ann_konsentrasi'] . '%') : null;
                            $ann_temp  = !empty($data['ann_temp'])         ? (str_contains($data['ann_temp'], 'C')         ? $data['ann_temp']         : $data['ann_temp'] . '°C') : null;

                            // Update atau Create Data
                            EngCompoundCheck::updateOrCreate(
                                [
                                    'plant_id'    => $plant_id,
                                    'machine_id'  => $machineMap[$bakKey] ?? null,
                                    'tanggal_cek' => $tglCek,
                                ],
                                [
                                    'diperiksa_oleh' => $diperiksaOleh,
                                    'diketahui_oleh' => $diketahuiOleh,
                                    'keterangan'     => $keterangan,

                                    'draw_type'        => $data['draw_type'] ?? null,
                                    'draw_supplier'    => $data['draw_supplier'] ?? null,
                                    'draw_warna'       => $data['draw_warna'] ?? null,
                                    'draw_konsentrasi' => $draw_kons,
                                    'draw_ph'          => $data['draw_ph'] ?? null,
                                    'draw_temp'        => $draw_temp,

                                    'ann_type'         => $data['ann_type'] ?? null,
                                    'ann_supplier'     => $data['ann_supplier'] ?? null,
                                    'ann_warna'        => $data['ann_warna'] ?? null,
                                    'ann_konsentrasi'  => $ann_kons,
                                    'ann_ph'           => $data['ann_ph'] ?? null,
                                    'ann_temp'         => $ann_temp,
                                ]
                            );
                        } else {
                            EngCompoundCheck::where('plant_id', $plant_id)
                                ->where('machine_id', $machineMap[$bakKey] ?? null)
                                ->whereDate('tanggal_cek', $tglCek)
                                ->delete();
                        }
                    }
                }
            }
            // LOGIKA UPDATE: AUTOWIRE
            elseif ($plantName === 'Autowire') {
                $autowireMachineId = 55;
                $dataCek = $request->autowire;
                if ($dataCek) {
                    foreach ($dataCek as $cekKey => $data) {
                        $tglCekAuto = $data['tanggal'] ?? null;
                        if (empty($tglCekAuto)) continue;
                        $hasInput = collect($data)->only([
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
                        if ($hasInput) {
                            // Format satuan khusus Autowire
                            $draw_kons = !empty($data['draw_konsentrasi']) ? (str_contains($data['draw_konsentrasi'], '%') ? $data['draw_konsentrasi'] : $data['draw_konsentrasi'] . '%') : null;
                            $draw_temp = !empty($data['draw_temp'])        ? (str_contains($data['draw_temp'], 'C')        ? $data['draw_temp']        : $data['draw_temp'] . '°C') : null;
                            $ann_kons  = !empty($data['ann_konsentrasi'])  ? (str_contains($data['ann_konsentrasi'], '%')  ? $data['ann_konsentrasi']  : $data['ann_konsentrasi'] . '%') : null;
                            $ann_temp  = !empty($data['ann_temp'])         ? (str_contains($data['ann_temp'], 'C')         ? $data['ann_temp']         : $data['ann_temp'] . '°C') : null;

                            EngCompoundCheck::updateOrCreate(
                                [
                                    'plant_id'    => $plant_id,
                                    'machine_id'  => $autowireMachineId,
                                    'tanggal_cek' => $tglCekAuto, // Pencarian spesifik ke tanggal di tab Autowire tersebut
                                ],
                                [
                                    'diperiksa_oleh' => $diperiksaOleh,
                                    'diketahui_oleh' => $diketahuiOleh,
                                    'keterangan'     => $keterangan,

                                    'draw_type'        => $data['draw_type'] ?? null,
                                    'draw_supplier'    => $data['draw_supplier'] ?? null,
                                    'draw_warna'       => $data['draw_warna'] ?? null,
                                    'draw_konsentrasi' => $draw_kons,
                                    'draw_ph'          => $data['draw_ph'] ?? null,
                                    'draw_temp'        => $draw_temp,

                                    'ann_type'         => $data['ann_type'] ?? null,
                                    'ann_supplier'     => $data['ann_supplier'] ?? null,
                                    'ann_warna'        => $data['ann_warna'] ?? null,
                                    'ann_konsentrasi'  => $ann_kons,
                                    'ann_ph'           => $data['ann_ph'] ?? null,
                                    'ann_temp'         => $ann_temp,
                                ]
                            );
                        } else {
                            // Hapus jika tab Autowire ini dikosongkan saat proses edit
                            EngCompoundCheck::where('plant_id', $plant_id)
                                ->where('machine_id', $autowireMachineId)
                                ->whereDate('tanggal_cek', $tglCekAuto)
                                ->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('eng.index')->with('success', 'Data Compound berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error Update Compound: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat mengupdate data.');
        }
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
                $q->whereNotNull('draw_ph')->orWhereNotNull('ann_ph');
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
                AVG(CAST(REPLACE(draw_konsentrasi, '%', '') AS DECIMAL(10,2))) as avg_draw_kons,
                AVG(CAST(REPLACE(ann_konsentrasi, '%', '') AS DECIMAL(10,2))) as avg_ann_kons,
                AVG(CAST(REPLACE(draw_temp, '°C', '') AS DECIMAL(10,2))) as avg_draw_temp,
                AVG(CAST(REPLACE(ann_temp, '°C', '') AS DECIMAL(10,2))) as avg_ann_temp
            ")
            ->groupByRaw($groupBy)
            ->orderByRaw('MIN(tanggal_cek) ASC')
            ->get();

        $labels = $stats->pluck('label');
        $drawPhData = $stats->pluck('avg_draw_ph')->map(fn($val) => round($val, 2));
        $annPhData  = $stats->pluck('avg_ann_ph')->map(fn($val) => round($val, 2));
        $drawKonsData = $stats->pluck('avg_draw_kons')->map(fn($val) => round($val, 2));
        $annKonsData  = $stats->pluck('avg_ann_kons')->map(fn($val) => round($val, 2));
        $drawTempData = $stats->pluck('avg_draw_temp')->map(fn($val) => round($val, 2));
        $annTempData  = $stats->pluck('avg_ann_temp')->map(fn($val) => round($val, 2));

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
            52 => 'BAK 3 (QDMD)',
            53 => 'BAK 4 (Multi 2 Samp)',
            54 => 'BAK 5 (Multi 1 Samp)',
            2 => 'BAK 6 (Twin RBD Cu)',
        ];

        return view('Division.Engineering.compound-stats', compact(
            'labels',
            'filter',
            'drawPhData',
            'annPhData',
            'drawKonsData',
            'annKonsData',
            'drawTempData',
            'annTempData',
            'mode',
            'plant',
            'machineId',
            'plantAMachines',
            'stdValues'
        ));
    }
}
