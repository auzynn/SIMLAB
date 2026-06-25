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
        return response()->json($request->user());
    }

    /**
     * Logout: hapus token Sanctum yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
