<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKelasLabRequest;
use App\Http\Requests\UpdateKelasLabRequest;
use App\Models\KelasLab;
use App\Models\KelasLabPeserta;
use App\Services\JadwalRuanganService;
use App\Services\NotifikasiService;
use App\Services\RekapTugasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Kelas Lab/Praktikum (SRS UC-02a, 3_SDD.md 3.7, 5.7).
 * - Buka/ubah/hapus: Admin (semua kelas), Supervisor (semua kelas), atau Dosen (milik sendiri)
 *   (KelasLabPolicy). Admin/Supervisor menunjuk dosen pengampu saat membuka.
 * - Kelola pendaftaran (list/approve/reject/hapus): Admin, Supervisor, atau Dosen pemilik.
 * - Daftar/batal peserta: Mahasiswa (Gate daftar-kelas-lab).
 */
class KelasLabController extends Controller
{
    public function __construct(
        private JadwalRuanganService $jadwal,
        private NotifikasiService $notifikasi,
    ) {}

    /**
     * List sesi (semua role). Filter ?mata_kuliah_id= untuk sesi paralel satu mata kuliah (T3.15).
     */
    public function index(Request $request): JsonResponse
    {
        $kelas = KelasLab::query()
            ->with(['mataKuliah', 'dosen.user', 'ruangan'])
            ->withCount(['peserta as peserta_count' => fn ($q) => $q->where('status', '!=', 'ditolak')])
            // "Bertugas" = pertemuan yang punya deadline (materi tanpa deadline tidak dihitung tugas).
            ->withCount(['deadlinePertemuan as tugas_count' => fn ($q) => $q->whereNotNull('deadline')])
            ->when($request->query('mata_kuliah_id'), fn ($q, $id) => $q->where('mata_kuliah_id', $id))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get()
            ->each->append('sisa_kuota');

        // Status pendaftaran mahasiswa ini per sesi (untuk katalog & halaman "Kelas Lab Saya").
        $mahasiswa = $request->user()->mahasiswa;
        if ($mahasiswa) {
            $statusSaya = KelasLabPeserta::where('mahasiswa_id', $mahasiswa->id)->pluck('status', 'kelas_lab_id');
            $kelas->each(function ($k) use ($statusSaya) {
                $st = $statusSaya[$k->id] ?? null;
                $k->setAttribute('status_pendaftaran', $st);
                $k->setAttribute('terdaftar', $st !== null && $st !== 'ditolak');
            });
        }

        return response()->json([
            'data' => $kelas,
            'message' => 'Berhasil mengambil data Kelas Lab.',
        ]);
    }

    /**
     * Rekap kepatuhan pengumpulan tugas per kelas (untuk Dosen/Supervisor/Admin).
     * "Perlu perhatian" bila ada pertemuan yang deadline-nya SUDAH lewat namun belum
     * semua peserta disetujui mengumpulkan. Mahasiswa → array kosong.
     */
    public function rekapTugas(Request $request, RekapTugasService $rekap): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user->role, ['dosen', 'supervisor', 'admin'], true)) {
            return response()->json(['data' => [], 'message' => 'Tidak ada rekap untuk peran ini.']);
        }

        return response()->json([
            'data' => $rekap->ringkasan($user),
            'message' => 'Berhasil mengambil rekap tugas per kelas.',
        ]);
    }

    /**
     * Detail satu sesi termasuk sisa kuota (T3.16).
     */
    public function show(KelasLab $kelasLab): JsonResponse
    {
        Gate::authorize('view', $kelasLab);

        $kelasLab->load(['mataKuliah', 'dosen.user', 'ruangan'])
            ->loadCount(['peserta as peserta_count' => fn ($q) => $q->where('status', '!=', 'ditolak')])
            ->append('sisa_kuota');

        return response()->json([
            'data' => $kelasLab,
            'message' => 'Berhasil mengambil detail Kelas Lab.',
        ]);
    }

    /**
     * Buka kelas baru (Dosen/Supervisor). Otorisasi & validasi bentrok di Form Request (T3.17).
     */
    public function store(StoreKelasLabRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['dibuat_oleh'] = $request->user()->id;
        $data['jam_mulai'] = $this->jadwal->jam($data['jam_mulai']);
        $data['jam_selesai'] = $this->jadwal->jam($data['jam_selesai']);

        $kelas = KelasLab::create($data);

        return response()->json([
            'data' => $kelas->load(['mataKuliah', 'dosen.user', 'ruangan']),
            'message' => 'Kelas Lab berhasil dibuka.',
        ], 201);
    }

    /**
     * Ubah jadwal/kuota (pemilik atau Supervisor). Otorisasi & bentrok di Form Request (T3.18).
     */
    public function update(UpdateKelasLabRequest $request, KelasLab $kelasLab): JsonResponse
    {
        $data = $request->validated();
        $data['jam_mulai'] = $this->jadwal->jam($data['jam_mulai']);
        $data['jam_selesai'] = $this->jadwal->jam($data['jam_selesai']);

        $kelasLab->update($data);

        return response()->json([
            'data' => $kelasLab->load(['mataKuliah', 'dosen.user', 'ruangan']),
            'message' => 'Kelas Lab berhasil diperbarui.',
        ]);
    }

    /**
     * Hapus kelas (pemilik atau Supervisor) (T3.18).
     */
    public function destroy(KelasLab $kelasLab): JsonResponse
    {
        Gate::authorize('delete', $kelasLab);

        $kelasLab->delete();

        return response()->json(['message' => 'Kelas Lab berhasil dihapus.']);
    }

    /**
     * Mahasiswa mendaftar sebagai peserta — tolak jika kuota penuh atau sudah terdaftar (T3.19, UC-02a).
     */
    public function daftar(Request $request, KelasLab $kelasLab): JsonResponse
    {
        Gate::authorize('daftar-kelas-lab');

        $mahasiswa = $request->user()->mahasiswa;
        if (! $mahasiswa) {
            return response()->json(['message' => 'Profil mahasiswa tidak ditemukan.'], 422);
        }

        // Baris pendaftaran di sesi ini (mungkin pernah ditolak → boleh ajukan ulang).
        $existing = KelasLabPeserta::where('kelas_lab_id', $kelasLab->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        if ($existing && $existing->status !== 'ditolak') {
            return response()->json(['message' => 'Anda sudah terdaftar / menunggu persetujuan di sesi ini.'], 422);
        }

        // Pendaftaran aktif (menunggu+disetujui) — untuk cek 1-sesi-per-matkul & bentrok jadwal.
        $kelasSaya = KelasLab::with('mataKuliah')
            ->whereIn('id', KelasLabPeserta::where('mahasiswa_id', $mahasiswa->id)->where('status', '!=', 'ditolak')->pluck('kelas_lab_id'))
            ->get();

        // Satu mata kuliah hanya boleh diambil satu sesi (boleh ambil mata kuliah lain).
        $matkulSama = $kelasSaya->firstWhere('mata_kuliah_id', $kelasLab->mata_kuliah_id);
        if ($matkulSama) {
            return response()->json([
                'message' => 'Anda sudah terdaftar pada sesi "'.$matkulSama->nama_sesi.'" untuk mata kuliah ini. Satu mata kuliah hanya boleh satu sesi.',
            ], 422);
        }

        // Tidak boleh bentrok jadwal (hari sama + jam tumpang tindih) dengan kelas lain yang sudah diambil.
        $bentrok = $kelasSaya->first(
            fn ($ks) => $ks->hari === $kelasLab->hari
                && $ks->jam_mulai < $kelasLab->jam_selesai
                && $ks->jam_selesai > $kelasLab->jam_mulai,
        );
        if ($bentrok) {
            return response()->json([
                'message' => 'Jadwal sesi ini bentrok dengan kelas Anda: '.$bentrok->mataKuliah?->nama_mk.' ('.$bentrok->nama_sesi.', '.ucfirst($bentrok->hari).' '.substr($bentrok->jam_mulai, 0, 5).'–'.substr($bentrok->jam_selesai, 0, 5).').',
            ], 422);
        }

        // Data penerima notifikasi disiapkan sebelum transaksi:
        // - dosen pengampu sesi ini → diberi tahu ada pendaftaran baru yang menunggu persetujuannya;
        // - nama mahasiswa pendaftar → dipakai pada pesan ke dosen.
        $kelasLab->loadMissing('dosen', 'mataKuliah');
        $dosenUserId = $kelasLab->dosen?->user_id;
        $namaMahasiswa = $request->user()->name;

        // Transaksi + kunci baris kelas agar hitung kuota aman (slot = menunggu + disetujui).
        $peserta = DB::transaction(function () use ($kelasLab, $mahasiswa, $existing, $dosenUserId, $namaMahasiswa) {
            $kelas = KelasLab::lockForUpdate()->find($kelasLab->id);

            if ($kelas->peserta()->where('status', '!=', 'ditolak')->count() >= $kelas->kuota) {
                return null;
            }

            // Ajukan ulang baris yang sebelumnya ditolak, atau buat baru.
            if ($existing) {
                $existing->update(['status' => 'menunggu', 'disetujui_oleh' => null]);
                $peserta = $existing;
            } else {
                $peserta = KelasLabPeserta::create([
                    'kelas_lab_id' => $kelas->id,
                    'mahasiswa_id' => $mahasiswa->id,
                    'status' => 'menunggu',
                ]);
            }

            // Notifikasi konfirmasi pendaftaran ke mahasiswa (SRS UC-07), transaksi yang sama.
            $this->notifikasi->kirim(
                $mahasiswa->user_id,
                'Pendaftaran Kelas Lab terkirim',
                'Pendaftaran Anda pada sesi "'.$kelasLab->nama_sesi.'" ('.$kelasLab->mataKuliah?->nama_mk.') terkirim, menunggu persetujuan.',
                'pendaftaran',
                $peserta->id,
            );

            // Notifikasi ke dosen pengampu bahwa ada pendaftaran baru menunggu persetujuannya.
            // Dijaga null-safe: sesi tanpa dosen (data anomali) cukup dilewati tanpa error.
            if ($dosenUserId) {
                $this->notifikasi->kirim(
                    $dosenUserId,
                    'Pendaftaran Kelas Lab baru',
                    $namaMahasiswa.' mendaftar pada sesi "'.$kelasLab->nama_sesi.'" ('.$kelasLab->mataKuliah?->nama_mk.'), menunggu persetujuan Anda.',
                    'pengajuan_masuk',
                    $peserta->id,
                );
            }

            return $peserta;
        });

        if (! $peserta) {
            return response()->json(['message' => 'Kuota sesi ini sudah penuh.'], 422);
        }

        return response()->json([
            'data' => $peserta,
            'message' => 'Pendaftaran terkirim, menunggu persetujuan dosen/supervisor.',
        ], 201);
    }

    /**
     * Mahasiswa membatalkan pendaftaran dirinya sendiri (T3.20).
     */
    public function batalDaftar(Request $request, KelasLab $kelasLab): JsonResponse
    {
        Gate::authorize('daftar-kelas-lab');

        $mahasiswa = $request->user()->mahasiswa;
        $peserta = KelasLabPeserta::where('kelas_lab_id', $kelasLab->id)
            ->where('mahasiswa_id', $mahasiswa?->id)
            ->first();

        if (! $peserta) {
            return response()->json(['message' => 'Anda belum terdaftar di sesi ini.'], 422);
        }

        // Setelah disetujui, mahasiswa tidak dapat membatalkan sendiri — harus lewat Dosen/Supervisor.
        if ($peserta->status === 'disetujui') {
            return response()->json([
                'message' => 'Pendaftaran sudah disetujui dan tidak dapat dibatalkan sendiri. Hubungi dosen/supervisor kelas.',
            ], 422);
        }

        $peserta->delete();

        return response()->json(['message' => 'Pendaftaran dibatalkan.']);
    }

    /**
     * List peserta satu sesi — pemilik kelas, Supervisor, atau Admin (T3.21, KelasLabPolicy::viewPeserta).
     */
    public function peserta(KelasLab $kelasLab): JsonResponse
    {
        Gate::authorize('viewPeserta', $kelasLab);

        $peserta = $kelasLab->peserta()->with(['mahasiswa.user', 'penyetuju'])->latest()->get();

        return response()->json([
            'data' => $peserta,
            'message' => 'Berhasil mengambil daftar peserta.',
        ]);
    }

    /**
     * List pendaftaran untuk persetujuan — Dosen (kelas miliknya), Supervisor, atau Admin (semua).
     * Filter opsional ?status=menunggu|disetujui|ditolak.
     */
    public function pendaftaran(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'dosen', 'supervisor'], true), 403, 'Anda tidak memiliki akses untuk tindakan ini.');

        $peserta = KelasLabPeserta::query()
            ->with(['mahasiswa.user', 'kelasLab.mataKuliah', 'kelasLab.dosen.user', 'penyetuju'])
            ->when($user->role === 'dosen', fn ($q) => $q->whereIn('kelas_lab_id', KelasLab::where('dosen_id', $user->dosen?->id)->pluck('id')))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->get();

        return response()->json([
            'data' => $peserta,
            'message' => 'Berhasil mengambil data pendaftaran.',
        ]);
    }

    /**
     * Setujui pendaftaran — pemilik kelas (Dosen) atau Supervisor. Tolak jika kuota disetujui penuh.
     */
    public function approvePendaftaran(Request $request, KelasLabPeserta $kelasLabPeserta): JsonResponse
    {
        $this->authorizePendaftaran($request, $kelasLabPeserta);

        // Notifikasi hanya dikirim bila status benar-benar berpindah menjadi disetujui,
        // agar approve berulang pada peserta yang sudah disetujui tidak menghasilkan notifikasi ganda.
        $perluNotifikasi = $kelasLabPeserta->status !== 'disetujui';

        if ($perluNotifikasi) {
            $disetujui = KelasLabPeserta::where('kelas_lab_id', $kelasLabPeserta->kelas_lab_id)
                ->where('status', 'disetujui')
                ->count();
            if ($disetujui >= $kelasLabPeserta->kelasLab->kuota) {
                return response()->json(['message' => 'Kuota kelas sudah penuh oleh peserta yang disetujui.'], 422);
            }
        }

        // Data penerima/isi pesan disiapkan sebelum transaksi (pola sama seperti daftar()).
        $kelasLabPeserta->loadMissing('mahasiswa', 'kelasLab.mataKuliah');

        DB::transaction(function () use ($request, $kelasLabPeserta, $perluNotifikasi) {
            $kelasLabPeserta->update(['status' => 'disetujui', 'disetujui_oleh' => $request->user()->id]);

            // Beri tahu mahasiswa bahwa pendaftarannya disetujui (null-safe untuk data anomali tanpa mahasiswa).
            if ($perluNotifikasi && $kelasLabPeserta->mahasiswa?->user_id) {
                $this->notifikasi->kirim(
                    $kelasLabPeserta->mahasiswa->user_id,
                    'Pendaftaran Kelas Lab disetujui',
                    'Pendaftaran Anda pada sesi "'.$kelasLabPeserta->kelasLab->nama_sesi.'" ('.$kelasLabPeserta->kelasLab->mataKuliah?->nama_mk.') telah disetujui.',
                    'status_pengajuan',
                    $kelasLabPeserta->kelas_lab_id,
                );
            }
        });

        return response()->json(['data' => $kelasLabPeserta, 'message' => 'Pendaftaran disetujui.']);
    }

    /**
     * Tolak pendaftaran — pemilik kelas (Dosen) atau Supervisor.
     */
    public function rejectPendaftaran(Request $request, KelasLabPeserta $kelasLabPeserta): JsonResponse
    {
        $this->authorizePendaftaran($request, $kelasLabPeserta);

        // Notifikasi hanya dikirim bila status benar-benar berpindah menjadi ditolak (hindari ganda).
        $perluNotifikasi = $kelasLabPeserta->status !== 'ditolak';

        $kelasLabPeserta->loadMissing('mahasiswa', 'kelasLab.mataKuliah');

        DB::transaction(function () use ($request, $kelasLabPeserta, $perluNotifikasi) {
            $kelasLabPeserta->update(['status' => 'ditolak', 'disetujui_oleh' => $request->user()->id]);

            // Beri tahu mahasiswa bahwa pendaftarannya ditolak. Pesan memuat kata "ditolak"
            // agar lonceng frontend menampilkan ikon merah (notification-bell iconMeta).
            if ($perluNotifikasi && $kelasLabPeserta->mahasiswa?->user_id) {
                $this->notifikasi->kirim(
                    $kelasLabPeserta->mahasiswa->user_id,
                    'Pendaftaran Kelas Lab ditolak',
                    'Pendaftaran Anda pada sesi "'.$kelasLabPeserta->kelasLab->nama_sesi.'" ('.$kelasLabPeserta->kelasLab->mataKuliah?->nama_mk.') ditolak. Anda dapat mendaftar sesi lain.',
                    'status_pengajuan',
                    $kelasLabPeserta->kelas_lab_id,
                );
            }
        });

        return response()->json(['data' => $kelasLabPeserta, 'message' => 'Pendaftaran ditolak.']);
    }

    /**
     * Hapus peserta dari kelas — pemilik kelas (Dosen) atau Supervisor.
     * Dipakai untuk mengeluarkan mahasiswa yang salah mendaftar (termasuk yang sudah disetujui).
     */
    public function hapusPeserta(Request $request, KelasLabPeserta $kelasLabPeserta): JsonResponse
    {
        $this->authorizePendaftaran($request, $kelasLabPeserta);

        $kelasLabPeserta->delete();

        return response()->json(['message' => 'Peserta dikeluarkan dari kelas.']);
    }

    /**
     * Otorisasi kelola pendaftaran: pemilik kelas (dosen pengampu), Supervisor, atau Admin
     * (KelasLabPolicy::update).
     */
    private function authorizePendaftaran(Request $request, KelasLabPeserta $peserta): void
    {
        if (! $request->user()->can('update', $peserta->kelasLab)) {
            abort(403, 'Anda tidak memiliki akses untuk tindakan ini.');
        }
    }
}
