<?php

namespace App\Services;

use App\Models\DeadlinePertemuan;
use App\Models\KelasLabPeserta;
use App\Models\Notifikasi;
use App\Models\Tugas;
use Illuminate\Support\Carbon;

/**
 * Pengingat tenggat tugas (SRS UC-07). Saat deadline sebuah pertemuan sudah terlewati
 * dan seorang peserta (disetujui) belum mengumpulkan tugas untuk pertemuan itu,
 * kirim notifikasi "tenggat terlewati" ke mahasiswa tersebut.
 *
 * Idempotent: maksimal satu notifikasi per (mahasiswa, deadline pertemuan) — aman
 * dipanggil berulang, baik oleh command terjadwal maupun lazy saat buka lonceng.
 */
class PengingatDeadlineService
{
    public function __construct(private NotifikasiService $notifikasi) {}

    /**
     * Buat pengingat untuk deadline yang sudah lewat & belum dikumpulkan.
     * Bila $userId diisi, hanya untuk mahasiswa tsb (jalur lazy per-user).
     * Mengembalikan jumlah notifikasi yang benar-benar dibuat.
     */
    public function generate(?int $userId = null): int
    {
        // Deadline disimpan sebagai wall-clock WIB; bandingkan dengan "sekarang" WIB.
        $nowWib = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');

        $deadlines = DeadlinePertemuan::query()
            ->with('kelasLab.mataKuliah')
            ->whereNotNull('deadline')
            ->where('deadline', '<', $nowWib)
            ->get();

        $dibuat = 0;

        foreach ($deadlines as $dl) {
            // Mahasiswa yang sudah mengumpulkan untuk (kelas, pertemuan) ini → dilewati.
            $sudahKumpul = Tugas::where('kelas_lab_id', $dl->kelas_lab_id)
                ->where('pertemuan', $dl->pertemuan)
                ->pluck('mahasiswa_id')
                ->all();

            // Peserta disetujui kelas ini (yang wajib mengumpulkan).
            $peserta = KelasLabPeserta::query()
                ->with('mahasiswa')
                ->where('kelas_lab_id', $dl->kelas_lab_id)
                ->where('status', 'disetujui')
                ->get();

            foreach ($peserta as $p) {
                $targetUserId = $p->mahasiswa?->user_id;
                if (! $targetUserId) {
                    continue;
                }
                // Jalur lazy: batasi ke user yang membuka lonceng.
                if ($userId && $targetUserId !== $userId) {
                    continue;
                }
                // Sudah mengumpulkan → tidak perlu pengingat.
                if (in_array($p->mahasiswa_id, $sudahKumpul, true)) {
                    continue;
                }
                // Sudah pernah diberi pengingat untuk deadline ini → tak spam.
                $sudahAda = Notifikasi::query()
                    ->where('tipe', 'pengingat')
                    ->where('referensi_id', $dl->id)
                    ->where('user_id', $targetUserId)
                    ->exists();
                if ($sudahAda) {
                    continue;
                }

                $mk = $dl->kelasLab?->mataKuliah?->nama_mk ?? 'Kelas Lab';
                $sesi = $dl->kelasLab?->nama_sesi ?? '';

                $this->notifikasi->kirim(
                    $targetUserId,
                    'Tenggat Tugas Terlewati',
                    "Batas waktu pengumpulan tugas Pertemuan {$dl->pertemuan} pada {$mk} — {$sesi} telah terlewati. ".
                        'Anda belum mengumpulkan tugas untuk pertemuan ini.',
                    'pengingat',
                    $dl->id,
                );

                $dibuat++;
            }
        }

        return $dibuat;
    }
}
