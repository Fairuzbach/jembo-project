<?php

namespace App\Http\Controllers\Engineering;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Imports\OperatorImport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Engineering\WorkOrderEngineering;
use App\Models\Engineering\Plant;
use App\Models\Engineering\EngineerTech;
use App\Models\Engineering\ImprovementStatus;
use App\Models\Engineering\ParameterImprovement;
use App\Models\Engineering\EngCompoundCheck;
use App\Models\Engineering\EngCompoundStandard;
use App\Http\Controllers\Controller;

class WorkOrderEngineeringController extends Controller
{

    public function index(Request $request)
    {
        // $queryUser = WorkOrderEngineering::query();
        $query = WorkOrderEngineering::latest();
        $user = Auth::user();
        $statsQuery = WorkOrderEngineering::query();

        if ($user->role !== 'eng.admin') {
            $query->where('requester_id', $user->id);
            $statsQuery->where('requester_id', $user->id);
        }
        $countTotal = (clone $statsQuery)->count();
        $countPending = (clone $statsQuery)->where('improvement_status', 'OPEN')->count();
        $countInProgress = (clone $statsQuery)->where('improvement_status', 'WIP')->count();
        $countCompleted = (clone $statsQuery)->where('improvement_status', 'CLOSED')->count();



        // 1. SEARCH
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_num', 'like', '%' . $search . '%')
                    ->orWhere('machine_name', 'like', '%' . $search . '%')->orWhere('plant', 'like', '%' . $search . '%');
            });
        }

        // 2. FILTER STATUS (Disesuaikan dengan improvement_status)
        if ($request->filled('improvement_status')) {
            $query->where('improvement_status', $request->improvement_status);
        }
        // Fallback untuk jaga-jaga jika ada link lama yang pakai work_status
        elseif ($request->filled('work_status')) {
            $query->where('improvement_status', $request->work_status);
        }

        // 3. PAGINATION
        $workOrders = $query->with('requester')
            ->paginate(15, ['*'], 'wo_page')
            ->withQueryString();

        $compoundChecks = EngCompoundCheck::with(['plant', 'pemeriksa'])
            ->select(
                'plant_id',
                'tanggal_cek',
                'diperiksa_oleh',
                \DB::raw('COUNT(id) as jumlah_mesin'), // Menghitung berapa mesin yg dicek di laporan ini
                \DB::raw('MAX(created_at) as created_at') // Mengambil waktu submit terakhir
            )
            ->groupBy('plant_id', 'tanggal_cek', 'diperiksa_oleh')
            ->orderBy('tanggal_cek', 'desc')
            ->paginate(15, ['*'], 'comp_page');

        $allStandards = EngCompoundStandard::all();
        $stdPlantA = $allStandards->where('plant', 'Plant A')->groupBy('kode_mesin');
        $stdAutowire = $allStandards->where('plant', 'Autowire')->groupBy('kode_mesin');

        // Data Pendukung
        $plants = Plant::with('machines')->get();
        $technicians = EngineerTech::all();
        // $improvementStatuses = ImprovementStatus::all();
        $improvementParameters = ParameterImprovement::all();

        return view('Division.Engineering.Engineering', compact(
            'workOrders',
            'plants',
            'technicians',
            'improvementParameters',
            'countTotal',
            'countPending',
            'countInProgress',
            'countCompleted',
            'compoundChecks',
            'stdPlantA',
            'stdAutowire'
        ));
    }

    public function storeCompound(Request $request)
    {
        // Validasi Dasar
        $request->validate([
            'plant' => 'required|in:Plant A,Autowire',
        ]);

        $plantName = $request->plant;
        $keterangan = $request->keterangan;

        // 1. Ambil Nama Operator dari input AJAX (Hidden Input)
        $diperiksaOleh = $request->nama_pemeriksa;
        if (!$diperiksaOleh || $diperiksaOleh == '........................' || $diperiksaOleh == 'DATA TIDAK DITEMUKAN') {
            $diperiksaOleh = auth()->user()->name;
        }

        // 2. Ambil Nama Foreman dari user yang sedang login
        $namaForeman = auth()->user()->name;

        // Tentukan ID Plant berdasarkan database Anda
        $plantId = ($plantName === 'Plant A') ? 1 : 2;

        DB::beginTransaction();

        try {
            if ($plantName === 'Plant A') {
                // Mapping ID mesin sesuai database Anda untuk masing-masing BAK
                $machineMap = [
                    'bak_1' => 1,  // HD 10 C
                    'bak_2' => 3,  // MD 1
                    'bak_3' => 52, // QDMD Deyang
                    'bak_4' => 53, // Multi 2 Samp
                    'bak_5' => 54, // Multi 1 Samp
                    'bak_6' => 2,  // Twin RBD Cu
                ];

                $tanggal = $request->plant_a_tanggal;
                $dataBak = $request->plant_a;

                foreach ($dataBak as $bakKey => $data) {
                    // Filter baris kosong: cek apakah ada data Drawing atau Annealing yang diisi
                    $hasDrawing = collect($data)->only(['draw_type', 'draw_supplier', 'draw_warna', 'draw_konsentrasi', 'draw_ph', 'draw_temp'])->filter(fn($val) => $val !== null && $val !== '')->isNotEmpty();
                    $hasAnnealing = collect($data)->only(['ann_type', 'ann_supplier', 'ann_warna', 'ann_konsentrasi', 'ann_ph', 'ann_temp'])->filter(fn($val) => $val !== null && $val !== '')->isNotEmpty();

                    if (!$hasDrawing && !$hasAnnealing) {
                        continue;
                    }

                    // Format Satuan
                    $draw_kons = $data['draw_konsentrasi'] ? $data['draw_konsentrasi'] . '%' : null;
                    $draw_temp = $data['draw_temp'] ? $data['draw_temp'] . '°C' : null;
                    $ann_kons  = $data['ann_konsentrasi'] ? $data['ann_konsentrasi'] . '%' : null;
                    $ann_temp  = $data['ann_temp'] ? $data['ann_temp'] . '°C' : null;

                    EngCompoundCheck::create([
                        'plant_id'       => $plantId,
                        'machine_id'     => $machineMap[$bakKey] ?? null,
                        'tanggal_cek'    => $tanggal,
                        'keterangan'     => $keterangan,
                        'diperiksa_oleh' => $diperiksaOleh,
                        'diketahui_oleh' => $namaForeman,
                        'status'         => 'waiting_approval',

                        // Kolom Drawing
                        'draw_type'        => $data['draw_type'] ?? null,
                        'draw_supplier'    => $data['draw_supplier'] ?? null,
                        'draw_warna'       => $data['draw_warna'] ?? null,
                        'draw_konsentrasi' => $draw_kons,
                        'draw_ph'          => $data['draw_ph'] ?? null,
                        'draw_temp'        => $draw_temp,

                        // Kolom Annealing
                        'ann_type'         => $data['ann_type'] ?? null,
                        'ann_supplier'     => $data['ann_supplier'] ?? null,
                        'ann_warna'        => $data['ann_warna'] ?? null,
                        'ann_konsentrasi'  => $ann_kons,
                        'ann_ph'           => $data['ann_ph'] ?? null,
                        'ann_temp'         => $ann_temp,
                    ]);
                }
            } elseif ($plantName === 'Autowire') {
                $autowireMachineId = 55;
                $dataCek = $request->autowire;

                foreach ($dataCek as $cekKey => $data) {
                    // Pastikan ada tanggal, jika tidak maka skip
                    if (empty($data['tanggal'])) continue;

                    // Format Satuan untuk Autowire
                    $draw_kons = isset($data['draw_konsentrasi']) && $data['draw_konsentrasi'] !== '' ? $data['draw_konsentrasi'] . '%' : null;
                    $draw_temp = isset($data['draw_temp']) && $data['draw_temp'] !== '' ? $data['draw_temp'] . '°C' : null;
                    $ann_kons  = isset($data['ann_konsentrasi']) && $data['ann_konsentrasi'] !== '' ? $data['ann_konsentrasi'] . '%' : null;
                    $ann_temp  = isset($data['ann_temp']) && $data['ann_temp'] !== '' ? $data['ann_temp'] . '°C' : null;

                    EngCompoundCheck::create([
                        'plant_id'       => $plantId,
                        'machine_id'     => $autowireMachineId,
                        'tanggal_cek'    => $data['tanggal'],
                        'keterangan'     => $keterangan,
                        'diperiksa_oleh' => $diperiksaOleh,
                        'diketahui_oleh' => $namaForeman,
                        'status'         => 'waiting_approval',

                        // Kolom Drawing Autowire
                        'draw_type'        => $data['draw_type'] ?? null,
                        'draw_supplier'    => $data['draw_supplier'] ?? null,
                        'draw_warna'       => $data['draw_warna'] ?? null,
                        'draw_konsentrasi' => $draw_kons,
                        'draw_ph'          => $data['draw_ph'] ?? null,
                        'draw_temp'        => $draw_temp,

                        // Kolom Annealing Autowire
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
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function editCompound($plant_id, $tanggal)
    {
        $plant = Plant::findOrFail($plant_id);

        // Ambil semua data pengecekan pada tanggal dan plant tersebut
        $checks = EngCompoundCheck::where('plant_id', $plant_id)
            ->whereDate('tanggal_cek', $tanggal)
            ->get()
            ->keyBy('machine_id'); // Jadikan ID Mesin sebagai key array agar mudah dipanggil di Blade

        // Tentukan nama Plant (untuk logika di Blade)
        $plantName = ($plant->name === 'Plant A - Autowire' || str_contains(strtolower($plant->name), 'autowire')) ? 'Autowire' : 'Plant A';

        $allStandards = EngCompoundStandard::all();

        $stdPlantA = $allStandards->where('plant', 'Plant A')->groupBy('kode_mesin');
        $stdAutowire = $allStandards->where('plant', 'Autowire')->groupBy('kode_mesin');

        return view('Division.Engineering.partials.modals.edit-compound', compact('plant', 'plantName', 'tanggal', 'checks', 'stdPlantA', 'stdAutowire'));
    }

    public function updateCompound(Request $request, $plant_id, $tanggal)
    {
        // Validasi dasar
        $request->validate([
            'plant' => 'required|in:Plant A,Autowire',
        ]);

        $plantName = $request->plant;
        $keterangan = $request->keterangan;
        $diperiksaOleh = $request->nama_pemeriksa ?? auth()->user()->name;
        $diperiksaOleh = auth()->id();

        DB::beginTransaction();

        try {
            // ==========================================
            // LOGIKA UPDATE: PLANT A
            // ==========================================
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
                            // 3. FITUR HAPUS: Jika saat diedit isi tab ini dikosongkan semua, hapus data lama dari DB!
                            EngCompoundCheck::where('plant_id', $plant_id)
                                ->where('machine_id', $machineMap[$bakKey] ?? null)
                                ->whereDate('tanggal_cek', $tglCek)
                                ->delete();
                        }
                    }
                }
            }
            // ==========================================
            // LOGIKA UPDATE: AUTOWIRE
            // ==========================================
            elseif ($plantName === 'Autowire') {

                $autowireMachineId = 55;
                $dataCek = $request->autowire;

                if ($dataCek) {
                    foreach ($dataCek as $cekKey => $data) {
                        // Ambil tanggal spesifik dari masing-masing tab Autowire
                        $tglCekAuto = $data['tanggal'] ?? null;

                        // Jika tidak ada tanggal di tab ini, lewati
                        if (empty($tglCekAuto)) continue;

                        // Cek apakah ada parameter teknis yang diisi
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

    public function store(Request $request)
    {
        $request->validate([
            'report_date' => 'required|date',
            'report_time' => 'required',
            'plant' => 'required|string',
            'engineer_tech' => 'required|array|min:1|max:5',
            'plant' => 'required|string',
            'machine_name' => 'required|string',
            'damaged_part' => 'required|string',
            'improvement_parameters' => 'required|string',
            'kerusakan_detail' => 'required|string',
            'priority' => 'nullable',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'initial_status' => 'required|in:OPEN,WIP,CLOSED',
        ], [
            'engineer_tech.required' => 'Wajib memilih minimal 1 engineer (Nama sendiri)'
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('work_orders', 'public');
        }

        $engineerString = implode(',', $request->engineer_tech);

        $dateCode = date('Ymd');
        $prefix = 'engIO-' . $dateCode . '-';
        $lastWorkOrder = WorkOrderEngineering::where('ticket_num', 'like', $prefix . '%')->orderBy('id', 'desc')->first();

        if ($lastWorkOrder) {
            $lastNumber = (int) substr($lastWorkOrder->ticket_num, -3);
            $newSequence = $lastNumber + 1;
        } else {
            $newSequence = 0;
        }
        $ticketNum = $prefix . sprintf('%03d', $newSequence);

        $finishedDate = null;
        if ($request->initial_status == 'CLOSED') {
            $finishedDate = Carbon::now();
        }

        WorkOrderEngineering::create([
            'requester_id' => auth()->id(),
            'ticket_num' => $ticketNum,
            'report_date' => $request->report_date,
            'report_time' => $request->report_time,
            'engineer_tech' => $engineerString,
            'plant' => $request->plant,
            'machine_name' => $request->machine_name,
            'damaged_part' => $request->damaged_part,
            'improvement_parameters' => $request->improvement_parameters,
            'kerusakan' => $request->damaged_part,
            'kerusakan_detail' => $request->kerusakan_detail,
            'priority' => $request->priority ?? 'medium',

            // Set Default Status
            'improvement_status' => $request->initial_status,
            'finished_date' => $finishedDate,

            'photo_path' => $photoPath,
        ]);

        return redirect()->route('engineering.wo.index')->with('success', 'Laporan berhasil dibuat dengan status !' . $request->initial_status);
    }
    public function standardsIndex()
    {
        // Mengambil semua data standar, diurutkan berdasarkan Plant dan Mesin
        $standards = EngCompoundStandard::orderBy('plant', 'asc')
            ->orderBy('kode_mesin', 'asc')
            ->orderBy('proses', 'desc') // desc supaya Drawing tampil duluan dari Annealing
            ->get();

        return view('Division.Engineering.standards-index', compact('standards'));
    }

    // Fungsi untuk mengupdate data standar ke database
    public function standardsUpdate(Request $request, $id)
    {
        $standard = EngCompoundStandard::findOrFail($id);

        // Update hanya kolom-kolom nilainya saja
        $standard->update([
            'std_tipe'        => $request->std_tipe,
            'std_supplier'    => $request->std_supplier,
            'std_warna'       => $request->std_warna,
            'std_konsentrasi' => $request->std_konsentrasi,
            'std_ph'          => $request->std_ph,
            'std_temp'        => $request->std_temp,
        ]);

        return redirect()->back()->with('success', 'Nilai standar berhasil diperbarui!');
    }
    public function updateStatus(Request $request, $id)
    {
        $ticket = WorkOrderEngineering::findOrFail($id);
        $user = Auth::user();

        //ROLE USER LOGIC
        if ($ticket->requester_id == $user->id && $user->role !== 'eng.admin') {
            $request->validate([
                'status' => 'required|in:WIP,CLOSED'
            ]);
            $ticket->improvement_status = $request->status;

            if ($request->status == 'CLOSED') {
                $ticket->finished_date = Carbon::now();
            } else {
                $ticket->finished_date = null;
            }
            $ticket->save();
            return redirect()->back()->with('success', 'Status laporan berhasil diupdate !' . $request->status);
        }

        //ROLE ENG.ADMIN LOGIC
        if ($user->role == 'eng.admin') {
            $ticket->improvement_status = $request->status;
            if ($request->status == 'CLOSED') {
                $ticket->finished_date = Carbon::now();
            } else if ($request->status == 'WIP' || $request->status == 'OPEN') {
                $ticket->finished_date = null;
            }
            $ticket->save();
            return redirect()->back()->with('success', 'Status telah diperbarui oleh Admin!');
        }

        if ($request->action == 'cancel') {
            if ($ticket->requester_id == $user->id && $ticket->improvement_status == 'pending') {
                $ticket->improvement_status = 'cancelled';
                $ticket->save();
                return redirect()->back()->with('success', 'Report berhasil dibatalkan!');
            }
            abort(403, 'Aksi tidak diizinkan');
        }

        if ($user->role == 'eng.admin') {
            $request->validate([
                'status' => 'required|in:OPEN,WIP,CLOSED,CANCELLED'
            ]);
            $ticket->improvement_status = $request->status;
            $ticket->save();

            return redirect()->back()->with('success', 'Tiket berhasil diperbarui!');
        }
        abort(403, 'Anda tidak memiliki akses.');
    }

    public function update(Request $request, WorkOrderEngineering $workOrder)
    {
        // PERBAIKAN DI SINI: Sesuaikan validasi dengan input view (improvement_status)
        $request->validate([
            'improvement_status' => 'required|in:OPEN,WIP,CLOSED,CANCELLED',
            'finished_date' => 'nullable|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'engineer_tech' => 'nullable|string|max:255',
            'maintenance_note' => 'nullable|string',
            'repair_solution' => 'required|string',
            'sparepart' => 'nullable|string',
        ]);

        $workOrder->update([
            // Sesuaikan mapping input ke database
            'improvement_status' => $request->improvement_status,

            'finished_date' => $request->finished_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'engineer_tech' => $request->engineer_tech,
            'maintenance_note' => $request->maintenance_note,
            'repair_solution' => $request->repair_solution,
            'sparepart' => $request->sparepart,
        ]);

        return redirect()->route('engineering.wo.index')->with('success', 'Status laporan #' . $workOrder->ticket_num . ' berhasil diperbarui!');
    }

    public function export(Request $request)
    {
        if ($request->filled('ticket_ids')) {
            $ids = explode(',', $request->ticket_ids);
            $data = WorkOrderEngineering::with('requester')
                ->whereIn('id', $ids)
                ->orderBy('report_date', 'asc')
                ->get();
            $fileName = 'Laporan_engIO_Selected_' . date('Ymd_His') . '.csv';
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $data = WorkOrderEngineering::with('requester')
                ->whereBetween('report_date', [$startDate, $endDate])
                ->orderBy('report_date', 'asc')
                ->orderBy('report_time', 'asc')
                ->get();

            $fileName = 'Laporan_engIO_' . $startDate . '_sd_' . $endDate . '.csv';
        }

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'No Tiket',
            'Tanggal Lapor',
            'Jam',
            'ID Pelapor',
            'Nama Pelapor',
            'Divisi Pelapor',
            'Plant',
            'Mesin',
            'Request',
            'Prioritas',
            'Status Improvement',
            'Engineer Tech',
            'Uraian Improvement',
            'Sparepart',
            'Tanggal Selesai'
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->ticket_num,
                    \Carbon\Carbon::parse($row->report_date)->format('Y-m-d'),
                    \Carbon\Carbon::parse($row->report_time)->format('H:i'),
                    $row->requester_id,
                    $row->requester->name ?? 'NO NAME',
                    $row->requester->divisi,
                    $row->plant,
                    $row->machine_name,
                    $row->damaged_part,
                    $row->priority,

                    // Pastikan export mengambil kolom improvement_status
                    $row->improvement_status,

                    $row->engineer_tech,
                    $row->kerusakan_detail,
                    $row->sparepart,
                    $row->finished_date ? \Carbon\Carbon::parse($row->finished_date)->format('Y-m-d') : '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function searchOperator(Request $request)
    {
        // Ambil NIK dari query string
        $nik = $request->query('nik');

        // Cari di database
        $operator = \App\Models\Operator::where('nik', $nik)->first();

        if ($operator) {
            return response()->json([
                'success' => true,
                'name'    => $operator->name
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Operator tidak ditemukan'
        ]);
    }

    public function importOperator(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new OperatorImport, $request->file('file_excel'));

        return redirect()->back()->with('success', 'Data Operator Berhasil Diimport!');
    }
}
