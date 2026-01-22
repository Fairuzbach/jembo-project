<?php

use App\Http\Controllers\Api\WorkOrderApiController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// --- A. PINTU TERBUKA (Bisa diakses tanpa login) ---
Route::post('/login', [AuthController::class, 'login']);

// --- B. PINTU TERKUNCI (Wajib Login / Pakai Token) ---
// Kita bungkus route di dalam middleware 'auth:sanctum'
Route::middleware('auth:sanctum')->group(function () {

    // Route Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Route Work Order (CRUD Lengkap)
    Route::get('/work-orders', [WorkOrderApiController::class, 'index']);
    Route::get('/work-orders/{id}', [WorkOrderApiController::class, 'show']);
    Route::post('/work-orders', [WorkOrderApiController::class, 'store']);
    Route::put('/work-orders/{id}', [WorkOrderApiController::class, 'update']);
    Route::delete('/work-orders/{id}', [WorkOrderApiController::class, 'destroy']);
});
