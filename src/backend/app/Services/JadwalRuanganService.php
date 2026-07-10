<?php

namespace App\Services;

use App\Models\KelasLab;
use App\Models\PeminjamanRuangan;
use App\Models\Ruangan;
use Illuminate\Support\Carbon;

/**
 * Deteksi bentrok jadwal ruangan — dipakai bersama oleh modul Peminjaman Ruangan
 * (UC-02) dan Kelas Lab (UC-02a). Sumber bentrok ada dua: peminjaman_ruangan
 * berstatus `disetujui` (slot tanggal+jam spesifik) dan kelas_lab aktif (pola
 * mingguan hari+jam sepanjang rentang semester). Lihat 3_SDD.md 3.5 & 3.7.
 *
 * Catatan: pencocokan hari (day-of-week) untuk peminjaman dilakukan di PHP, bukan
 * lewat fungsi tanggal SQL, agar konsisten antara SQLite (test) dan MySQL (prod).
 * Jam dibandingkan sebagai string format `H:i:s` — pemanggil wajib menormalkan.
 */
class JadwalRuanganService
{
    // ISO-8601 day-of-week (1=Senin .. 7=Minggu) → nilai enum `hari` pada kelas_lab.
    private const HARI = [
        1 => 'senin',
        2 => 'selasa',
        3 => 'rabu',
        4 => 'kamis',
        5 => 'jumat',
        6 => 'sabtu',
        7 => 'minggu',
    ];

    /**
     * Nama hari (enum) dari sebuah tanggal.
     */
    public function hariDari(Carbon|string $tanggal): string
    {
        return self::HARI[Carbon::parse($tanggal)->dayOfWeekIso];
    }

    /**
     * Normalkan jam ke format `H:i:s` agar perbandingan string konsisten.
     */
    public function jam(string $jam): string
    {
        return Carbon::createFromFormat('H:i:s', mb_substr($jam, 0, 5).':00')->format('H:i:s');
    }

    /**
     * Apakah peminjaman pada (ruangan, tanggal, rentang jam) bentrok / tidak muat?
     *
     * Ruangan boleh dibagi beberapa peminjaman selama kapasitas (jumlah komputer)
     * belum habis: 1 peminjaman disetujui = 1 kursi. Slot dianggap bentrok bila
     * jumlah peminjaman disetujui yang tumpang tindih sudah mencapai kapasitas,
     * ATAU ada Kelas Lab overlap (praktikum memblok ruangan penuh — tidak dibagi).
     *
     * Kapasitas `null`/`0` diperlakukan sebagai 1 (eksklusif) — aman untuk data lama.
     *
     * Catatan: hitungan memakai jumlah peminjaman yang overlap dengan slot baru, bukan
     * konkurensi maksimum sesungguhnya. Ini konservatif (bisa sedikit lebih ketat bila
     * ada banyak sub-interval), memadai & aman untuk skala aplikasi ini.
     *
     * @param  int|null  $abaikanPeminjamanId  peminjaman yang dikecualikan (mis. saat approve ulang dirinya)
     */
    public function peminjamanBentrok(
        int $ruanganId,
        Carbon|string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?int $abaikanPeminjamanId = null,
    ): bool {
        $tanggal = Carbon::parse($tanggal)->format('Y-m-d');
        $jamMulai = $this->jam($jamMulai);
        $jamSelesai = $this->jam($jamSelesai);

        // (1) Kelas Lab aktif pada hari yang sama, jam overlap, tanggal dalam rentang semester.
        //     Praktikum memblok ruangan secara penuh — tidak boleh dibagi peminjaman lain.
        $hari = $this->hariDari($tanggal);

        $bentrokKelas = KelasLab::query()
            ->where('ruangan_id', $ruanganId)
            ->where('hari', $hari)
            ->whereDate('tanggal_mulai_semester', '<=', $tanggal)
            ->whereDate('tanggal_selesai_semester', '>=', $tanggal)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->exists();

        if ($bentrokKelas) {
            return true;
        }

        // (2) Kapasitas: hitung peminjaman disetujui lain yang tumpang tindih pada slot ini.
        //     Penuh bila jumlah tsb sudah >= kapasitas ruangan.
        $kapasitas = max(1, (int) (Ruangan::whereKey($ruanganId)->value('kapasitas') ?? 1));

        $terpakai = PeminjamanRuangan::query()
            ->where('ruangan_id', $ruanganId)
            ->where('status', 'disetujui')
            ->whereDate('tanggal', $tanggal)
            ->when($abaikanPeminjamanId, fn ($q) => $q->where('id', '!=', $abaikanPeminjamanId))
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->count();

        return $terpakai >= $kapasitas;
    }

    /**
     * Apakah kelas (pola mingguan hari+jam sepanjang rentang semester) bentrok di ruangan yang sama?
     *
     * @param  int|null  $abaikanKelasId  kelas yang dikecualikan (mis. saat update dirinya)
     */
    public function kelasBentrok(
        int $ruanganId,
        string $hari,
        string $jamMulai,
        string $jamSelesai,
        Carbon|string $tanggalMulai,
        Carbon|string $tanggalSelesai,
        ?int $abaikanKelasId = null,
    ): bool {
        $jamMulai = $this->jam($jamMulai);
        $jamSelesai = $this->jam($jamSelesai);
        $tanggalMulai = Carbon::parse($tanggalMulai)->format('Y-m-d');
        $tanggalSelesai = Carbon::parse($tanggalSelesai)->format('Y-m-d');

        // (1) Kelas lain: hari sama, jam overlap, rentang semester saling tumpang tindih
        $bentrokKelas = KelasLab::query()
            ->where('ruangan_id', $ruanganId)
            ->where('hari', $hari)
            ->when($abaikanKelasId, fn ($q) => $q->where('id', '!=', $abaikanKelasId))
            ->whereDate('tanggal_mulai_semester', '<=', $tanggalSelesai)
            ->whereDate('tanggal_selesai_semester', '>=', $tanggalMulai)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->exists();

        if ($bentrokKelas) {
            return true;
        }

        // (2) Peminjaman disetujui yang jatuh di hari (day-of-week) sama, dalam rentang semester, jam overlap.
        //     Filter hari di PHP karena fungsi DAYOFWEEK berbeda antar database.
        return PeminjamanRuangan::query()
            ->where('ruangan_id', $ruanganId)
            ->where('status', 'disetujui')
            ->whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->get(['id', 'tanggal'])
            ->contains(fn ($p) => $this->hariDari($p->tanggal) === $hari);
    }
}
