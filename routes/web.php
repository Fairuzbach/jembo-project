<?php

use Illuminate\Support\Facades\Route;

// Import Controller
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Facilities\FacilitiesController;
use App\Http\Controllers\GeneralAffair\GeneralAffairController;
use App\Http\Controllers\Engineering\WorkOrderEngineeringController;
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
        ->with('error', 'HARAP MASUKKAN KODE DEPARTMENT DI URL (Contoh: /wo-fh, /wo-eng, atau /wo-ga).');
});

// B. DEPARTMENT ENTRY POINTS
// User ketik /wo-fh -> Redirect ke Dashboard FH -> Kena Middleware Auth -> Redirect ke Login
// Setelah login sukses, Laravel akan mengembalikan user ke Dashboard FH.
Route::get('/wo-fh', function () {
    return redirect()->route('fh.index');
});

Route::get('/wo-eng', function () {
    return redirect()->route('eng.index');
});

Route::get('/wo-ga', function () {
    return redirect()->route('ga.index');
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
    Route::get('/dashboard', function () {
        auth()->guard('web')->logout();
        $request->session->invalidate();
        $request->session->regenerateToken();

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
        Route::get('/export', [FacilitiesController::class, 'index'])->name('export');

        // Status Actions
        Route::post('/{id}/update-status', [FacilitiesController::class, 'updateStatus'])->name('updateStatus');
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
    });


    // --- E. MODULE ENGINEERING (ENG) ---
    // URL Asli: /eng (User dilempar ke sini dari /wo-eng)
    Route::prefix('eng')->name('eng.')->group(function () {
        Route::get('/', [WorkOrderEngineeringController::class, 'index'])->name('index');
        Route::get('/export', [WorkOrderEngineeringController::class, 'export'])->name('export');
        Route::post('/store', [WorkOrderEngineeringController::class, 'store'])->name('store');
        Route::put('/{workOrder}', [WorkOrderEngineeringController::class, 'update'])->name('update');
        Route::put('/{id}/update-status', [WorkOrderEngineeringController::class, 'updateStatus'])->name('updateStatus');
    });


    // --- F. USER PROFILE ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


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
});
