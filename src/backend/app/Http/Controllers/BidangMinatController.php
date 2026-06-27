<?php

namespace App\Http\Controllers;

use App\Models\BidangMinat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Master Bidang Minat — dikelola Admin/Supervisor (Gate `manage-bidang-minat`).
 * Read dibuka untuk semua user terautentikasi (dipakai dropdown Edit Profil Dosen).
 */
class BidangMinatController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => BidangMinat::orderBy('nama')->get(),
            'message' => 'Berhasil mengambil data bidang minat.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manage-bidang-minat');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('bidang_minat', 'nama')],
        ]);

        $bidang = BidangMinat::create($data);

        return response()->json([
            'data' => $bidang,
            'message' => 'Bidang riset berhasil ditambahkan.',
        ], 201);
    }

    public function update(Request $request, BidangMinat $bidangMinat): JsonResponse
    {
        Gate::authorize('manage-bidang-minat');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('bidang_minat', 'nama')->ignore($bidangMinat->id)],
        ]);

        $bidangMinat->update($data);

        return response()->json([
            'data' => $bidangMinat,
            'message' => 'Bidang riset berhasil diperbarui.',
        ]);
    }

    public function destroy(BidangMinat $bidangMinat): JsonResponse
    {
        Gate::authorize('manage-bidang-minat');

        // Pivot otomatis terhapus karena cascadeOnDelete pada dosen_bidang_minat.
        $bidangMinat->delete();

        return response()->json(['message' => 'Bidang riset berhasil dihapus.']);
    }
}
