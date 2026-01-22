<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Facilities\WorkOrderFacilities;
use App\Models\Engineering\Plant;
use App\Models\Engineering\Machine;
use App\Models\FacilityTech;
use App\Models\User;
use App\Services\Facility\FacilityService;
use App\Http\Requests\Facility\StoreFacilityRequest;

class FacilitiesController extends Controller
{
    protected $facilityService;

    public function __construct(FacilityService $facilityService)
    {
        $this->facilityService = $facilityService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. QUERY UTAMA (Load Relasi)
        // Hapus 'plant' dari with() karena tabel WO tidak punya plant_id (relasi error)
        $query = WorkOrderFacilities::with(['user', 'machine', 'technicians']);

        // --- LOGIKA HAK AKSES (VISIBILITY) ---

        // A. KELOMPOK FACILITY / ADMIN (Bisa Lihat Semua)
        $isFacilityOrAdmin = ($user->divisi === 'Facility') ||
            str_contains($user->role, 'fh.') ||
            ($user->role === 'super.admin');

        if ($isFacilityOrAdmin) {
            // Tidak ada filter, tampilkan semua
        }

        // B. KELOMPOK BOSS LOKAL (Manager / SPV / Admin Unit)
        else {
            // Ambil data user, konversi ke huruf kecil
            $jabatan = strtolower($user->jabatan ?? '');
            $role    = strtolower($user->role ?? '');

            // Cek apakah user memiliki jabatan Boss
            $isBoss = str_contains($jabatan, 'manager') ||
                str_contains($jabatan, 'spv') ||
                str_contains($jabatan, 'supervisor') ||
                str_contains($role, 'admin'); // Termasuk 'mv.admin'

            if ($isBoss) {
                $query->where(function ($q) use ($user) {
                    // 1. Selalu tampilkan tiket buatan sendiri
                    $q->where('requester_id', $user->id);

                    // 2. Tampilkan tiket bawahan (Cek kesamaan teks Nama Plant dengan Divisi User)
                    if (!empty($user->divisi)) {
                        $cleanDivisi = strtolower(trim($user->divisi));
                        // Logic: Kolom 'plant' (text) mengandung kata dari divisi user
                        $q->orWhereRaw('LOWER(plant) LIKE ?', ['%' . $cleanDivisi . '%']);
                    }
                });
            }
            // C. STAFF BIASA (Hanya Lihat Punya Sendiri)
            else {
                $query->where('requester_id', $user->id);
            }
        }

        // --- FILTER SEARCH ---
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_num', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // --- FILTER PLANT (Dropdown) ---
        // Karena tabel pakai Text 'plant', kita harus konversi ID dropdown jadi Text
        if ($request->has('plant_id') && $request->plant_id != '') {
            $filterPlant = \App\Models\Engineering\Plant::find($request->plant_id);
            if ($filterPlant) {
                $query->where('plant', $filterPlant->name);
            }
        }

        // --- FILTER LAINNYA ---
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // --- EKSEKUSI DATA UTAMA ---
        $workOrders = $query->latest()->paginate(10)->withQueryString();


        // --- DATA PENDUKUNG VIEW ---
        // Pastikan Model Plant sudah di-use atau panggil full namespace
        $excludedPlants = ['PE', 'QC FO', 'HC', 'Plant F', 'FA', 'IT', 'Sales', 'Marketing', 'RM Office', 'RM 1', 'RM 2', 'RM 3', 'RM 5', 'MT', 'FH', 'FO', 'QR', 'Plant Tools', 'Gudang Jadi'];
        $listPlants = \App\Models\Engineering\Plant::whereNotIn('name', $excludedPlants)->get();

        $machines = \App\Models\Engineering\Machine::all();
        $technicians = \App\Models\FacilityTech::all();
        $pageIds = $workOrders->pluck('id')->toArray();


        // --- HITUNG STATISTIK (LOGIKA SAMA DENGAN DI ATAS) ---
        $statsQuery = WorkOrderFacilities::query();

        // Terapkan filter hak akses yang sama ke statistik
        if (!$isFacilityOrAdmin) {
            $jabatan = strtolower($user->jabatan ?? '');
            $role    = strtolower($user->role ?? '');
            $isBoss = str_contains($jabatan, 'manager') || str_contains($jabatan, 'spv') || str_contains($jabatan, 'supervisor') || str_contains($role, 'admin');

            if ($isBoss) {
                $statsQuery->where(function ($q) use ($user) {
                    $q->where('requester_id', $user->id);
                    if (!empty($user->divisi)) {
                        $cleanDivisi = strtolower(trim($user->divisi));
                        $q->orWhereRaw('LOWER(plant) LIKE ?', ['%' . $cleanDivisi . '%']);
                    }
                });
            } else {
                $statsQuery->where('requester_id', $user->id);
            }
        }

        // Hitung Angka
        $countTotal = (clone $statsQuery)->count();
        $countPending = (clone $statsQuery)->where('status', 'waiting_approval')->count();
        $countWaitingApproval = (clone $statsQuery)->where('status', 'waiting_facilities_approval')->count();
        $countProgress = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countDone = (clone $statsQuery)->where('status', 'completed')->count();

        // --- RETURN VIEW ---
        return view('Division.Facilities.index', compact(
            'workOrders',
            'listPlants',
            'machines',
            'technicians',
            'countTotal',
            'countPending',
            'countWaitingApproval',
            'countProgress',
            'countDone',
            'pageIds'
        ));
    }

    public function dashboard()
    {
        // Hanya Admin yang boleh akses dashboard analitik
        if (!in_array(Auth::user()->role, ['fh.admin', 'super.admin'])) {
            return redirect()->route('fh.index')->with('error', 'Unauthorized access');
        }

        // Contoh data dashboard (bisa dikembangkan di Service)
        $stats = [
            'total' => WorkOrderFacilities::count(),
            'completed' => WorkOrderFacilities::where('status', 'completed')->count(),
            'pending' => WorkOrderFacilities::where('status', 'pending')->count(),
            'in_progress' => WorkOrderFacilities::where('status', 'in_progress')->count(),

        ];

        $ganttData = $this->facilityService->getGanttChartData();



        return view('Division.Facilities.dashboard', compact('stats'));
    }

    public function store(StoreFacilityRequest $request)
    {
        try {
            $this->facilityService->createTicket($request->validated(), $request->file('photo'));
            return redirect()->route('fh.index')->with('success', 'Ticket created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create ticket: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'status' => 'required|string',
            'start_date' => 'nullable|date',
            'facility_tech_ids' => 'nullable|array',
            'facility_tech_ids.*' => 'exists:facility_teches,id',
        ]);

        try {
            $ticket = WorkOrderFacilities::findOrFail($id);

            // Update Data Tiket
            $ticket->update([
                'status' => $request->status,
                'start_date' => $request->start_date,
            ]);

            // Sync Teknisi (Pivot Table)
            if ($request->has('facility_tech_ids')) {
                $ticket->technicians()->sync($request->facility_tech_ids);
            }

            return redirect()->back()->with('success', 'Status updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating status: ' . $e->getMessage());
        }
    }

    // --- APPROVAL METHODS ---

    public function approve(Request $request, $id)
    {
        $result = $this->facilityService->approveTicket($id);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);

        $result = $this->facilityService->rejectTicket($id, $request->reason);

        return back()->with('success', $result['message']);
    }

    // Export Excel (Jika dipanggil via route terpisah atau query param)
    public function export(Request $request)
    {
        // Logika export bisa ditaruh di sini atau di index
        return redirect()->route('fh.index');
    }
}
