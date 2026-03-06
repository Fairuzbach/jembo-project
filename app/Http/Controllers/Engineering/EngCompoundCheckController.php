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
                    'bak_3' => 226,
                    'bak_4' => 228,
                    'bak_5' => 227,
                    'bak_6' => 2,
                ];
                $tanggal = $request->plant_a_tanggal;
                $dataBak = $request->plant_a;

                foreach ($dataBak as $bakKey => $data) {
                    $hasDrawing = collect($data)->only(['draw_type', 'draw_supplier', 'draw_warna', 'draw_konsentrasi', 'draw_ph', 'draw_temp'])->filter(fn($val) => $val !== null && $val !== '')->isNotEmpty();
                    $hasAnnealing = collect($data)->only([
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

                    if (!$hasDrawing && !$hasAnnealing) continue;

                    // PERBAIKAN: Format string aman yang tidak menghilangkan angka 0
                    $draw_kons  = (isset($data['draw_konsentrasi']) && $data['draw_konsentrasi'] !== '') ? (str_contains($data['draw_konsentrasi'], '%') ? $data['draw_konsentrasi'] : $data['draw_konsentrasi'] . '%') : null;
                    $draw_temp  = (isset($data['draw_temp']) && $data['draw_temp'] !== '') ? (str_contains($data['draw_temp'], 'C') ? $data['draw_temp'] : $data['draw_temp'] . '°C') : null;

                    $ann_kons   = (isset($data['ann_konsentrasi']) && $data['ann_konsentrasi'] !== '') ? (str_contains($data['ann_konsentrasi'], '%') ? $data['ann_konsentrasi'] : $data['ann_konsentrasi'] . '%') : null;
                    $ann_temp   = (isset($data['ann_temp']) && $data['ann_temp'] !== '') ? (str_contains($data['ann_temp'], 'C') ? $data['ann_temp'] : $data['ann_temp'] . '°C') : null;

                    $ann_kons_2 = (isset($data['ann_konsentrasi_2']) && $data['ann_konsentrasi_2'] !== '') ? (str_contains($data['ann_konsentrasi_2'], '%') ? $data['ann_konsentrasi_2'] : $data['ann_konsentrasi_2'] . '%') : null;
                    $ann_temp_2 = (isset($data['ann_temp_2']) && $data['ann_temp_2'] !== '') ? (str_contains($data['ann_temp_2'], 'C') ? $data['ann_temp_2'] : $data['ann_temp_2'] . '°C') : null;

                    EngCompoundCheck::create([
                        'plant_id'         => $plantId,
                        'machine_id'       => $machineMap[$bakKey] ?? null,
                        'tanggal_cek'      => $tanggal,
                        'keterangan'       => $keterangan,
                        'diperiksa_oleh'   => $diperiksaOleh,
                        'diketahui_oleh'   => $namaForeman,
                        'status'           => 'waiting_approval',

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


                        'ann_type_2'        => $data['ann_type_2'] ?? null,
                        'ann_supplier_2'    => $data['ann_supplier_2'] ?? null,
                        'ann_warna_2'       => $data['ann_warna_2'] ?? null,
                        'ann_konsentrasi_2' => $ann_kons_2,
                        'ann_ph_2'          => $data['ann_ph_2'] ?? null,
                        'ann_temp_2'        => $ann_temp_2,
                    ]);
                }
            } elseif ($plantName === 'Autowire') {
                $autowireMachineId = 55;
                $data = $request->autowire;
                $tanggalCek = $request->autowire_tanggal;
                if (!$tanggalCek) {
                    throw new \Exception("Tanggal pengecekan Autowire wajib diisi.");
                }

                $exists = EngCompoundCheck::where('plant_id', $plantId)
                    ->where('machine_id', $autowireMachineId)
                    ->where('tanggal_cek', $tanggalCek)
                    ->exists();

                if ($exists) {
                    throw new \Exception("Data pengecekan untuk tanggal " . \Carbon\Carbon::parse($tanggalCek)->format('d-m-Y') . " sudah ada.");
                }

                if ($data) {
                    // 2. Formatting Satuan (Konsentrasi & Temp)
                    $draw_kons = (isset($data['draw_konsentrasi']) && $data['draw_konsentrasi'] !== '') ? (str_contains($data['draw_konsentrasi'], '%') ? $data['draw_konsentrasi'] : $data['draw_konsentrasi'] . '%') : null;
                    $draw_temp = (isset($data['draw_temp']) && $data['draw_temp'] !== '') ? (str_contains($data['draw_temp'], 'C') ? $data['draw_temp'] : $data['draw_temp'] . '°C') : null;
                    $ann_kons  = (isset($data['ann_konsentrasi']) && $data['ann_konsentrasi'] !== '') ? (str_contains($data['ann_konsentrasi'], '%') ? $data['ann_konsentrasi'] : $data['ann_konsentrasi'] . '%') : null;
                    $ann_temp  = (isset($data['ann_temp']) && $data['ann_temp'] !== '') ? (str_contains($data['ann_temp'], 'C') ? $data['ann_temp'] : $data['ann_temp'] . '°C') : null;

                    // 3. Simpan Data (Single Insert, No Loop)
                    EngCompoundCheck::create([
                        'plant_id'         => $plantId,
                        'machine_id'       => $autowireMachineId,
                        'tanggal_cek'      => $tanggalCek,
                        'keterangan'       => $keterangan,
                        'diperiksa_oleh'   => $diperiksaOleh,
                        'diketahui_oleh'   => $namaForeman,
                        'status'           => 'waiting_approval',

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
        $plantName = ($plant->name === 'Plant A - Autowire' || str_contains(strtolower($plant->name), 'autowire')) ? 'Autowire' : 'Plant A';

        if ($plantName === 'Autowire') {
            $bulan = \Carbon\Carbon::parse($tanggal)->month;
            $tahun = \Carbon\Carbon::parse($tanggal)->year;

            $checksData = EngCompoundCheck::where('plant_id', $plant_id)->whereMonth('tanggal_cek', $bulan)->whereYear('tanggal_cek', $tahun)->orderBy('tanggal_cek', 'asc')->get();

            $checks = $checksData->values();
        } else {
            $checksData = EngCompoundCheck::where('plant_id', $plant_id)->whereDate('tanggal_cek', $tanggal)->get();
            $checks = $checksData->keyBy('machine_id');
        }

        $firstCheck   = $checksData->first();
        $operatorName = $firstCheck ? $firstCheck->diperiksa_oleh : '';
        $foremanName  = $firstCheck ? $firstCheck->diketahui_oleh : '';
        $keterangan   = $firstCheck ? $firstCheck->keterangan : ''; // Tambahan: Ambil keterangan existing
        $status       = $firstCheck ? $firstCheck->status : 'waiting_approval';

        // 3. Mapping ID Mesin untuk Plant A (Sangat penting agar Blade tahu ID masing-masing Bak)
        $machineMap = [
            'bak_1' => 1,
            'bak_2' => 3,
            'bak_3' => 226,
            'bak_4' => 228,
            'bak_5' => 227,
            'bak_6' => 2, // Bak 6 / Twin RBD
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
            if ($plantName === 'Plant A') {
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
                        // UPDATE: Tangkap juga inputan _2 saat Edit
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
                            'ann_temp',
                            'ann_type_2',
                            'ann_supplier_2',
                            'ann_warna_2',
                            'ann_konsentrasi_2',
                            'ann_ph_2',
                            'ann_temp_2'
                        ])->filter(fn($val) => $val !== null && $val !== '')->isNotEmpty();

                        $tglCek = $request->plant_a_tanggal ?? $tanggal;

                        if ($hasInput) {
                            $draw_kons  = (isset($data['draw_konsentrasi']) && $data['draw_konsentrasi'] !== '') ? (str_contains($data['draw_konsentrasi'], '%') ? $data['draw_konsentrasi'] : $data['draw_konsentrasi'] . '%') : null;
                            $draw_temp  = (isset($data['draw_temp']) && $data['draw_temp'] !== '') ? (str_contains($data['draw_temp'], 'C') ? $data['draw_temp'] : $data['draw_temp'] . '°C') : null;
                            $ann_kons   = (isset($data['ann_konsentrasi']) && $data['ann_konsentrasi'] !== '') ? (str_contains($data['ann_konsentrasi'], '%') ? $data['ann_konsentrasi'] : $data['ann_konsentrasi'] . '%') : null;
                            $ann_temp   = (isset($data['ann_temp']) && $data['ann_temp'] !== '') ? (str_contains($data['ann_temp'], 'C') ? $data['ann_temp'] : $data['ann_temp'] . '°C') : null;

                            $ann_kons_2 = (isset($data['ann_konsentrasi_2']) && $data['ann_konsentrasi_2'] !== '') ? (str_contains($data['ann_konsentrasi_2'], '%') ? $data['ann_konsentrasi_2'] : $data['ann_konsentrasi_2'] . '%') : null;
                            $ann_temp_2 = (isset($data['ann_temp_2']) && $data['ann_temp_2'] !== '') ? (str_contains($data['ann_temp_2'], 'C') ? $data['ann_temp_2'] : $data['ann_temp_2'] . '°C') : null;

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

                                    'ann_type_2'        => $data['ann_type_2'] ?? null,
                                    'ann_supplier_2'    => $data['ann_supplier_2'] ?? null,
                                    'ann_warna_2'       => $data['ann_warna_2'] ?? null,
                                    'ann_konsentrasi_2' => $ann_kons_2,
                                    'ann_ph_2'          => $data['ann_ph_2'] ?? null,
                                    'ann_temp_2'        => $ann_temp_2,
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
            } elseif ($plantName === 'Autowire') {
                $autowireMachineId = 55;
                $data = $request->autowire;
                $tglCekAuto = $request->autowire_tanggal;

                if ($data) {
                    $draw_kons = (isset($data['draw_konsentrasi']) && $data['draw_konsentrasi'] !== '') ? (str_contains($data['draw_konsentrasi'], '%') ? $data['draw_konsentrasi'] : $data['draw_konsentrasi'] . '%') : null;
                    $draw_temp = (isset($data['draw_temp']) && $data['draw_temp'] !== '') ? (str_contains($data['draw_temp'], 'C') ? $data['draw_temp'] : $data['draw_temp'] . '°C') : null;
                    $ann_kons  = (isset($data['ann_konsentrasi']) && $data['ann_konsentrasi'] !== '') ? (str_contains($data['ann_konsentrasi'], '%') ? $data['ann_konsentrasi'] : $data['ann_konsentrasi'] . '%') : null;
                    $ann_temp  = (isset($data['ann_temp']) && $data['ann_temp'] !== '') ? (str_contains($data['ann_temp'], 'C') ? $data['ann_temp'] : $data['ann_temp'] . '°C') : null;

                    EngCompoundCheck::updateOrCreate(
                        [
                            'plant_id'    => $plant_id,
                            'machine_id'  => $autowireMachineId,
                            'tanggal_cek' => $tglCekAuto,
                        ],
                        [
                            'diperiksa_oleh' => $diperiksaOleh,
                            'diketahui_oleh' => $diketahuiOleh,
                            'keterangan'     => $keterangan,
                            'draw_type'      => $data['draw_type'] ?? null,
                            'draw_supplier'  => $data['draw_supplier'] ?? null,
                            'draw_warna'     => $data['draw_warna'] ?? null,
                            'draw_konsentrasi' => $draw_kons,
                            'draw_ph'        => $data['draw_ph'] ?? null,
                            'draw_temp'      => $draw_temp,
                            'ann_type'       => $data['ann_type'] ?? null,
                            'ann_supplier'   => $data['ann_supplier'] ?? null,
                            'ann_warna'      => $data['ann_warna'] ?? null,
                            'ann_konsentrasi' => $ann_kons,
                            'ann_ph'         => $data['ann_ph'] ?? null,
                            'ann_temp'       => $ann_temp,
                        ]
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

            // 3. Ambil Data Standar
            $machineIds = collect($baksMap)->pluck('id_mesin');
            $standards = DB::table('eng_compound_standards')
                ->whereIn('machine_id', $machineIds)
                ->get()
                ->groupBy('machine_id');
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

        $plantId = (int) $request->plant_id;
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // 1. PENENTUAN FILE TEMPLATE BERDASARKAN PLANT
        if ($plantId == 1) {
            $templatePath = storage_path('app/templates/template_compound.xlsx');
            $plantLabel = 'Plant_A';
        } else {
            $templatePath = storage_path('app/templates/template_compound_autowire.xlsx');
            $plantLabel = 'Autowire';
        }

        if (!file_exists($templatePath)) {
            return back()->with('error', "File template tidak ditemukan di: " . $templatePath);
        }

        // 2. Load File Excel Template
        $spreadsheet = IOFactory::load($templatePath);

        // 3. Ambil data transaksi
        $dataChecks = EngCompoundCheck::where('plant_id', $plantId)
            ->whereMonth('tanggal_cek', $bulan)
            ->whereYear('tanggal_cek', $tahun)
            ->orderBy('tanggal_cek', 'asc')
            ->get();

        // if ($plantId == 2) { // 2 adalah ID dari form Autowire
        //     $daftarMesin = [];
        //     foreach ($dataChecks as $cek) {
        //         $mesin = Machine::find($cek->machine_id);
        //         $daftarMesin[] = $mesin ? $mesin->name : 'ID Mesin tidak ditemukan: ' . $cek->machine_id;
        //     }

        //     dd([
        //         'Plant ID yang dicari' => $plantId,
        //         'Bulan' => $bulan,
        //         'Tahun' => $tahun,
        //         'Total Data Ditemukan' => $dataChecks->count(),
        //         'Daftar Nama Mesin Autowire' => array_unique($daftarMesin)
        //     ]);
        // }

        // Kelompokkan data berdasarkan mesin/bak
        $groupedData = $dataChecks->groupBy('machine_id');

        // 4. Isi data ke masing-masing Sheet
        foreach ($groupedData as $machineId => $checks) {
            $machine = Machine::find($machineId);
            $rawName = $machine ? strtoupper($machine->name) : '';

            // Bersihkan nama database dari spasi/simbol untuk pencocokan "Anti-Gagal"
            $cleanDbName = preg_replace('/[^A-Z0-9]/', '', $rawName);

            // PENCARIAN NAMA MESIN TANPA BERGANTUNG PADA KATA "BAK"
            $targetKeyword = '';
            if (str_contains($cleanDbName, 'HD10')) {
                $targetKeyword = 'BAK1';
            } elseif (str_contains($cleanDbName, 'MD1')) {
                $targetKeyword = 'BAK2';
            } elseif (str_contains($cleanDbName, 'QDMD')) {
                $targetKeyword = 'BAK3';
            } elseif (str_contains($cleanDbName, 'MULTI2')) {
                $targetKeyword = 'BAK4';
            } elseif (str_contains($cleanDbName, 'MULTI1')) {
                $targetKeyword = 'BAK5';
            } elseif (str_contains($cleanDbName, 'TWIN') || str_contains($cleanDbName, 'RBD')) {
                // Jika ada kata Twin atau RBD, otomatis ini Bak 6
                $targetKeyword = 'BAK6';
            } elseif (str_contains($cleanDbName, 'HONTA') || str_contains($cleanDbName, 'AUTOWIRE') || str_contains($cleanDbName, 'MULTIDRAWING3')) {
                $targetKeyword = 'HONTA';
            }

            // Cari sheet template
            $sheet = null;
            foreach ($spreadsheet->getSheetNames() as $templateSheetName) {
                $cleanTemplateName = preg_replace('/[^A-Z0-9]/', '', strtoupper($templateSheetName));
                if ($targetKeyword !== '' && str_contains($cleanTemplateName, $targetKeyword)) {
                    $sheet = $spreadsheet->getSheetByName($templateSheetName);
                    break;
                }
            }

            if ($sheet) {
                // A. SUNTIKKAN NILAI STANDAR KE HEADER (Baris ke-6)
                $stdDraw = DB::table('eng_compound_standards')->where('machine_id', $machineId)->where('proses', 'drawing')->first();
                $stdAnn = DB::table('eng_compound_standards')->where('machine_id', $machineId)->where('proses', 'annealing')->first();

                $rowStd = 6;
                $formatStd = function ($val) {
                    return "Standard :\n" . ($val ?? '-');
                };

                // Suntik Standar Drawing (Kolom C - H)
                $sheet->setCellValue('C' . $rowStd, $formatStd($stdDraw->std_tipe ?? null));
                $sheet->setCellValue('D' . $rowStd, $formatStd($stdDraw->std_supplier ?? null));
                $sheet->setCellValue('E' . $rowStd, $formatStd($stdDraw->std_warna ?? null));
                $sheet->setCellValue('F' . $rowStd, $formatStd($stdDraw->std_konsentrasi ?? null));
                $sheet->setCellValue('G' . $rowStd, $formatStd($stdDraw->std_ph ?? null));
                $sheet->setCellValue('H' . $rowStd, $formatStd($stdDraw->std_temp ?? null));

                // Suntik Standar Annealing 1 (Kolom I - N)
                $sheet->setCellValue('I' . $rowStd, $formatStd($stdAnn->std_tipe ?? null));
                $sheet->setCellValue('J' . $rowStd, $formatStd($stdAnn->std_supplier ?? null));
                $sheet->setCellValue('K' . $rowStd, $formatStd($stdAnn->std_warna ?? null));
                $sheet->setCellValue('L' . $rowStd, $formatStd($stdAnn->std_konsentrasi ?? null));
                $sheet->setCellValue('M' . $rowStd, $formatStd($stdAnn->std_ph ?? null));
                $sheet->setCellValue('N' . $rowStd, $formatStd($stdAnn->std_temp ?? null));

                if ($targetKeyword === 'BAK6') {
                    // Suntik Standar Annealing 2 (Kolom O - T) khusus Bak 6
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

                    // Drawing Aktual (Kolom C - H)
                    $sheet->setCellValue('C' . $rowData, $check->draw_type);
                    $sheet->setCellValue('D' . $rowData, $check->draw_supplier);
                    $sheet->setCellValue('E' . $rowData, $check->draw_warna);
                    $sheet->setCellValue('F' . $rowData, $check->draw_konsentrasi);
                    $sheet->setCellValue('G' . $rowData, $check->draw_ph);
                    $sheet->setCellValue('H' . $rowData, $check->draw_temp);

                    // Annealing 1 Aktual (Kolom I - N)
                    $sheet->setCellValue('I' . $rowData, $check->ann_type);
                    $sheet->setCellValue('J' . $rowData, $check->ann_supplier);
                    $sheet->setCellValue('K' . $rowData, $check->ann_warna);
                    $sheet->setCellValue('L' . $rowData, $check->ann_konsentrasi);
                    $sheet->setCellValue('M' . $rowData, $check->ann_ph);
                    $sheet->setCellValue('N' . $rowData, $check->ann_temp);

                    if ($targetKeyword === 'BAK6') {
                        // Annealing 2 Aktual untuk Bak 6 (Kolom O - T)
                        $sheet->setCellValue('O' . $rowData, $check->ann_type_2 ?? '-');
                        $sheet->setCellValue('P' . $rowData, $check->ann_supplier_2 ?? '-');
                        $sheet->setCellValue('Q' . $rowData, $check->ann_warna_2 ?? '-');
                        $sheet->setCellValue('R' . $rowData, $check->ann_konsentrasi_2 ?? '-');
                        $sheet->setCellValue('S' . $rowData, $check->ann_ph_2 ?? '-');
                        $sheet->setCellValue('T' . $rowData, $check->ann_temp_2 ?? '-');

                        // Diperiksa & Keterangan di Kolom U & V
                        $sheet->setCellValue('U' . $rowData, $check->diperiksa_oleh);
                        $sheet->setCellValue('V' . $rowData, $check->keterangan);
                    } else {
                        // Diperiksa & Keterangan di Kolom O & P (Bak Normal)
                        $sheet->setCellValue('O' . $rowData, $check->diperiksa_oleh);
                        $sheet->setCellValue('P' . $rowData, $check->keterangan);
                    }

                    $rowData++;
                }
            }
        }

        // 5. Download File
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
}
