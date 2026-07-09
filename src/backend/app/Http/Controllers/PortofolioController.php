<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortofolioRequest;
use App\Http\Requests\UpdatePortofolioRequest;
use App\Models\Portofolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Portofolio riset mahasiswa (PRD 3.7, 3_SDD.md 3.14, 5.14).
 * Read dibuka untuk semua role login (Dosen mencari topik TA, dll); CUD hanya pemilik
 * (Mahasiswa) — divalidasi via Store/UpdatePortofolioRequest & pengecekan kepemilikan di destroy.
 */
class PortofolioController extends Controller
{
    /**
     * List portofolio: semua atau tersaring `?user_id=` (mis. halaman portofolio satu mahasiswa).
     */
    public function index(Request $request): JsonResponse
    {
        $portofolio = Portofolio::query()
            ->with('user:id,name,role')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->latest('tanggal')
            ->latest()
            ->get();

        return response()->json([
            'data' => $portofolio,
            'message' => 'Berhasil mengambil data portofolio.',
        ]);
    }

    /**
     * Tambah portofolio (Mahasiswa). Pemilik di-set dari user login, bukan dari input.
     */
    public function store(StorePortofolioRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $portofolio = Portofolio::create($data);

        return response()->json([
            'data' => $portofolio,
            'message' => 'Portofolio berhasil ditambahkan.',
        ], 201);
    }

    /**
     * Ubah portofolio milik sendiri (kepemilikan divalidasi di UpdatePortofolioRequest).
     */
    public function update(UpdatePortofolioRequest $request, Portofolio $portofolio): JsonResponse
    {
        $portofolio->update($request->validated());

        return response()->json([
            'data' => $portofolio,
            'message' => 'Portofolio berhasil diperbarui.',
        ]);
    }

    /**
     * Hapus portofolio milik sendiri. Mahasiswa lain / role lain ditolak (403).
     */
    public function destroy(Request $request, Portofolio $portofolio): JsonResponse
    {
        if ($request->user()->id !== $portofolio->user_id) {
            return response()->json([
                'message' => 'Anda tidak berhak menghapus portofolio ini.',
            ], 403);
        }

        $portofolio->delete();

        return response()->json(['message' => 'Portofolio berhasil dihapus.']);
    }
}
