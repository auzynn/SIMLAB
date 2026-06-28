<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Alur registrasi/login Google OAuth (GoogleAuthController).
 * Socialite di-mock agar tidak benar-benar memanggil Google — lihat 3_SDD.md Bagian 2.
 */
class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T1.18 — Email berdomain non-UNSIL ditolak saat login Google.
     * Sistem tidak boleh membuat akun; harus redirect balik ke login dengan error.
     */
    public function test_domain_email_non_unsil_ditolak(): void
    {
        // URL frontend dipastikan agar assert redirect deterministik
        config(['services.frontend.url' => 'http://localhost:3000']);

        // Google "mengembalikan" user beremail non-UNSIL
        $this->mockGoogleUser('seseorang@gmail.com');

        $res = $this->get('/api/auth/google/callback');

        // Ditolak: redirect ke /login?error=invalid_domain (bukan terbit token)
        $res->assertRedirect('http://localhost:3000/login?error=invalid_domain');

        // Tidak ada akun yang lahir dari email non-UNSIL
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * T1.19 — Login Google pertama kali (email @student) membuat akun `users`
     * berrole mahasiswa + profil `mahasiswa` otomatis.
     */
    public function test_login_google_pertama_membuat_user_dan_profil_mahasiswa(): void
    {
        config(['services.frontend.url' => 'http://localhost:3000']);
        $this->mockGoogleUser('197006028@student.unsil.ac.id');

        // Berhasil: redirect ke /auth/callback membawa token (bukan error)
        $this->get('/api/auth/google/callback')
            ->assertRedirectContains('/auth/callback?token=');

        // Akun users dibuat dengan role mahasiswa (diturunkan dari domain @student)
        $this->assertDatabaseHas('users', [
            'email' => '197006028@student.unsil.ac.id',
            'role' => 'mahasiswa',
        ]);

        // Profil mahasiswa ikut terbuat & tertaut; tidak ada profil dosen
        $user = User::where('email', '197006028@student.unsil.ac.id')->first();
        $this->assertDatabaseHas('mahasiswa', ['user_id' => $user->id]);
        $this->assertDatabaseCount('dosen', 0);
    }

    /**
     * T1.19 — Login Google pertama kali (email @unsil.ac.id) membuat akun `users`
     * berrole dosen + profil `dosen` otomatis.
     */
    public function test_login_google_pertama_membuat_user_dan_profil_dosen(): void
    {
        config(['services.frontend.url' => 'http://localhost:3000']);
        $this->mockGoogleUser('nur.widiyasono@unsil.ac.id');

        $this->get('/api/auth/google/callback')
            ->assertRedirectContains('/auth/callback?token=');

        $this->assertDatabaseHas('users', [
            'email' => 'nur.widiyasono@unsil.ac.id',
            'role' => 'dosen',
        ]);

        $user = User::where('email', 'nur.widiyasono@unsil.ac.id')->first();
        $this->assertDatabaseHas('dosen', ['user_id' => $user->id]);
        $this->assertDatabaseCount('mahasiswa', 0);
    }

    /**
     * T1.20 — NPM diekstrak dari local-part email; angkatan = "20" + dua digit awal NPM.
     */
    public function test_npm_dan_angkatan_diekstrak_dari_email(): void
    {
        config(['services.frontend.url' => 'http://localhost:3000']);
        $this->mockGoogleUser('197006028@student.unsil.ac.id');

        $this->get('/api/auth/google/callback');

        // "197006028" -> npm; dua digit awal "19" -> angkatan "2019"
        // (string concat "20"."19", BUKAN penjumlahan angka)
        $this->assertDatabaseHas('mahasiswa', [
            'npm' => '197006028',
            'angkatan' => '2019',
        ]);
    }

    /**
     * Pasang mock pada rantai Socialite::driver('google')->stateless()->user()
     * agar mengembalikan user Google palsu dengan email yang diberikan.
     * (Dipakai ulang untuk test alur Google lain: T1.19, T1.20.)
     */
    private function mockGoogleUser(string $email): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn('Pengguna Uji');
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/a.jpg');

        // Shorthand Mockery untuk rantai pemanggilan method
        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);
    }
}
