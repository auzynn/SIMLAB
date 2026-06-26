<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\InfoLabController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Semua endpoint berprefix /api. Lihat 3_SDD.md Bagian 5.
*/

// --- Autentikasi ---
Route::post('/auth/login', [AuthController::class, 'login']);

// Google OAuth (publik) — alur registrasi/login institusi UNSIL
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

// Konten info lab (publik untuk baca — dipakai halaman informasi tanpa login)
Route::get('/info-lab/{tipe}', [InfoLabController::class, 'show'])
    ->whereIn('tipe', ['beranda', 'visi_misi', 'kepala_lab', 'roadmap_kk']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Atur/ubah password untuk mengaktifkan login manual (3_SDD.md 2.1, SRS UC-01b)
    Route::post('/auth/set-password', [AuthController::class, 'setPassword']);
    Route::patch('/auth/change-password', [AuthController::class, 'changePassword']);

    // Kelola user & role — khusus Admin (3_SDD.md 5.2, otorisasi via Gate manage-users)
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

    // Update konten info lab — khusus Admin (3_SDD.md 5.12, otorisasi via Gate manage-info-lab)
    Route::patch('/info-lab/{tipe}', [InfoLabController::class, 'update'])
        ->whereIn('tipe', ['beranda', 'visi_misi', 'kepala_lab', 'roadmap_kk']);
});
