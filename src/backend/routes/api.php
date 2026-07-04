<?php

use App\Http\Controllers\AslabController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BidangMinatController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\InfoLabController;
use App\Http\Controllers\KelasLabController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\PeminjamanRuanganController;
use App\Http\Controllers\RuanganController;
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

// Profil dosen (publik untuk baca — halaman Daftar & Detail Dosen tanpa login)
Route::get('/dosen', [DosenController::class, 'index']);
Route::get('/dosen/{dosen}', [DosenController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Atur/ubah password untuk mengaktifkan login manual (3_SDD.md 2.1, SRS UC-01b)
    Route::post('/auth/set-password', [AuthController::class, 'setPassword']);
    Route::patch('/auth/change-password', [AuthController::class, 'changePassword']);

    // Unggah/ganti foto avatar akun sendiri (multipart) — Profil Saya
    Route::post('/auth/avatar', [AuthController::class, 'updateAvatar']);

    // Edit profil sendiri (name, no_telp; +nidn & bidang_minat_ids[] untuk dosen)
    Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);

    // Master Bidang Minat: read terbuka untuk semua yang login (dipakai dropdown Edit Profil),
    // CUD via Gate manage-bidang-minat (Admin/Supervisor) di controller.
    Route::apiResource('bidang-minat', BidangMinatController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Update profil dosen — pemilik atau Admin/Supervisor (3_SDD.md 5.3, DosenPolicy)
    Route::patch('/dosen/{dosen}', [DosenController::class, 'update']);

    // Kelola user & role — khusus Admin (3_SDD.md 5.2, otorisasi via Gate manage-users)
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

    // Delegasi Asisten Lab — Admin menetapkan mahasiswa jadi Supervisor (Gate manage-users)
    Route::get('/aslab', [AslabController::class, 'index']);
    Route::post('/aslab/{user}', [AslabController::class, 'promote']);
    Route::delete('/aslab/{user}', [AslabController::class, 'demote']);

    // Data Master (3_SDD.md 5.5, 5.6): read terbuka untuk semua role login,
    // CUD via Gate manage-master-data (Admin/Supervisor) di masing-masing controller.
    Route::apiResource('ruangan', RuanganController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('mata-kuliah', MataKuliahController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['mata-kuliah' => 'mataKuliah']);

    // Peminjaman Ruangan (3_SDD.md 5.5, SRS UC-02). /kalender didefinisikan sebelum aksi
    // ber-{id} agar tidak terbentur route model binding.
    Route::get('/peminjaman-ruangan/kalender', [PeminjamanRuanganController::class, 'kalender']);
    Route::get('/peminjaman-ruangan', [PeminjamanRuanganController::class, 'index']);
    Route::post('/peminjaman-ruangan', [PeminjamanRuanganController::class, 'store']);
    Route::patch('/peminjaman-ruangan/{peminjamanRuangan}/approve', [PeminjamanRuanganController::class, 'approve']);
    Route::patch('/peminjaman-ruangan/{peminjamanRuangan}/reject', [PeminjamanRuanganController::class, 'reject']);
    Route::delete('/peminjaman-ruangan/{peminjamanRuangan}', [PeminjamanRuanganController::class, 'destroy']);

    // Kelas Lab/Praktikum (3_SDD.md 5.7, SRS UC-02a). Aksi peserta & pendaftaran
    // didefinisikan sebelum apiResource agar tidak terbaca sebagai {kelasLab}.
    // Persetujuan pendaftaran — Dosen (kelas miliknya) / Supervisor.
    Route::get('/kelas-lab/pendaftaran', [KelasLabController::class, 'pendaftaran']);
    Route::patch('/kelas-lab/pendaftaran/{kelasLabPeserta}/approve', [KelasLabController::class, 'approvePendaftaran']);
    Route::patch('/kelas-lab/pendaftaran/{kelasLabPeserta}/reject', [KelasLabController::class, 'rejectPendaftaran']);
    Route::delete('/kelas-lab/pendaftaran/{kelasLabPeserta}', [KelasLabController::class, 'hapusPeserta']);
    Route::get('/kelas-lab/{kelasLab}/peserta', [KelasLabController::class, 'peserta']);
    Route::post('/kelas-lab/{kelasLab}/daftar', [KelasLabController::class, 'daftar']);
    Route::delete('/kelas-lab/{kelasLab}/daftar', [KelasLabController::class, 'batalDaftar']);
    Route::apiResource('kelas-lab', KelasLabController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy'])
        ->parameters(['kelas-lab' => 'kelasLab']);

    // Update konten info lab — khusus Admin (3_SDD.md 5.12, otorisasi via Gate manage-info-lab)
    Route::patch('/info-lab/{tipe}', [InfoLabController::class, 'update'])
        ->whereIn('tipe', ['beranda', 'visi_misi', 'kepala_lab', 'roadmap_kk']);
});
