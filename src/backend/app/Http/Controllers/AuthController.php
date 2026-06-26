<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login manual (email + password). Mengembalikan Sanctum token + data user.
     * Lihat alur di 3_SDD.md Bagian 2.1.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Email tidak ditemukan → pesan umum (tidak membocorkan keberadaan email)
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Akun belum pernah set password (lahir lewat Google OAuth) → tolak eksplisit
        if (is_null($user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini belum mengaktifkan login manual. Silakan login dengan Google UNSIL, lalu atur password di halaman Profil.'],
            ]);
        }

        // Password tidak cocok → pesan umum
        if (! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Data user yang sedang login (berdasarkan Bearer token aktif).
     */
    public function me(Request $request): JsonResponse
    {
        // Muat profil sesuai role agar halaman Profil bisa menampilkan data diri.
        return response()->json($request->user()->load(['dosen', 'mahasiswa']));
    }

    /**
     * Logout: hapus token Sanctum yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * Atur password pertama kali (akun lahir lewat Google OAuth, password masih NULL).
     * Tidak butuh password lama. Lihat 3_SDD.md 2.1 (alur set password pertama kali).
     */
    public function setPassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Sudah punya password → arahkan ke ubah password, bukan set ulang
        if (! is_null($user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password sudah pernah diatur. Gunakan menu Ubah Password.'],
            ]);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Cast 'hashed' pada model meng-hash otomatis saat disimpan.
        $user->update(['password' => $validated['password']]);

        return response()->json([
            'message' => 'Password berhasil diatur. Anda kini bisa login manual dengan email & password.',
        ]);
    }

    /**
     * Ubah password yang sudah ada — wajib menyertakan password lama yang cocok.
     * Lihat 3_SDD.md 2.1 (alur ganti password).
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Belum punya password → harus set dulu lewat set-password
        if (is_null($user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Akun belum memiliki password. Gunakan menu Atur Password terlebih dahulu.'],
            ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak cocok.'],
            ]);
        }

        $user->update(['password' => $validated['password']]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }
}
