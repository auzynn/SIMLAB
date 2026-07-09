<?php

use App\Http\Controllers\AslabController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BidangMinatController;
use App\Http\Controllers\DeadlinePertemuanController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\InfoLabController;
use App\Http\Controllers\KelasLabController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PeminjamanPerangkatController;
use App\Http\Controllers\PeminjamanRuanganController;
use App\Http\Controllers\PerangkatController;
use App\Http\Controllers\PerpanjanganController;
use App\Http\Controllers\PortofolioController;
use App\Http\Controllers\RekapTugasController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\SertifikasiController;
use App\Http\Controllers\TugasController;
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
    // Atur ulang password tanpa password lama (jalur "lupa password") — khusus akun tertaut Google UNSIL
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

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

    // Inventaris Perangkat (3_SDD.md 5.9): read terbuka untuk semua role login,
    // CUD via Gate manage-master-data (Admin/Supervisor) di controller.
    Route::apiResource('perangkat', PerangkatController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Peminjaman Perangkat (3_SDD.md 5.9, SRS UC-03). Ajukan: Mahasiswa;
    // approve/reject/kembalikan & perpanjangan: Admin/Supervisor (Gate approve-peminjaman-perangkat).
    Route::get('/peminjaman-perangkat', [PeminjamanPerangkatController::class, 'index']);
    Route::post('/peminjaman-perangkat', [PeminjamanPerangkatController::class, 'store']);
    // Batalkan pengajuan sendiri saat masih menunggu (pemilik) / hapus (Admin/Supervisor).
    Route::delete('/peminjaman-perangkat/{peminjamanPerangkat}', [PeminjamanPerangkatController::class, 'destroy']);
    Route::patch('/peminjaman-perangkat/{peminjamanPerangkat}/approve', [PeminjamanPerangkatController::class, 'approve']);
    Route::patch('/peminjaman-perangkat/{peminjamanPerangkat}/reject', [PeminjamanPerangkatController::class, 'reject']);
    Route::patch('/peminjaman-perangkat/{peminjamanPerangkat}/kembalikan', [PeminjamanPerangkatController::class, 'kembalikan']);
    Route::post('/peminjaman-perangkat/{peminjamanPerangkat}/perpanjangan', [PeminjamanPerangkatController::class, 'ajukanPerpanjangan']);
    Route::patch('/perpanjangan/{perpanjanganPeminjaman}/approve', [PerpanjanganController::class, 'approve']);
    Route::patch('/perpanjangan/{perpanjanganPeminjaman}/reject', [PerpanjanganController::class, 'reject']);

    // Pengumpulan Tugas (menggantikan Presensi). Kirim: Mahasiswa (kelas yang diikuti);
    // rekap sesuai role (Mahasiswa → miliknya; Dosen → kelasnya; Admin/Supervisor → semua).
    Route::get('/tugas', [TugasController::class, 'index']);
    Route::post('/tugas', [TugasController::class, 'store']);
    Route::delete('/tugas/{tugas}', [TugasController::class, 'destroy']);

    // Kelas Lab/Praktikum (3_SDD.md 5.7, SRS UC-02a). Aksi peserta & pendaftaran
    // didefinisikan sebelum apiResource agar tidak terbaca sebagai {kelasLab}.
    // Persetujuan pendaftaran — Dosen (kelas miliknya) / Supervisor.
    // Rekap kepatuhan pengumpulan tugas per kelas (Dosen/Supervisor/Admin) — sebelum {kelasLab}.
    Route::get('/kelas-lab/rekap-tugas', [KelasLabController::class, 'rekapTugas']);
    Route::get('/kelas-lab/pendaftaran', [KelasLabController::class, 'pendaftaran']);
    Route::patch('/kelas-lab/pendaftaran/{kelasLabPeserta}/approve', [KelasLabController::class, 'approvePendaftaran']);
    Route::patch('/kelas-lab/pendaftaran/{kelasLabPeserta}/reject', [KelasLabController::class, 'rejectPendaftaran']);
    Route::delete('/kelas-lab/pendaftaran/{kelasLabPeserta}', [KelasLabController::class, 'hapusPeserta']);
    Route::get('/kelas-lab/{kelasLab}/peserta', [KelasLabController::class, 'peserta']);
    Route::post('/kelas-lab/{kelasLab}/daftar', [KelasLabController::class, 'daftar']);
    Route::delete('/kelas-lab/{kelasLab}/daftar', [KelasLabController::class, 'batalDaftar']);

    // Deadline pengumpulan tugas per pertemuan (Dosen pengampu/Supervisor/Admin atur; semua role lihat).
    Route::get('/kelas-lab/{kelasLab}/deadline', [DeadlinePertemuanController::class, 'index']);
    Route::put('/kelas-lab/{kelasLab}/deadline/{pertemuan}', [DeadlinePertemuanController::class, 'upsert']);
    Route::delete('/kelas-lab/{kelasLab}/deadline/{pertemuan}', [DeadlinePertemuanController::class, 'destroy']);
    Route::apiResource('kelas-lab', KelasLabController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy'])
        ->parameters(['kelas-lab' => 'kelasLab']);

    // Katalog Sertifikasi (3_SDD.md 5.13, SRS UC-05). Read terbuka untuk semua role login;
    // CUD via Gate manage-master-data (Admin/Supervisor) di controller. Modul informasional.
    Route::apiResource('sertifikasi', SertifikasiController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['sertifikasi' => 'sertifikasi']);

    // Portofolio Mahasiswa (3_SDD.md 5.14, PRD 3.7). Read terbuka untuk semua role login;
    // CUD hanya pemilik (Mahasiswa) — divalidasi di Store/UpdatePortofolioRequest & destroy.
    Route::apiResource('portofolio', PortofolioController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['portofolio' => 'portofolio']);

    // Notifikasi In-App (3_SDD.md 5.14, SRS UC-07). Semua role hanya notifikasi miliknya;
    // pembuatan dilakukan internal (NotifikasiService), bukan endpoint publik.
    // read-all didefinisikan sebelum aksi ber-{notifikasi} agar tidak terbaca sebagai id.
    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::patch('/notifikasi/read-all', [NotifikasiController::class, 'readAll']);
    Route::patch('/notifikasi/{notifikasi}/read', [NotifikasiController::class, 'read']);
    Route::delete('/notifikasi/{notifikasi}', [NotifikasiController::class, 'destroy']);

    // Laporan/Report (3_SDD.md 5.13, SRS UC-06). Rekap + unduh PDF — Admin/Supervisor.
    Route::get('/report', [ReportController::class, 'index']);
    Route::get('/report/pdf', [ReportController::class, 'pdf']);

    // Rekap Tugas Kelas Lab (3_SDD.md 5.15, SRS UC-06). Ringkasan + matriks per pertemuan,
    // unduh PDF & Excel — Admin/Supervisor/Dosen (Dosen di-scope ke kelas sendiri).
    Route::get('/rekap-tugas', [RekapTugasController::class, 'index']);
    Route::get('/rekap-tugas/pdf', [RekapTugasController::class, 'pdf']);
    Route::get('/rekap-tugas/excel', [RekapTugasController::class, 'excel']);

    // Update konten info lab — khusus Admin (3_SDD.md 5.12, otorisasi via Gate manage-info-lab)
    Route::patch('/info-lab/{tipe}', [InfoLabController::class, 'update'])
        ->whereIn('tipe', ['beranda', 'visi_misi', 'kepala_lab', 'roadmap_kk']);

    // Unggah lampiran pengumuman (Admin) — dipakai opsi "File" pada editor Pengumuman
    Route::post('/info-lab/upload', [InfoLabController::class, 'uploadLampiran']);
});
