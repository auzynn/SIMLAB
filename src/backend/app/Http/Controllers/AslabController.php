<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Delegasi Asisten Lab (Aslab) — Admin menetapkan mahasiswa menjadi Supervisor.
 * Sengaja dibatasi hanya transisi mahasiswa↔supervisor (bukan ubah role bebas)
 * dan hanya Admin (Gate manage-users), agar aman sesuai matriks RBAC.
 */
class AslabController extends Controller
{
    private const RELASI = 'mahasiswa:id,user_id,npm,angkatan,prodi';

    /**
     * Kandidat (mahasiswa) + Aslab aktif (supervisor yang berasal dari mahasiswa).
     */
    public function index(): JsonResponse
    {
        Gate::authorize('manage-users');

        $kandidat = User::where('role', 'mahasiswa')->with(self::RELASI)->orderBy('name')->get();
        $aslab = User::where('role', 'supervisor')->whereHas('mahasiswa')->with(self::RELASI)->orderBy('name')->get();

        return response()->json([
            'data' => ['kandidat' => $kandidat, 'aslab' => $aslab],
            'message' => 'Berhasil mengambil data delegasi Aslab.',
        ]);
    }

    /**
     * Jadikan mahasiswa sebagai Aslab (Supervisor). Profil mahasiswa dipertahankan
     * agar bisa dikembalikan. Hanya berlaku untuk akun berperan mahasiswa.
     */
    public function promote(User $user): JsonResponse
    {
        Gate::authorize('manage-users');

        if ($user->role !== 'mahasiswa') {
            return response()->json(['message' => 'Hanya akun mahasiswa yang dapat dijadikan Asisten Lab.'], 422);
        }

        $user->update(['role' => 'supervisor']);

        return response()->json([
            'data' => $user->load(self::RELASI),
            'message' => "{$user->name} ditetapkan sebagai Asisten Lab (Supervisor).",
        ]);
    }

    /**
     * Kembalikan Aslab menjadi Mahasiswa — hanya untuk Supervisor yang berasal dari mahasiswa.
     */
    public function demote(User $user): JsonResponse
    {
        Gate::authorize('manage-users');

        if ($user->role !== 'supervisor' || ! $user->mahasiswa) {
            return response()->json(['message' => 'Hanya Asisten Lab (dari mahasiswa) yang dapat dikembalikan.'], 422);
        }

        $user->update(['role' => 'mahasiswa']);

        return response()->json([
            'data' => $user->load(self::RELASI),
            'message' => "{$user->name} dikembalikan menjadi Mahasiswa.",
        ]);
    }
}
