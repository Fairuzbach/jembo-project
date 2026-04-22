<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Facilities\WorkOrderFacilities;
use App\Services\Facility\FacilityService;
use App\Http\Requests\Facility\StoreFacilityRequest;
use App\Exports\FacilitiesExport;
use Maatwebsite\Excel\Facades\Excel;

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

        // 1. QUERY DASAR & EAGER LOAD
        $query = WorkOrderFacilities::with(['user', 'machine', 'technicians']);

        // =================================================================
        // LOGIKA HAK AKSES (VISIBILITY)
        // =================================================================

        // A. KELOMPOK FACILITY / ADMIN
        $isFacilityOrAdmin = ($user->divisi === 'FACILITY') ||
            str_contains($user->role, 'fh.') ||
            ($user->role === 'super.admin');

        if ($isFacilityOrAdmin) {
            $query->where('status', '!=', 'waiting_approval');
        }
        // B. KELOMPOK USER (Manager, SPV, Staff)
        else {
            $uDiv = strtoupper(trim($user->divisi ?? ''));

            $isAutowireAdmin = $user->hasRole('autowire.admin');
            $isCcvAdmin      = $user->hasRole('ccv.admin');
            $isLvAdmin       = $user->hasRole('lv.admin');
            $isMvAdmin       = $user->hasRole('mv.admin');

            $uLevel    = strtoupper($user->job_level ?? '');
            $isManager = str_contains($uLevel, 'MANAGER') || str_contains($uLevel, 'HEAD') || $isLvAdmin || $isMvAdmin;
            $isSpv     = str_contains($uLevel, 'SPV') || str_contains($uLevel, 'SUPERVISOR');

            $query->where(function ($q) use ($user, $uDiv, $isManager, $isSpv, $isAutowireAdmin, $isCcvAdmin) {
                // User selalu bisa lihat tiket sendiri
                $q->where('requester_id', $user->id);

                if ($isAutowireAdmin) {
                    $q->orWhere('plant', 'PLANT A - AUTOWIRE');
                } elseif ($isCcvAdmin) {
                    $q->orWhere('plant', 'PLANT D - CCV');
                } elseif ($isManager) {
                    if (!empty($uDiv)) {
                        $q->orWhere('plant', 'LIKE', '%' . $uDiv . '%');
                    }
                } elseif ($isSpv) {
                    if (!empty($uDiv)) {
                        $q->orWhere('plant', '=', $uDiv);
                    }
                }
            });
        }

        // =================================================================
        // [PENTING] CLONE UNTUK STATISTIK DI SINI (SEBELUM FILTER APAPUN)
        // =================================================================
        // Kita simpan query yang sudah berisi Hak Akses, tapi BELUM ada filter status/search
        // Agar angka di kartu atas tetap menunjukkan Total Global User tersebut.
        $statsQuery = clone $query;

        // =================================================================
        // FILTER SEARCH & LAINNYA (Hanya mempengaruhi Tabel & Export)
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

        // Filter Status (Hanya untuk tabel, tidak boleh merusak statsQuery)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // EXPORT (Menggunakan query yang sudah difilter)
        if ($request->boolean('export')) {
            if ($request->filled('selected_ids')) {
                $ids = explode(',', $request->selected_ids);
                $query->whereIn('id', $ids); // Override query filter jika ada selected IDs
            }
            return Excel::download(new FacilitiesExport($query), 'work-orders-facility.xlsx');
        }

        // =================================================================
        // HITUNG STATISTIK (MENGGUNAKAN $statsQuery YANG BERSIH)
        // =================================================================

        $countTotal           = (clone $statsQuery)->count();
        $countPending         = (clone $statsQuery)->where('status', 'waiting_approval')->count();
        $countWaitingApproval = (clone $statsQuery)->where('status', 'waiting_facility_approval')->count();
        $countProgress        = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countDone            = (clone $statsQuery)->where('status', 'completed')->count();

        // =================================================================
        // EKSEKUSI DATA TABEL (MENGGUNAKAN $query YANG TER-FILTER)
        // =================================================================
        $workOrders = $query->latest()->paginate(10)->withQueryString();
        $pageIds    = $workOrders->pluck('id')->toArray();

        // DATA PENDUKUNG
        $excludedPlants = ['pe', 'QC FO', 'HC', 'Plant F', 'FA', 'IT', 'Sales', 'Marketing', 'RM Office', 'RM 1', 'RM 2', 'RM 3', 'RM 5', 'MT', 'FH', 'FO', 'QR', 'Plant Tools', 'Gudang Jadi', 'QC Lab', 'Konstruksi', 'GA - JAKARTA', 'GA - TANGERANG', 'QC LV', 'QC MV', 'JEMBO ENERGINDO', 'ACCOUNTING', 'Gudang Scrap', 'Workshop Electric', 'SS', 'PP'];
        $listPlants     = \App\Models\Engineering\Plant::whereNotIn('name', $excludedPlants)->get();
        $machines       = \App\Models\Engineering\Machine::all();
        $technicians    = \App\Models\FacilityTech::all();

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


        return view('Division.Facilities.Dashboard', $data);
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
