<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Mulai alur Google OAuth: arahkan user ke halaman izin Google.
     * Stateless karena route API tidak memakai session. (3_SDD.md Bagian 2)
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Callback dari Google: validasi domain, find-or-create user,
     * terbitkan Sanctum token, lalu redirect balik ke frontend membawa token.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth gagal: '.$e->getMessage());

            return $this->toFrontend('/login', ['error' => 'oauth_failed']);
        }

        $email = strtolower((string) $googleUser->getEmail());

        // Tentukan role dari domain email institusi (cek student lebih dulu)
        $role = $this->roleFromEmail($email);
        if ($role === null) {
            return $this->toFrontend('/login', ['error' => 'invalid_domain']);
        }

        // Find-or-create (users-only untuk tahap ini; profil dosen/mahasiswa menyusul)
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'role' => $role,
                'email_verified_at' => now(),
                // password sengaja NULL: login manual baru aktif setelah di-set di Profil
            ]);

            // (BELUM DIAKTIFKAN) Auto-create profil dosen/mahasiswa sesuai SDD Bagian 2 langkah 6.
            // Aktifkan dengan meng-uncomment baris di bawah SETELAH menjalankan
            // `php artisan migrate` untuk tabel dosen & mahasiswa.
            // $this->createRoleProfile($user, $email);
        } elseif (is_null($user->google_id)) {
            // Akun sudah ada (mis. dibuat manual) tapi belum tertaut Google → tautkan
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar' => $user->avatar ?: $googleUser->getAvatar(),
            ])->save();
        }

        $token = $user->createToken('google-oauth')->plainTextToken;

        return $this->toFrontend('/auth/callback', ['token' => $token]);
    }

    /**
     * Petakan domain email institusi ke role. Null jika domain tidak diizinkan.
     */
    private function roleFromEmail(string $email): ?string
    {
        if (Str::endsWith($email, '@student.unsil.ac.id')) {
            return 'mahasiswa';
        }

        if (Str::endsWith($email, '@unsil.ac.id')) {
            return 'dosen';
        }

        return null;
    }

    /**
     * (BELUM DIAKTIFKAN) Buat profil dosen/mahasiswa otomatis saat registrasi pertama.
     *
     * Sengaja belum dipanggil di callback() — tabel `dosen`/`mahasiswa` belum dimigrasi.
     * Untuk mengaktifkan: jalankan migrasi lalu uncomment pemanggilan di callback().
     * Lihat 3_SDD.md Bagian 2 (langkah 6) serta 3.2 & 3.3.
     */
    private function createRoleProfile(User $user, string $email): void
    {
        if ($user->role === 'dosen') {
            Dosen::create(['user_id' => $user->id]);

            return;
        }

        if ($user->role === 'mahasiswa') {
            $npm = Str::before($email, '@');        // local-part email, mis. "197006028"
            $angkatan = '20'.substr($npm, 0, 2);    // string concat: "19" -> "2019" (bukan penjumlahan)

            Mahasiswa::create([
                'user_id' => $user->id,
                'npm' => $npm,
                'angkatan' => $angkatan,
            ]);
        }
    }

    /**
     * Bangun redirect ke frontend dengan query string.
     */
    private function toFrontend(string $path, array $query): RedirectResponse
    {
        $base = rtrim((string) config('services.frontend.url'), '/');

        return redirect()->away($base.$path.'?'.http_build_query($query));
    }
}
