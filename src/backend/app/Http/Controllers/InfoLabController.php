<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInfoLabRequest;
use App\Models\InfoLab;
use Illuminate\Http\JsonResponse;

/**
 * Konten halaman informasi lab — baca publik, update khusus Admin (3_SDD.md 5.12).
 */
class InfoLabController extends Controller
{
    /**
     * Ambil konten satu tipe. Publik (dipakai halaman informasi tanpa login).
     * Tipe sudah dibatasi enum lewat constraint route.
     */
    public function show(string $tipe): JsonResponse
    {
        $info = InfoLab::where('tipe', $tipe)->firstOrFail();

        // Tipe kepala_lab boleh ditautkan ke entri dosen → dirender sebagai kartu identitas.
        if ($tipe === 'kepala_lab' && $info->dosen_id) {
            $info->load(['dosen.user', 'dosen.bidangMinat']);
        }

        return response()->json([
            'data' => $info,
            'message' => 'Berhasil mengambil konten.',
        ]);
    }

    /**
     * Update konten satu tipe (Admin). Dibuat jika belum ada (upsert by tipe).
     */
    public function update(UpdateInfoLabRequest $request, string $tipe): JsonResponse
    {
        $info = InfoLab::updateOrCreate(
            ['tipe' => $tipe],
            [...$request->validated(), 'updated_by' => $request->user()->id],
        );

        return response()->json([
            'data' => $info,
            'message' => 'Konten berhasil diperbarui.',
        ]);
    }
}
