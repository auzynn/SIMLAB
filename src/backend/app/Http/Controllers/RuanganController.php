<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Data master ruangan lab (3_SDD.md 3.4, 5.5).
 * Read dibuka untuk semua user terautentikasi (dipakai form peminjaman & kelas lab);
 * CUD via Gate `manage-master-data` (Admin/Supervisor).
 */
class RuanganController extends Controller
{
    private const STATUS = ['tersedia', 'dipakai', 'perbaikan'];

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Ruangan::orderBy('nama_ruangan')->get(),
            'message' => 'Berhasil mengambil data ruangan.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $data = $request->validate([
            'nama_ruangan' => ['required', 'string', 'max:255'],
            'kapasitas' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(self::STATUS)],
        ]);

        $ruangan = Ruangan::create($data);

        return response()->json([
            'data' => $ruangan,
            'message' => 'Ruangan berhasil ditambahkan.',
        ], 201);
    }

    public function update(Request $request, Ruangan $ruangan): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $data = $request->validate([
            'nama_ruangan' => ['required', 'string', 'max:255'],
            'kapasitas' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(self::STATUS)],
        ]);

        $ruangan->update($data);

        return response()->json([
            'data' => $ruangan,
            'message' => 'Ruangan berhasil diperbarui.',
        ]);
    }

    public function destroy(Ruangan $ruangan): JsonResponse
    {
        Gate::authorize('manage-master-data');

        // ponytail: 3_SDD.md 5.5 — tolak hapus bila masih ada peminjaman_ruangan atau
        // kelas_lab aktif yang merujuk ruangan ini. Cek ditambahkan saat tabel tsb dibuat
        // (FASE 3 lanjutan: T3.5/T3.12); kini hapus diperbolehkan langsung.
        $ruangan->delete();

        return response()->json(['message' => 'Ruangan berhasil dihapus.']);
    }
}
