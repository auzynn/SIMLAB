<?php

namespace App\Http\Controllers;

use App\Models\PerpanjanganPeminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Persetujuan perpanjangan waktu pinjam perangkat (SRS UC-03, 3_SDD.md 3.11, 5.9).
 * Approve/reject: Admin/Supervisor (Gate approve-peminjaman-perangkat).
 * Saat disetujui, tanggal_kembali_rencana pada peminjaman induk diperbarui otomatis (T4.9).
 */
class PerpanjanganController extends Controller
{
    public function approve(PerpanjanganPeminjaman $perpanjanganPeminjaman): JsonResponse
    {
        Gate::authorize('approve-peminjaman-perangkat');

        if ($perpanjanganPeminjaman->status !== 'menunggu') {
            return response()->json(['message' => 'Perpanjangan sudah diproses sebelumnya.'], 422);
        }

        DB::transaction(function () use ($perpanjanganPeminjaman) {
            $perpanjanganPeminjaman->update([
                'status' => 'disetujui',
                'disetujui_oleh' => request()->user()->id,
            ]);

            // T4.9 (SRS UC-03): perbarui tanggal kembali rencana peminjaman induk.
            $perpanjanganPeminjaman->peminjaman?->update([
                'tanggal_kembali_rencana' => $perpanjanganPeminjaman->tanggal_kembali_baru->toDateString(),
            ]);
        });

        return response()->json([
            'data' => $perpanjanganPeminjaman->load(['peminjaman', 'penyetuju']),
            'message' => 'Perpanjangan disetujui, tanggal kembali rencana diperbarui.',
        ]);
    }

    public function reject(PerpanjanganPeminjaman $perpanjanganPeminjaman): JsonResponse
    {
        Gate::authorize('approve-peminjaman-perangkat');

        if ($perpanjanganPeminjaman->status !== 'menunggu') {
            return response()->json(['message' => 'Perpanjangan sudah diproses sebelumnya.'], 422);
        }

        $perpanjanganPeminjaman->update([
            'status' => 'ditolak',
            'disetujui_oleh' => request()->user()->id,
        ]);

        return response()->json([
            'data' => $perpanjanganPeminjaman->load(['peminjaman', 'penyetuju']),
            'message' => 'Perpanjangan ditolak.',
        ]);
    }
}
