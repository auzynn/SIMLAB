<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\PeminjamanPerangkat;
use Illuminate\Support\Carbon;

/**
 * Pengingat pengembalian perangkat (SRS UC-03/UC-07). Saat peminjaman berstatus
 * 'disetujui' mencapai/melewati tanggal_kembali_rencana dan belum dikembalikan,
 * kirim notifikasi ke peminjam agar mengembalikan sebelum lab tutup pukul 17.00.
 *
 * Idempotent: maksimal satu pengingat per peminjaman per hari — aman dipanggil
 * berulang, baik oleh command terjadwal maupun lazy saat user membuka lonceng.
 */
class PengingatPengembalianService
{
    public function __construct(private NotifikasiService $notifikasi) {}

    /**
     * Buat pengingat untuk peminjaman yang sudah jatuh tempo.
     * Bila $userId diisi, hanya untuk peminjam tsb (dipakai jalur lazy per-user).
     * Mengembalikan jumlah pengingat yang benar-benar dibuat.
     */
    public function generate(?int $userId = null): int
    {
        $today = Carbon::today()->toDateString();

        $peminjaman = PeminjamanPerangkat::query()
            ->with('perangkat')
            ->where('status', 'disetujui')
            ->whereNull('tanggal_kembali_aktual')
            ->whereDate('tanggal_kembali_rencana', '<=', $today)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->get();

        $dibuat = 0;

        foreach ($peminjaman as $p) {
            // Sudah ada pengingat untuk peminjaman ini hari ini? → lewati (tak spam).
            $sudahAda = Notifikasi::query()
                ->where('tipe', 'pengingat')
                ->where('referensi_id', $p->id)
                ->whereDate('created_at', $today)
                ->exists();

            if ($sudahAda) {
                continue;
            }

            $nama = $p->perangkat?->nama_perangkat ?? 'perangkat';

            $this->notifikasi->kirim(
                $p->user_id,
                'Pengingat Pengembalian Perangkat',
                "Peminjaman {$nama} telah mencapai batas waktu pengembalian. ".
                    'Silakan dikembalikan sebelum jam operasional Lab tutup pukul 17.00.',
                'pengingat',
                $p->id,
            );

            $dibuat++;
        }

        return $dibuat;
    }
}
