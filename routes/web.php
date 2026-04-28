<?php

use Illuminate\Support\Facades\Route;

// Import Controller
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Facilities\FacilitiesController;
use App\Http\Controllers\GeneralAffair\GeneralAffairController;
use App\Http\Controllers\Engineering\WorkOrderEngineeringController;
use App\Http\Controllers\Engineering\EngCompoundCheckController;
use App\Http\Controllers\Engineering\EngCompoundStandardController;
use App\Http\Controllers\Engineering\OperatorController;
use App\Http\Controllers\ChangePasswordController;
use App\Models\Employee;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES (Login & Entry Points)
|--------------------------------------------------------------------------
*/

// A. ROOT URL (domain.com)
// Jika user mengakses tanpa slash departemen, lempar ke login dengan ERROR.
Route::get('/', function () {
    return redirect()->route('login')
        ->with('error', 'HARAP PILIH DEPARTEMEN.');
});

// B. DEPARTMENT ENTRY POINTS
// User ketik /wo-fh -> Redirect ke Dashboard FH -> Kena Middleware Auth -> Redirect ke Login
// Setelah login sukses, Laravel akan mengembalikan user ke Dashboard FH.

Route::get('/login/{dept}', [AuthenticatedSessionController::class, 'create'])->name('login.dept');

Route::get('/wo-fh', function () {
    if (auth()->check()) return redirect()->route('fh.index');
    return redirect()->route('login.dept', ['dept' => 'wo-fh']);
});

Route::get('/wo-eng', function () {
    if (auth()->check()) return redirect()->route('eng.index');
    return redirect()->route('login.dept', ['dept' => 'wo-eng']);
});

Route::get('/wo-ga', function () {
    if (auth()->check()) return redirect()->route('ga.index');
    return redirect()->route('login.dept', ['dept' => 'wo-ga']);
});

// C. UTILITIES
Route::get('/landing', function () {
    return view('landing');
})->name('landing');

// Microsoft Auth
Route::get('/auth/login-microsoft', [MicrosoftAuthController::class, 'login']);
Route::get('/auth/callback', [MicrosoftAuthController::class, 'callback']);

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| 2. PROTECTED ROUTES (Wajib Login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // --- A. DASHBOARD UTAMA (Fallback) ---
    Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', 'Access Denied');
    })->name('dashboard');


    // --- B. API HELPER (Internal Use Only) ---
    Route::get('/check-employee/{nik}', function ($nik) {
        $employee = Employee::where('nik', $nik)->first();
        if ($employee) {
            return response()->json([
                'success'  => true,
                'name'     => $employee->name,
                'division' => $employee->department ?? $employee->division ?? '-'
            ]);
        }
        return response()->json(['success' => false], 200);
    })->name('check.employee.api');


    // --- C. MODULE FACILITIES (FH) ---
    // URL Asli: /fh (User dilempar ke sini dari /wo-fh)
    Route::prefix('fh')->name('fh.')->group(function () {
        Route::get('/', [FacilitiesController::class, 'index'])->name('index');
        Route::get('/dashboard', [FacilitiesController::class, 'dashboard'])->name('dashboard');
        Route::post('/store', [FacilitiesController::class, 'store'])->name('store');
        Route::get('/export', [FacilitiesController::class, 'export'])->name('export');
        Route::get('/{id}/pdf', [FacilitiesController::class, 'exportPdf'])->name('pdf');
        // Status Actions
        Route::put('/{id}/update-status', [FacilitiesController::class, 'updateStatus'])->name('updateStatus');
        Route::post('/{id}/approve', [FacilitiesController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [FacilitiesController::class, 'reject'])->name('reject');
    });


    // --- D. MODULE GENERAL AFFAIR (GA) ---
    // URL Asli: /ga (User dilempar ke sini dari /wo-ga)
    Route::prefix('ga')->name('ga.')->group(function () {
        Route::get('/', [GeneralAffairController::class, 'index'])->name('index');
        Route::get('/dashboard', [GeneralAffairController::class, 'dashboard'])->name('dashboard');
        Route::get('/export', [GeneralAffairController::class, 'export'])->name('export');
        Route::post('/store', [GeneralAffairController::class, 'store'])->name('store');
        Route::get('/check-employee', [GeneralAffairController::class, 'checkEmployee'])->name('check-employee');

        // Approval Flow
        Route::post('/process/{id}', [GeneralAffairController::class, 'processTicket'])->name('process');
        Route::post('/{id}/approve-technical', [GeneralAffairController::class, 'approveByTechnical'])->name('approve-technical');
        Route::put('/update-status/{id}', [GeneralAffairController::class, 'updateStatus'])->name('update-status');
        Route::get('/get-departments/{plant_id}', [GeneralAffairController::class, 'getDepartmentsByPlant'])->name('get.departments');
        Route::get('/detail/{id}', [GeneralAffairController::class, 'show'])->name('show');
    });


    // --- E. MODULE ENGINEERING (ENG) ---
    // URL Asli: /eng (User dilempar ke sini dari /wo-eng)
    Route::prefix('eng')->name('eng.')->group(function () {
        Route::post('/compound', [EngCompoundCheckController::class, 'storeCompound'])->name('storeCompound');
        Route::get('/', [WorkOrderEngineeringController::class, 'index'])->name('index');
        Route::get('/export', [WorkOrderEngineeringController::class, 'export'])->name('export');
        Route::post('/store', [WorkOrderEngineeringController::class, 'store'])->name('store');
        Route::put('/{workOrder}', [WorkOrderEngineeringController::class, 'update'])->name('update');
        Route::put('/{id}/update-status', [WorkOrderEngineeringController::class, 'updateStatus'])->name('updateStatus');
        Route::get('/compound/edit/{plant_id}/{tanggal}', [EngCompoundCheckController::class, 'editCompound'])->name('compound.edit');
        Route::put('/compound/update/{plant_id}/{tanggal}', [EngCompoundCheckController::class, 'updateCompound'])->name('compound.update');
        Route::get('/compound/standards', [EngCompoundStandardController::class, 'index'])->name('compound.standards');
        Route::put('/compound/standards/{id}', [EngCompoundStandardController::class, 'update'])->name('compound.standards.update');
        Route::get('/operator/search', [OperatorController::class, 'searchOperator'])->name('operator.search');
        Route::post('/operator/import', [OperatorController::class, 'importOperator'])->name('operator.import');
        Route::get('/compound/statistics', [EngCompoundCheckController::class, 'statistics'])->name('compound.stats');
        Route::get('/compound/export', [EngCompoundCheckController::class, 'export'])->name('compound.export');
        Route::get('/compound/report', [EngCompoundCheckController::class, 'report'])->name('compound.report');
    });


    // --- F. USER PROFILE ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/security/change-password', [ChangePasswordController::class, 'index'])->name('view.change.password');
    Route::post('/security/change-password', [ChangePasswordController::class, 'update'])->name('save.change.password');

    // --- G. SUPER ADMIN ---
    Route::middleware(['can:user.manage'])->prefix('admin')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::post('/import', [UserManagementController::class, 'import'])->name('import');
        Route::get('/template', [UserManagementController::class, 'downloadTemplate'])->name('template');
        Route::put('/{user}/role', [UserManagementController::class, 'updateRole'])->name('update-role');
        Route::put('/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('/categories', [UserManagementController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/categories/{id}', [UserManagementController::class, 'destroyCategory'])->name('categories.destroy');
    });

    Route::prefix('superadmin')->name('superadmin.')->middleware('auth')->group(function () {
        Route::get('/monitor', [\App\Http\Controllers\Admin\UserMonitorController::class, 'index'])->name('monitor');
        Route::get('/monitor/data', [\App\Http\Controllers\Admin\UserMonitorController::class, 'data'])->name('monitor.data');
    });
});
