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

        // Hak kelola konten halaman informasi lab: khusus Admin (2_SRS.md Bagian 1)
        Gate::define('manage-info-lab', fn (User $user) => $user->role === 'admin');

        // Hak kelola master Bidang Minat: Admin & Supervisor (sesuai instruksi pemilik).
        Gate::define(
            'manage-bidang-minat',
            fn (User $user) => in_array($user->role, ['admin', 'supervisor'], true),
        );
    }
}
