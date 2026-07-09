<?php

namespace App\Services;

use App\Models\KelasLab;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menyusun Rekap Tugas Kelas Lab per pertemuan selama satu semester.
 *
 * Dipakai bersama oleh:
 *  - Ringkasan kepatuhan per kelas (badge di halaman Kelas Lab, endpoint lama /kelas-lab/rekap-tugas)
 *  - Laporan Rekap Tugas (JSON/PDF/Excel) — endpoint /rekap-tugas*
 *
 * Data selalu dihitung on-request sehingga selalu mencerminkan tugas terbaru
 * ("update terus-menerus" tanpa penyimpanan snapshot).
 */
class RekapTugasService
{
    private const TOTAL_PERTEMUAN = 16;

    /**
     * Ringkasan kepatuhan per kelas (rich). Satu baris per kelas.
     * Dosen hanya melihat kelasnya sendiri; Supervisor/Admin melihat semua.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function ringkasan(User $user): Collection
    {
        $kelas = KelasLab::query()
            ->when($user->role === 'dosen', fn ($q) => $q->where('dosen_id', $user->dosen?->id))
            ->withCount(['peserta as peserta_disetujui_count' => fn ($q) => $q->where('status', 'disetujui')])
            ->with(['mataKuliah', 'dosen.user', 'deadlinePertemuan'])
            ->get();

        $now = Carbon::now('Asia/Jakarta');
        $nowWib = $now->format('Y-m-d H:i:s');
        $pengumpul = $this->pengumpulUnikPerPertemuan($kelas->pluck('id'));

        return $kelas->map(function ($k) use ($nowWib, $now, $pengumpul) {
            $peserta = (int) $k->peserta_disetujui_count;

            // Hanya pertemuan yang punya deadline dihitung sebagai "tugas".
            $bertugas = $k->deadlinePertemuan->filter(fn ($d) => $d->deadline !== null);
            $lewat = $bertugas->filter(fn ($d) => $d->deadline->format('Y-m-d H:i:s') < $nowWib);

            $tunggakan = $lewat->sum(function ($d) use ($peserta, $pengumpul, $k) {
                $sudah = $pengumpul[$k->id][$d->pertemuan] ?? 0;

                return max(0, $peserta - $sudah);
            });

            // Status kepatuhan 3 tingkat (identik dengan rekapTugas lama).
            $akanDatang = $bertugas->count() - $lewat->count();
            if ($tunggakan > 0) {
                $status = 'perhatian';
            } elseif ($akanDatang > 0) {
                $status = 'berjalan';
            } else {
                $status = 'beres';
            }

            return [
                'kelas_lab_id' => $k->id,
                'mata_kuliah' => $k->mataKuliah?->nama_mk,
                'nama_sesi' => $k->nama_sesi,
                'dosen' => $k->dosen?->user?->name,
                'hari' => $k->hari,
                'jam' => substr((string) $k->jam_mulai, 0, 5).'–'.substr((string) $k->jam_selesai, 0, 5),
                'peserta_disetujui' => $peserta,
                'total_tugas' => $bertugas->count(),                        // alias kompatibilitas UI lama
                'pertemuan_bertugas' => $bertugas->count(),                 // X/16 diberi tugas
                'pertemuan_berjalan' => $this->pertemuanBerjalan($k, $now),  // pertemuan ke-N/16
                'tunggakan' => $tunggakan,
                'perlu_perhatian' => $tunggakan > 0,
                'status' => $status,   // 'perhatian' | 'berjalan' | 'beres'
                'deadline_terdekat' => ($t = $bertugas->min('deadline')) ? $t->format('Y-m-d H:i:s') : null,
            ];
        })->values();
    }

    /**
     * Laporan lengkap: ringkasan semua kelas + detail matriks per kelas
     * (peserta × pertemuan bertugas: tepat/telat/belum).
     *
     * @return array{generated_at: string, ringkasan: array, detail: array}
     */
    public function build(User $user): array
    {
        $ringkasan = $this->ringkasan($user);

        $kelas = KelasLab::query()
            ->when($user->role === 'dosen', fn ($q) => $q->where('dosen_id', $user->dosen?->id))
            ->with([
                'mataKuliah',
                'dosen.user',
                'deadlinePertemuan',
                'peserta' => fn ($q) => $q->where('status', 'disetujui')->with('mahasiswa.user'),
            ])
            ->get();

        // Semua pengumpulan tugas untuk kelas terkait, di-index [kelas][pertemuan][mahasiswa] => Tugas.
        $tugas = Tugas::query()
            ->whereIn('kelas_lab_id', $kelas->pluck('id'))
            ->orderBy('created_at')
            ->get();
        $tugasIndex = [];
        foreach ($tugas as $t) {
            // Simpan pengumpulan pertama (paling awal) per (kelas, pertemuan, mahasiswa).
            $tugasIndex[$t->kelas_lab_id][$t->pertemuan][$t->mahasiswa_id] ??= $t;
        }

        $detail = $kelas->map(function ($k) use ($tugasIndex) {
            // Kolom matriks = pertemuan yang diberi tugas (punya deadline), urut menaik.
            $pertemuanBertugas = $k->deadlinePertemuan
                ->filter(fn ($d) => $d->deadline !== null)
                ->sortBy('pertemuan')
                ->values();

            $kolom = $pertemuanBertugas->map(fn ($d) => [
                'pertemuan' => $d->pertemuan,
                'materi' => $d->materi,
                'deadline' => $d->deadline->format('Y-m-d H:i:s'),
            ])->all();

            $peserta = $k->peserta
                ->sortBy(fn ($p) => $p->mahasiswa?->npm)
                ->map(function ($p) use ($k, $pertemuanBertugas, $tugasIndex) {
                    $sel = [];
                    $totalKumpul = 0;
                    $totalTelat = 0;

                    foreach ($pertemuanBertugas as $d) {
                        $t = $tugasIndex[$k->id][$d->pertemuan][$p->mahasiswa_id] ?? null;

                        if (! $t) {
                            $sel[$d->pertemuan] = ['status' => 'belum'];

                            continue;
                        }

                        $telat = $t->created_at->format('Y-m-d H:i:s') > $d->deadline->format('Y-m-d H:i:s');
                        $totalKumpul++;
                        $totalTelat += $telat ? 1 : 0;
                        $sel[$d->pertemuan] = [
                            'status' => $telat ? 'telat' : 'tepat',
                            'judul' => $t->judul,
                            'tautan' => $t->tautan,
                            'dikumpulkan' => $t->created_at->format('Y-m-d H:i:s'),
                        ];
                    }

                    return [
                        'npm' => $p->mahasiswa?->npm,
                        'nama' => $p->mahasiswa?->user?->name,
                        'prodi' => $p->mahasiswa?->prodi,
                        'sel' => $sel,
                        'total_kumpul' => $totalKumpul,
                        'telat' => $totalTelat,
                    ];
                })->values();

            return [
                'kelas_lab_id' => $k->id,
                'mata_kuliah' => $k->mataKuliah?->nama_mk,
                'nama_sesi' => $k->nama_sesi,
                'dosen' => $k->dosen?->user?->name,
                'hari' => $k->hari,
                'jam' => substr((string) $k->jam_mulai, 0, 5).'–'.substr((string) $k->jam_selesai, 0, 5),
                'pertemuan' => $kolom,
                'peserta' => $peserta,
            ];
        })->values();

        return [
            'generated_at' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'ringkasan' => $ringkasan->all(),
            'detail' => $detail->all(),
        ];
    }

    /**
     * Jumlah pengumpul unik per (kelas, pertemuan) dalam satu query agregat.
     *
     * @param  Collection<int, int>  $kelasIds
     * @return array<int, array<int, int>>
     */
    private function pengumpulUnikPerPertemuan(Collection $kelasIds): array
    {
        $pengumpul = [];
        Tugas::query()
            ->whereIn('kelas_lab_id', $kelasIds)
            ->selectRaw('kelas_lab_id, pertemuan, COUNT(DISTINCT mahasiswa_id) as n')
            ->groupBy('kelas_lab_id', 'pertemuan')
            ->get()
            ->each(function ($row) use (&$pengumpul) {
                $pengumpul[$row->kelas_lab_id][$row->pertemuan] = (int) $row->n;
            });

        return $pengumpul;
    }

    /**
     * Progres pertemuan berjalan dari jadwal mingguan (mulai semester → sekarang), 0..16.
     */
    private function pertemuanBerjalan(KelasLab $k, Carbon $now): int
    {
        $mulai = $k->tanggal_mulai_semester;
        if (! $mulai) {
            return 0;
        }
        if ($now->lt($mulai->startOfDay())) {
            return 0; // semester belum mulai
        }
        $minggu = $mulai->startOfDay()->diffInWeeks($now, false);

        return max(0, min(self::TOTAL_PERTEMUAN, (int) floor($minggu) + 1));
    }
}
