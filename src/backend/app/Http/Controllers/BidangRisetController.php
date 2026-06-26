<?php

namespace App\Http\Controllers;

use App\Models\BidangRiset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Master Bidang Riset — dikelola Admin/Supervisor (Gate `manage-bidang-riset`).
 * Read dibuka untuk semua user terautentikasi (dipakai dropdown Edit Profil Dosen).
 */
class BidangRisetController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => BidangRiset::orderBy('nama')->get(),
            'message' => 'Berhasil mengambil data bidang riset.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manage-bidang-riset');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('bidang_riset', 'nama')],
        ]);

        $bidang = BidangRiset::create($data);

        return response()->json([
            'data' => $bidang,
            'message' => 'Bidang riset berhasil ditambahkan.',
        ], 201);
    }

    public function update(Request $request, BidangRiset $bidangRiset): JsonResponse
    {
        Gate::authorize('manage-bidang-riset');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('bidang_riset', 'nama')->ignore($bidangRiset->id)],
        ]);

        $bidangRiset->update($data);

        return response()->json([
            'data' => $bidangRiset,
            'message' => 'Bidang riset berhasil diperbarui.',
        ]);
    }

    public function destroy(BidangRiset $bidangRiset): JsonResponse
    {
        Gate::authorize('manage-bidang-riset');

        // Pivot otomatis terhapus karena cascadeOnDelete pada dosen_bidang_riset.
        $bidangRiset->delete();

        return response()->json(['message' => 'Bidang riset berhasil dihapus.']);
    }
}
