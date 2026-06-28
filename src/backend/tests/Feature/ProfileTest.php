<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Edit profil akun sendiri (PATCH /api/auth/profile) — 3_SDD.md 5.1.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T1.22 — NPM & angkatan mahasiswa immutable: tak bisa diubah lewat update profil
     * meski dikirim di body; hanya `prodi` yang boleh berubah (SDD 3.3).
     */
    public function test_npm_dan_angkatan_tidak_bisa_diubah_lewat_update_profil(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        Mahasiswa::create([
            'user_id' => $user->id,
            'npm' => '197006028',
            'angkatan' => '2019',
        ]);
        Sanctum::actingAs($user);

        $res = $this->patchJson('/api/auth/profile', [
            'npm' => '999999999',     // coba ubah — harus diabaikan backend
            'angkatan' => '2099',     // coba ubah — harus diabaikan backend
            'prodi' => 'Informatika', // field sah → boleh berubah
        ]);

        $res->assertOk();

        // NPM & angkatan tetap; hanya prodi yang berubah
        $this->assertDatabaseHas('mahasiswa', [
            'user_id' => $user->id,
            'npm' => '197006028',
            'angkatan' => '2019',
            'prodi' => 'Informatika',
        ]);
        $this->assertDatabaseMissing('mahasiswa', ['npm' => '999999999']);
    }
}
