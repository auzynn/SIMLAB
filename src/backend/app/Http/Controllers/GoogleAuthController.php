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

            // Auto-create profil dosen/mahasiswa (SDD Bagian 2 langkah 6, 3.2 & 3.3).
            $this->createRoleProfile($user, $email);
        } else {
            // Akun lama: pastikan konsisten — tautkan google_id bila kosong & backfill
            // profil bila belum ada (mis. akun pra-aktivasi createRoleProfile).
            if (is_null($user->google_id)) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $user->avatar ?: $googleUser->getAvatar(),
                ])->save();
            }

            $this->ensureRoleProfile($user, $email);
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
     * Buat profil dosen/mahasiswa otomatis saat registrasi pertama.
     * Idempotent (firstOrCreate): aman dipanggil ulang tanpa duplikat — lihat 3_SDD.md 3.2 & 3.3.
     *
     * Mahasiswa: NPM diekstrak dari local-part email; angkatan = "20" + dua digit awal NPM.
     */
    private function createRoleProfile(User $user, string $email): void
    {
        if ($user->role === 'dosen') {
            Dosen::firstOrCreate(['user_id' => $user->id]);

            return;
        }

        if ($user->role === 'mahasiswa') {
            $npm = Str::before($email, '@');        // local-part email, mis. "197006028"
            $angkatan = '20'.substr($npm, 0, 2);    // string concat: "19" -> "2019" (bukan penjumlahan)

            Mahasiswa::firstOrCreate(
                ['user_id' => $user->id],
                ['npm' => $npm, 'angkatan' => $angkatan],
            );
        }
    }

    /**
     * Backfill profil dosen/mahasiswa untuk akun lama yang belum punya
     * (mis. dibuat sebelum auto-create diaktifkan). Aman karena firstOrCreate.
     */
    private function ensureRoleProfile(User $user, string $email): void
    {
        if (($user->role === 'dosen' && ! $user->dosen)
            || ($user->role === 'mahasiswa' && ! $user->mahasiswa)
        ) {
            $this->createRoleProfile($user, $email);
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
