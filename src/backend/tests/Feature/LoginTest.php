<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Login manual (POST /api/auth/login) — 3_SDD.md 2.1, SRS UC-01b.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T1.21 — Login manual ditolak jika akun belum pernah mengatur password
     * (kolom `password` masih NULL karena akun lahir lewat Google OAuth).
     */
    public function test_login_manual_ditolak_jika_password_masih_null(): void
    {
        // Akun ber-password NULL (skenario akun hasil registrasi Google)
        User::factory()->create([
            'email' => 'mahasiswa@student.unsil.ac.id',
            'password' => null,
        ]);

        $res = $this->postJson('/api/auth/login', [
            'email' => 'mahasiswa@student.unsil.ac.id',
            'password' => 'apa-saja-123',
        ]);

        // Ditolak (422) dengan pesan eksplisit sesuai SRS UC-01 skenario 2b
        $res->assertStatus(422)
            ->assertJsonValidationErrors('email')
            ->assertJsonFragment([
                'Akun ini belum mengaktifkan login manual. Silakan login dengan Google UNSIL, lalu atur password di halaman Profil.',
            ]);
    }
}
