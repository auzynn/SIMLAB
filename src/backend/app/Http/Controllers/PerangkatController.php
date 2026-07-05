<?php

namespace App\Http\Controllers;

use App\Models\Perangkat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Data master perangkat lab (3_SDD.md 3.9, 5.9).
 * Read dibuka untuk semua user terautentikasi (dipakai form peminjaman);
 * CUD via Gate `manage-master-data` (Admin/Supervisor).
 */
class PerangkatController extends Controller
{
    private const STATUS = ['tersedia', 'dipinjam', 'perbaikan'];

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Perangkat::orderBy('nama_perangkat')->get(),
            'message' => 'Berhasil mengambil data perangkat.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $data = $request->validate([
            'nama_perangkat' => ['required', 'string', 'max:255'],
            'nomor_seri' => ['required', 'string', 'max:255', 'unique:perangkat,nomor_seri'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUS)],
        ]);

        $perangkat = Perangkat::create($data);

        return response()->json([
            'data' => $perangkat,
            'message' => 'Perangkat berhasil ditambahkan.',
        ], 201);
    }

    public function update(Request $request, Perangkat $perangkat): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $data = $request->validate([
            'nama_perangkat' => ['required', 'string', 'max:255'],
            'nomor_seri' => ['required', 'string', 'max:255', Rule::unique('perangkat', 'nomor_seri')->ignore($perangkat->id)],
            'kategori' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUS)],
        ]);

        $perangkat->update($data);

        return response()->json([
            'data' => $perangkat,
            'message' => 'Perangkat berhasil diperbarui.',
        ]);
    }

    public function destroy(Perangkat $perangkat): JsonResponse
    {
        Gate::authorize('manage-master-data');

        // 3_SDD.md 5.9 — tolak hapus bila status bukan `tersedia` atau masih ada
        // peminjaman aktif (menunggu/disetujui) yang merujuk perangkat ini.
        if ($perangkat->status !== 'tersedia') {
            return response()->json([
                'message' => 'Perangkat tidak dapat dihapus karena statusnya '.$perangkat->status.'.',
            ], 422);
        }

        $adaPeminjamanAktif = $perangkat->peminjaman()
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->exists();

        if ($adaPeminjamanAktif) {
            return response()->json([
                'message' => 'Perangkat tidak dapat dihapus karena masih ada peminjaman aktif.',
            ], 422);
        }

        $perangkat->delete();

        return response()->json(['message' => 'Perangkat berhasil dihapus.']);
    }
}
