<?php

namespace App\Http\Controllers;

use App\Models\PerpanjanganPeminjaman;
use App\Services\NotifikasiService;
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
    public function __construct(private NotifikasiService $notifikasi) {}

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
            $peminjaman = $perpanjanganPeminjaman->peminjaman;
            $peminjaman?->update([
                'tanggal_kembali_rencana' => $perpanjanganPeminjaman->tanggal_kembali_baru->toDateString(),
            ]);

            if ($peminjaman) {
                $this->notifikasi->kirim(
                    $peminjaman->user_id,
                    'Perpanjangan disetujui',
                    'Pengajuan perpanjangan Anda disetujui. Tanggal kembali baru: '.$perpanjanganPeminjaman->tanggal_kembali_baru->format('d-m-Y').'.',
                    'status_pengajuan',
                    $perpanjanganPeminjaman->id,
                );
            }
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

        DB::transaction(function () use ($perpanjanganPeminjaman) {
            $perpanjanganPeminjaman->update([
                'status' => 'ditolak',
                'disetujui_oleh' => request()->user()->id,
            ]);

            $peminjaman = $perpanjanganPeminjaman->peminjaman;
            if ($peminjaman) {
                $this->notifikasi->kirim(
                    $peminjaman->user_id,
                    'Perpanjangan ditolak',
                    'Pengajuan perpanjangan peminjaman perangkat Anda ditolak.',
                    'status_pengajuan',
                    $perpanjanganPeminjaman->id,
                );
            }
        });

        return response()->json([
            'data' => $perpanjanganPeminjaman->load(['peminjaman', 'penyetuju']),
            'message' => 'Perpanjangan ditolak.',
        ]);
    }
}
