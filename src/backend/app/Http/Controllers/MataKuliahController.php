<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Data master mata kuliah/praktikum (3_SDD.md 3.6, 5.6).
 * Read dibuka untuk semua user terautentikasi (Dosen memilih saat membuka Kelas Lab);
 * CUD via Gate `manage-master-data` (Admin/Supervisor).
 */
class MataKuliahController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => MataKuliah::orderBy('nama_mk')->get(),
            'message' => 'Berhasil mengambil data mata kuliah.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $data = $request->validate([
            'kode_mk' => ['nullable', 'string', 'max:50', Rule::unique('mata_kuliah', 'kode_mk')],
            'nama_mk' => ['required', 'string', 'max:255'],
            'sks' => ['nullable', 'integer', 'min:0', 'max:24'],
        ]);

        $mataKuliah = MataKuliah::create($data);

        return response()->json([
            'data' => $mataKuliah,
            'message' => 'Mata kuliah berhasil ditambahkan.',
        ], 201);
    }

    public function update(Request $request, MataKuliah $mataKuliah): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $data = $request->validate([
            'kode_mk' => ['nullable', 'string', 'max:50', Rule::unique('mata_kuliah', 'kode_mk')->ignore($mataKuliah->id)],
            'nama_mk' => ['required', 'string', 'max:255'],
            'sks' => ['nullable', 'integer', 'min:0', 'max:24'],
        ]);

        $mataKuliah->update($data);

        return response()->json([
            'data' => $mataKuliah,
            'message' => 'Mata kuliah berhasil diperbarui.',
        ]);
    }

    public function destroy(MataKuliah $mataKuliah): JsonResponse
    {
        Gate::authorize('manage-master-data');

        // ponytail: kelas_lab merujuk mata_kuliah via FK cascadeOnDelete (3_SDD.md 3.7);
        // saat modul Kelas Lab aktif (T3.12), pertimbangkan tolak hapus bila masih dipakai
        // sesi aktif alih-alih cascade diam-diam.
        $mataKuliah->delete();

        return response()->json(['message' => 'Mata kuliah berhasil dihapus.']);
    }
}
