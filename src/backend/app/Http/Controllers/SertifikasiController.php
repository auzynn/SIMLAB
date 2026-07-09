<?php

namespace App\Http\Controllers;

use App\Models\Sertifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Katalog informasi sertifikasi eksternal (SRS UC-05, 3_SDD.md 3.13, 5.13).
 * Modul murni informasional — tidak menangani pendaftaran (dilakukan langsung ke penyelenggara).
 * Read dibuka untuk semua role login (referensi mahasiswa); CUD via Gate `manage-master-data`
 * (Admin/Supervisor) mengikuti matriks RBAC 2_SRS.md Bagian 1.
 */
class SertifikasiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Sertifikasi::orderBy('nama_sertifikasi')->get(),
            'message' => 'Berhasil mengambil katalog sertifikasi.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $sertifikasi = Sertifikasi::create($this->validated($request));

        return response()->json([
            'data' => $sertifikasi,
            'message' => 'Sertifikasi berhasil ditambahkan.',
        ], 201);
    }

    public function update(Request $request, Sertifikasi $sertifikasi): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $sertifikasi->update($this->validated($request));

        return response()->json([
            'data' => $sertifikasi,
            'message' => 'Sertifikasi berhasil diperbarui.',
        ]);
    }

    public function destroy(Sertifikasi $sertifikasi): JsonResponse
    {
        Gate::authorize('manage-master-data');

        $sertifikasi->delete();

        return response()->json(['message' => 'Sertifikasi berhasil dihapus.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'nama_sertifikasi' => ['required', 'string', 'max:255'],
            'penyelenggara' => ['required', 'string', 'max:255'],
            'jadwal' => ['nullable', 'string', 'max:255'],
            'persyaratan' => ['nullable', 'string'],
            'tautan_pendaftaran' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
