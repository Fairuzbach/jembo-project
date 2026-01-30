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

        // 1. QUERY DASAR
        $query = WorkOrderFacilities::with(['user', 'machine', 'technicians']);

        // =================================================================
        // LOGIKA HAK AKSES (VISIBILITY) - [FIXED]
        // =================================================================

        // A. KELOMPOK FACILITY / ADMIN (Lihat Semua)
        $isFacilityOrAdmin = ($user->divisi === 'Facility') ||
            str_contains($user->role, 'fh.') ||
            ($user->role === 'super.admin');

        if ($isFacilityOrAdmin) {
            // Admin Facility tidak perlu lihat draft yang belum disetujui atasan
            $query->where('status', '!=', 'waiting_approval');
        }

        // B. KELOMPOK USER (Manager, SPV, Staff)
        else {
            $uDiv   = strtoupper($user->divisi ?? ''); // Normalisasi Upper
            $uLevel = strtoupper($user->job_level ?? '');
            $uRole  = $user->role;

            // Cek Level Jabatan
            $isManager = str_contains($uLevel, 'MANAGER') || str_contains($uLevel, 'HEAD');
            $isSpv     = str_contains($uLevel, 'SPV') || str_contains($uLevel, 'SUPERVISOR') || str_contains($uRole, 'admin');

            $query->where(function ($q) use ($user, $uDiv, $isManager, $isSpv) {

                // 1. Selalu tampilkan tiket buatan sendiri (Apapun jabatannya)
                $q->where('requester_id', $user->id);

                // 2. Tampilkan Tiket Bawahan (Logic Hierarki)
                if ($isManager) {
                    // [LOGIC MANAGER] - FUZZY MATCH
                    // Manager MV (Plant D) -> Boleh lihat 'Plant D' DAN 'Plant D - CCV'
                    // Maka kita pakai LIKE
                    if (!empty($user->divisi)) {
                        $q->orWhere('plant', 'LIKE', '%' . $user->divisi . '%');
                    }
                } elseif ($isSpv) {
                    // [LOGIC SUPERVISOR] - STRICT MATCH (PERBAIKAN UTAMA DISINI)
                    // Supervisor MV (Plant D) -> HANYA boleh lihat 'Plant D' (Exact Match)
                    // Tidak boleh intip 'Plant D - CCV'
                    if (!empty($user->divisi)) {
                        $q->orWhere('plant', '=', $user->divisi); // Pake Sama Dengan (=), Jangan LIKE
                    }
                }
            });
        }

        // =================================================================
        // FILTER SEARCH & LAINNYA
        // =================================================================

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_num', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('plant_id')) {
            $filterPlant = \App\Models\Engineering\Plant::find($request->plant_id);
            if ($filterPlant) {
                $query->where('plant', $filterPlant->name);
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // =================================================================
        // HITUNG STATISTIK (EFISIENSI)
        // =================================================================
        // Jangan tulis ulang logic visibility! Clone saja query utama yang sudah difilter di atas.

        $statsQuery = clone $query;
        // Note: Query utama belum dipaginate, jadi aman di-clone untuk count.

        $countTotal           = (clone $statsQuery)->count();
        // Cek Typo Status di Database Anda: 'waiting_facility_approval' vs 'waiting_facilities_approval'
        // Sesuaikan string di bawah ini dengan isi database:
        $countPending         = (clone $statsQuery)->where('status', 'waiting_approval')->count();
        $countWaitingApproval = (clone $statsQuery)->where('status', 'waiting_facility_approval')->count();
        $countProgress        = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countDone            = (clone $statsQuery)->where('status', 'completed')->count();


        // EKSEKUSI DATA (PAGINATION)
        $workOrders = $query->latest()->paginate(10)->withQueryString();
        $pageIds = $workOrders->pluck('id')->toArray();

        // DATA PENDUKUNG
        $excludedPlants = ['PE', 'QC FO', 'HC', 'Plant F', 'FA', 'IT', 'Sales', 'Marketing', 'RM Office', 'RM 1', 'RM 2', 'RM 3', 'RM 5', 'MT', 'FH', 'FO', 'QR', 'Plant Tools', 'Gudang Jadi', 'QC Lab', 'SS', 'Konstruksi'];
        $listPlants = \App\Models\Engineering\Plant::whereNotIn('name', $excludedPlants)->get();
        $machines = \App\Models\Engineering\Machine::all();
        $technicians = \App\Models\FacilityTech::all();

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

    public function dashboard(\Illuminate\Http\Request $request)
    {
        if (!in_array(Auth::user()->role, ['fh.admin'])) {
            return redirect()->route('fh.index')->with('error', 'Unauthorized Access');
        }

        $data = $this->facilityService->getDashboardStats($request);


        return view('Division.Facilities.dashboard', $data);
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
            $this->facilityService->updateStatus($id, $request->all());

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
