<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hak kelola data user & role: khusus Admin (2_SRS.md Bagian 1 matriks RBAC)
        Gate::define('manage-users', fn (User $user) => $user->role === 'admin');

        // Hak kelola konten halaman informasi lab: Admin & Supervisor (2_SRS.md Bagian 1 revisi).
        // Supervisor (Aslab) didelegasikan memelihara konten publik lab.
        Gate::define(
            'manage-info-lab',
            fn (User $user) => in_array($user->role, ['admin', 'supervisor'], true),
        );

        // Hak kelola master Bidang Minat: Admin & Supervisor (sesuai instruksi pemilik).
        Gate::define(
            'manage-bidang-minat',
            fn (User $user) => in_array($user->role, ['admin', 'supervisor'], true),
        );

        // Hak kelola Data Master (ruangan, mata kuliah, perangkat): Admin & Supervisor
        // (2_SRS.md Bagian 1; 3_SDD.md 3.4, 3.6). Read tetap terbuka untuk semua role login.
        Gate::define(
            'manage-master-data',
            fn (User $user) => in_array($user->role, ['admin', 'supervisor'], true),
        );

        // Approve/reject pengajuan peminjaman ruangan: Admin & Supervisor (2_SRS.md Bagian 1, UC-02).
        Gate::define(
            'approve-peminjaman-ruangan',
            fn (User $user) => in_array($user->role, ['admin', 'supervisor'], true),
        );

        // Approve/reject/kembalikan peminjaman perangkat & perpanjangan: Admin & Supervisor
        // (2_SRS.md Bagian 1, UC-03).
        Gate::define(
            'approve-peminjaman-perangkat',
            fn (User $user) => in_array($user->role, ['admin', 'supervisor'], true),
        );

        // Mendaftar sebagai peserta Kelas Lab: khusus Mahasiswa (2_SRS.md Bagian 1, UC-02a).
        Gate::define('daftar-kelas-lab', fn (User $user) => $user->role === 'mahasiswa');

        // Akses Laporan/Report (lihat rekap + unduh PDF): Admin & Supervisor (2_SRS.md Bagian 1, UC-06).
        Gate::define(
            'view-report',
            fn (User $user) => in_array($user->role, ['admin', 'supervisor'], true),
        );

        // Akses Rekap Tugas Kelas Lab (rekap + unduh PDF/Excel): Admin, Supervisor & Dosen.
        // Dosen di-scope ke kelas miliknya di RekapTugasService (2_SRS.md Bagian 1, UC-06).
        Gate::define(
            'view-rekap-tugas',
            fn (User $user) => in_array($user->role, ['admin', 'supervisor', 'dosen'], true),
        );
    }
}
