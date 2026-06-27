<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDosenRequest;
use App\Models\Dosen;
use Illuminate\Http\JsonResponse;

/**
 * Profil dosen — daftar & detail publik (halaman Profil Dosen), update terbatas
 * pemilik/Admin/Supervisor lewat DosenPolicy (3_SDD.md 5.3).
 */
class DosenController extends Controller
{
    /**
     * Daftar semua dosen untuk halaman Daftar Dosen. Publik.
     * Eager load `user` (nama/email/avatar) — wajib per catatan SDD 3.2.
     */
    public function index(): JsonResponse
    {
        $dosen = Dosen::with(['user', 'bidangMinat'])
            ->get()
            ->sortBy(fn ($d) => $d->user?->name)
            ->values();

        return response()->json([
            'data' => $dosen,
            'message' => 'Berhasil mengambil daftar dosen.',
        ]);
    }

    /**
     * Detail satu dosen (halaman Biografi/Detail Dosen). Publik.
     */
    public function show(Dosen $dosen): JsonResponse
    {
        $dosen->load(['user', 'bidangMinat']);

        return response()->json([
            'data' => $dosen,
            'message' => 'Berhasil mengambil detail dosen.',
        ]);
    }

    /**
     * Update profil dosen — pemilik atau Admin/Supervisor (DosenPolicy).
     * `name`/`no_telp` ditulis ke akun `users`, sisanya ke tabel `dosen`.
     */
    public function update(UpdateDosenRequest $request, Dosen $dosen): JsonResponse
    {
        $data = $request->validated();

        // Pisahkan kolom akun (users) dari kolom profil dosen
        $userFields = array_intersect_key($data, array_flip(['name', 'no_telp']));
        if (! empty($userFields) && $dosen->user) {
            $dosen->user->update($userFields);
        }

        $dosenFields = array_diff_key($data, array_flip(['name', 'no_telp']));
        if (! empty($dosenFields)) {
            $dosen->update($dosenFields);
        }

        return response()->json([
            'data' => $dosen->fresh()->load(['user', 'bidangMinat']),
            'message' => 'Profil dosen berhasil diperbarui.',
        ]);
    }
}
