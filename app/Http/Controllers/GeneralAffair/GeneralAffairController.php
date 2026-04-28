<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// --- MODELS ---
use App\Models\User;
use App\Models\Engineering\Plant;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use App\Models\GeneralAffair\Category;
use App\Http\Requests\GA\StoreWorkOrderRequest;
use App\Http\Requests\GA\ProcessTicketRequest;
use App\Http\Requests\GA\UpdateStatusRequest;
use App\Services\GeneralAffair\WorkOrderService;
use App\Services\GeneralAffair\DashboardService;

// --- EXPORT ---
use App\Exports\WorkOrderExport;
use Maatwebsite\Excel\Facades\Excel;

class GeneralAffairController extends Controller
{
    protected $gaService;
    protected $dashboardService;
    // Constructor injection 
    public function __construct(
        WorkOrderService $gaService,
        DashboardService $dashboardService
    ) {
        $this->gaService = $gaService;
        $this->dashboardService = $dashboardService;
    }

    public function checkEmployee(Request $request)
    {
        $employee = \App\Models\User::where('nik', $request->nik)->first();

        if ($employee) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'name' => $employee->name,
                    'department' => $employee->divisi
                ]
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'NIK tidak ditemukan'
            ], 200);
        }
    }


    public function index(Request $request)
    {
        $workOrders = $this->gaService->getWorkOrders(
            $request,
            Auth::user()
        );

        $ganttStartDate = $request->start_date ?? now()->subMonths(2)->format('Y-m-d');

        $rawGanttTickets = WorkOrderGeneralAffair::query()
            ->whereIn('status', ['waiting_approval', 'waiting_approval_ga', 'pending', 'in_progress', 'completed'])
            ->where('created_at', '>=', $ganttStartDate)
            ->latest()
            ->limit(50)
            ->get();

        $ganttData = $this->dashboardService->prepareGanttChart($rawGanttTickets);

        $stats = $this->gaService->getIndexStats(Auth::user());
        $plants = Plant::whereNotIn('name', ['PROCUREMENT', 'QC', 'FO', 'PE', 'QR', 'SS', 'FH', 'RM', 'Plant F'])->get();
        $pageIds = $workOrders->pluck('id')->toArray();
        $categoriesDB = Category::where('status', 'active')->get();


        return view('Division.GeneralAffair.GeneralAffair', array_merge(
            [
                'workOrders' => $workOrders,
                'ganttData' => $ganttData,
                'plants' => $plants,
                'pageIds' => $pageIds,
                'parameters' => config('workorder.parameters'),
                'categories' => config('workorder.categories'),
                'categoriesDB' => $categoriesDB
            ],
            $stats
        ));
    }

    public function dashboard(Request $request)
    {
        $data = $this->dashboardService->getDashboardData($request);
        // dd($data['countPending']);
        return view('Division.GeneralAffair.Dashboard', $data);
    }

    // =========================================================================
    // 3. CRUD ACTIONS (STORE, UPDATE, APPROVE, REJECT)
    // =========================================================================

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        try {
            $result = $this->gaService->createWorkOrder(
                $request->validated(),
                $request->file('photo')
            );

            return redirect()->back()->with('success', $result['message']);
        } catch (\Exception $e) {
            \Log::error('Gagal Store GA: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function getDepartmentsByPlant($plant_id)
    {
        try {
            $plant = Plant::find($plant_id);
            if (!$plant) return response()->json([]);

            $name = trim($plant->name);
            $specificDept = '';

            switch ($name) {
                case 'Plant A':
                case 'Plant C':
                case 'Plant F':
                case 'MC Cable':
                case 'Plant A - Autowire':
                    $specificDept = 'LOW VOLTAGE';
                    break;
                case 'Plant B':
                case 'Plant D':
                case 'Plant D - CCV':
                    $specificDept = 'MEDIUM VOLTAGE';
                    break;
                case 'Plant E':
                case 'FO':
                    $specificDept = 'FIBER OPTIC';
                    break;
                case 'RM 1':
                case 'RM 2':
                case 'RM 3':
                case 'RM 5':
                case 'RM Office':
                    $specificDept = 'SUPPLY CHAIN';
                    break;
                case 'QC FO':
                case 'QC LAB':
                case 'QC LV':
                case 'QC MV':
                case 'QR':
                    $specificDept = 'QUALITY ASSURANCE';
                    break;
                case 'Konstruksi':
                    $specificDept = 'FH';
                    break;
                case 'Workshop Electric':
                case 'MT':
                    $specificDept = 'MAINTENANCE';
                    break;
                case 'Gudang Jadi':
                case 'SS':
                    $specificDept = 'SALES SUPPORT';
                    break;
                case 'Plant Tools':
                case 'PE':
                    $specificDept = 'PROCESS ENGINEERING';
                    break;
                case 'PP':
                    $specificDept = 'PRODUCTION PLANNING';
                    break;
                case 'Planning':
                    $specificDept = 'PRODUCTION PLANNING';
                    break;
                case 'IT':
                    $specificDept = 'INFORMATION TECHNOLOGY';
                    break;
                case 'GA - TANGERANG':
                case 'GA - JAKARTA':
                    $specificDept = 'GENERAL AFFAIR';
                    break;
                case 'FA':
                    $specificDept = 'FINANCE';
                    break;
                case 'JEMBO ENERGINDO':
                    $specificDept = 'JEMBO ENERGINDO';
                    break;
                case 'Marketing':
                    $specificDept = 'MARKETING';
                    break;
                case 'HC':
                    $specificDept = 'HUMAN CAPITAL';
                    break;
                case 'Sales 1':
                    $specificDept = 'SALES 1';
                    break;
                case 'Sales 2':
                    $specificDept = 'SALES 2';
                    break;
                default:
                    $specificDept = 'General';
                    break;
            }

            $departments = [
                $specificDept,
                'ACCOUNTING',
                'FINANCE',
                'FH',
                'FIBER OPTIC',
                'GENERAL AFFAIR',
                'HUMAN CAPITAL',
                'INFORMATION TECHNOLOGY',
                'LOW VOLTAGE',
                'MAINTENANCE',
                'MARKETING',
                'MEDIUM VOLTAGE',
                'PROCESS ENGINEERING',
                'PRODUCTION PLANNING',
                'QUALITY ASSURANCE',
                'SALES 1',
                'SALES 2',
                'SUPPLY CHAIN',
                'SALES SUPPORT',
                'RESEARCH & DEVELOPMENT'
            ];

            return response()->json(array_values(array_unique($departments)));
        } catch (\Exception $e) {
            \Log::error('Error getDepartmentsByPlant: ' . $e->getMessage());
            return response()->json(['General'], 200);
        }
    }

    // --- UTAMA: ACTION APPROVE/REJECT OLEH ADMIN GA ---
    public function processTicket(ProcessTicketRequest $request, $id)
    {

        try {
            // Panggil Service (Return berupa Array sekarang)
            $result = $this->gaService->processTicket(
                $id,
                $request->action,
                $request->reason,
                $request->all()
            );

            // 1. Siapkan Redirect dengan pesan Sukses
            $redirect = redirect()->back()->with('success', $result['message']);

            // 2. Cek apakah ada request untuk menampilkan Alert Peringatan
            // (Ini yang memicu popup "Segera Kerjakan" di view)
            if (!empty($result['alert'])) {
                $redirect->with('alert-action', $result['alert']);
            }

            return $redirect;
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses tiket: ' . $e->getMessage());
        }
    }

    // --- ACTION APPROVE OLEH ADMIN DIVISI LAIN (TECHNICAL) ---
    public function approveByTechnical(Request $request, $id)
    {
        \Log::Emergency("Koneksi Masuk ke Controller! ID: " . $id);
        $action = ($request->action === 'reject' || $request->action === 'decline') ? 'reject' : 'approve';

        try {
            $result = $this->gaService->processTicket(
                $id,
                $action,
                $request->reason
            );

            // Kita ambil message dari array result
            return redirect()->back()->with('success', $result['message']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }


    // --- UPDATE STATUS PROGRESS (OLEH GA ADMIN) ---
    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        try {
            $this->gaService->updateStatus(
                $id,
                $request->validated(),
                $request->file('completion_photo')
            );
            return redirect()->back()->with('success', 'Status berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi Kesalahan: ' . $e->getMessage());
        }
    }


    // =========================================================================
    // 4. EXPORT
    // =========================================================================

    public function export(Request $request)
    {
        $user = Auth::user();

        // Pastikan relasi benar
        $query = WorkOrderGeneralAffair::with(['user', 'plantInfo']);

        // LOGIKA HAK AKSES (Access Control)
        $roleMap = [
            'eng.admin' => ['Engineering', 'engineering', 'ENGINEERING', 'PE'],
            'fh.admin'  => ['Facility', 'FH', 'FACILITY'],
            'mt.admin'  => ['Maintenance', 'maintenance', 'MT'],
            'lv.admin'  => ['Low Voltage', 'LOW VOLTAGE', 'low voltage', 'LV', 'lv'],
            'mv.admin'  => ['Medium Voltage', 'medium voltage', 'MV', 'mv'],
            'qr.admin'  => ['QR', 'qr'],
            'sc.admin'  => ['SC', 'sc'],
            'fo.admin'  => ['FO', 'fo'],
            'ss.admin'  => ['SS', 'ss'],
            'fa.admin'  => ['FA', 'fa'],
            'it.admin'  => ['IT', 'it'],
            'hc.admin'  => ['HC', 'hc'],
            'sales1.admin' => ['Sales 1', 'sales 1'],
            'sales2.admin' => ['Sales 2', 'sales 2'],
            'marketing.admin' => ['Marketing', 'marketing'],
            'pp.admin' => ['Production Planning', 'PP', 'pp']
        ];

        if ($user) {
            // 1. LEVEL SUPER ADMIN / GA ADMIN
            if (
                in_array($user->role, ['ga.admin', 'super.ga.admin', 'admin_ga']) ||
                $user->role === (User::ROLE_GA_ADMIN ?? '')
            ) {
                // Pisahkan logika berdasarkan tab yang sedang dibuka
                if ($request->view === 'internal') {
                    // Export Tab Internal: Hanya ambil data GA
                    $query->where('department', 'LIKE', '%GENERAL AFFAIR%');
                } else {
                    // Export Tab Eksternal (Default): Sembunyikan data GA
                    $query->where('department', 'NOT LIKE', '%GENERAL AFFAIR%');
                }
            }
            // 2. LEVEL ADMIN DEPARTEMEN
            elseif (array_key_exists($user->role, $roleMap)) {
                $allowedDepts = $roleMap[$user->role];
                $query->where(function ($q) use ($user, $allowedDepts) {
                    $q->whereIn('department', $allowedDepts)
                        ->orWhere('requester_id', $user->id);
                });
            }
            // 3. LEVEL USER BIASA
            else {
                // [PERUBAHAN LOGIC USER]
                // User bisa lihat data teman sedevisi + data sendiri
                $userDivisi = $user->divisi;

                $query->where(function ($q) use ($user, $userDivisi) {
                    // A. Jika user punya divisi, tampilkan semua tiket divisi itu
                    if ($userDivisi) {
                        $q->where('department', $userDivisi);
                    }
                    // B. ATAU tampilkan tiket buatan sendiri
                    $q->orWhere('requester_id', $user->id);
                });
            }
        }

        // LOGIKA FILTER DARI FORM (Tetap sama)
        if ($request->filled('selected_ids')) {
            $ids = explode(',', $request->selected_ids);
            $query->whereIn('id', $ids);
        } else {
            $query->when($request->search, function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('ticket_num', 'LIKE', "%{$request->search}%")
                        ->orWhere('requester_name', 'LIKE', "%{$request->search}%")
                        ->orWhere('description', 'LIKE', "%{$request->search}%");
                });
            });

            // Filter status dari dropdown (bukan batasan hak akses)
            $query->when($request->status && $request->status !== 'all', fn($q) => $q->where('status', $request->status));
            $query->when($request->category && $request->category !== 'all', fn($q) => $q->where('category', $request->category));
            $query->when($request->parameter && $request->parameter !== 'all', fn($q) => $q->where('parameter_permintaan', $request->parameter));
            $query->when($request->plant_id && $request->plant_id !== 'all', fn($q) => $q->where('plant', $request->plant_id));

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereDate('created_at', '>=', $request->start_date)
                    ->whereDate('created_at', '<=', $request->end_date);
            }
        }

        $query->orderBy('created_at', 'desc');

        return Excel::download(new WorkOrderExport($query), 'Laporan-GA-' . date('d-m-Y-H-i') . '.xlsx');
    }
}
