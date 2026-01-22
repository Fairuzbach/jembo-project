<?php

use Illuminate\Support\Facades\Route;
// Import Controller
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Facilities\FacilitiesController; // Pastikan namespace ini benar
use App\Http\Controllers\GeneralAffair\GeneralAffairController;
use App\Http\Controllers\Engineering\WorkOrderEngineeringController;
use App\Models\Employee;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES (Login & Auth)
|--------------------------------------------------------------------------
*/

// Redirect Root ke Halaman Landing (atau Login jika ingin strict)
Route::get('/', function () {
    return redirect()->route('landing');
});

Route::get('/landing', function () {
    return view('landing');
})->name('landing');

// Halaman Landing Divisi (Opsional, Public info)
Route::get('/wo-fh', function () {
    return view('landingfh');
})->name('wo-fh');
Route::get('/wo-eng', function () {
    return view('landingeng');
})->name('wo-eng');
Route::get('/wo-ga', function () {
    return view('landingga');
})->name('wo-ga');

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

    // --- A. DASHBOARD UTAMA ---
    Route::get('/dashboard', function () {
        return view('landing'); // Redirect ke landing internal setelah login
    })->name('dashboard');


    // --- B. API HELPER (Internal Use Only) ---
    // Dipindah ke sini agar aman dari akses publik
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
    Route::prefix('fh')->name('fh.')->group(function () {
        // Halaman Utama
        Route::get('/', [FacilitiesController::class, 'index'])->name('index');

        // Dashboard Admin
        Route::get('/dashboard', [FacilitiesController::class, 'dashboard'])->name('dashboard');

        // Create Ticket
        Route::post('/store', [FacilitiesController::class, 'store'])->name('store');

        // Export Excel
        Route::get('/export', [FacilitiesController::class, 'index'])->name('export'); // Index menangani export juga

        // Update Status (Approve / Assign / Reject / Complete)
        // SATU ROUTE SAKTI untuk semua aksi status
        Route::post('/{id}/update-status', [FacilitiesController::class, 'updateStatus'])->name('updateStatus');
        Route::post('/{id}/approve', [FacilitiesController::class, 'approve'])->name('approve'); // Route Baru
        Route::post('/{id}/reject', [FacilitiesController::class, 'reject'])->name('reject');   // Route Baru

    });


    // --- D. MODULE GENERAL AFFAIR (GA) ---
    Route::prefix('ga')->name('ga.')->group(function () {
        Route::get('/', [GeneralAffairController::class, 'index'])->name('index');
        Route::get('/dashboard', [GeneralAffairController::class, 'dashboard'])->name('dashboard');
        Route::get('/export', [GeneralAffairController::class, 'export'])->name('export');
        Route::post('/store', [GeneralAffairController::class, 'store'])->name('store');

        // Helper GA
        Route::get('/check-employee', [GeneralAffairController::class, 'checkEmployee'])->name('check-employee');

        // Approval Flow
        Route::post('/process/{id}', [GeneralAffairController::class, 'processTicket'])->name('process');
        Route::post('/{id}/approve-technical', [GeneralAffairController::class, 'approveByTechnical'])->name('approve-technical');
        Route::put('/update-status/{id}', [GeneralAffairController::class, 'updateStatus'])->name('update-status');
    });
    // Helper Plant Departments
    Route::get('/get-departments/{plant_id}', [GeneralAffairController::class, 'getDepartmentsByPlant'])->name('get.departments');


    // --- E. MODULE ENGINEERING (ENG) ---
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


    // --- G. SUPER ADMIN / USER MANAGEMENT ---
    Route::middleware(['can:user.manage'])->prefix('admin')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index'); // /admin

        // User Actions
        Route::post('/import', [UserManagementController::class, 'import'])->name('import');
        Route::get('/template', [UserManagementController::class, 'downloadTemplate'])->name('template');
        Route::put('/{user}/role', [UserManagementController::class, 'updateRole'])->name('update-role');
        Route::put('/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');

        // Categories Actions (Jika masih ada di controller user management)
        Route::post('/categories', [UserManagementController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/categories/{id}', [UserManagementController::class, 'destroyCategory'])->name('categories.destroy');
    });
});
