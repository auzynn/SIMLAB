<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Akun Admin & Supervisor sengaja dibuat lewat seeder (bukan endpoint publik)
     * sesuai 3_SDD.md Bagian 2. Password di-set agar bisa langsung login manual.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@unsil.ac.id'],
            [
                'name' => 'Administrator',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'supervisor@unsil.ac.id'],
            [
                'name' => 'Supervisor Lab',
                'role' => 'supervisor',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Konten awal halaman informasi lab (3_SDD.md 3.15)
        $this->call(InfoLabSeeder::class);
    }
}
