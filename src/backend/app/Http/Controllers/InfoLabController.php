<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInfoLabRequest;
use App\Models\InfoLab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    /**
     * Unggah lampiran pengumuman (Admin) → simpan di disk publik, balas URL + nama asli.
     * Dipakai opsi "File" pada editor Pengumuman; URL yang dikembalikan disimpan di konten JSON.
     */
    public function uploadLampiran(Request $request): JsonResponse
    {
        Gate::authorize('manage-info-lab');

        $request->validate([
            // Dokumen/gambar umum, maks 5MB
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,zip', 'max:5120'],
        ]);

        $file = $request->file('file');
        $ext = $file->extension() ?: $file->getClientOriginalExtension();
        // Nama acak agar tak bentrok & tak bocorkan info; nama asli disimpan sebagai label.
        $path = $file->storeAs('pengumuman', Str::uuid().'.'.$ext, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'name' => $file->getClientOriginalName(),
            'message' => 'File berhasil diunggah.',
        ]);
    }
}
