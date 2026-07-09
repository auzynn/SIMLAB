<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeminjamanRuanganRequest;
use App\Models\KelasLab;
use App\Models\PeminjamanRuangan;
use App\Services\JadwalRuanganService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Pengajuan peminjaman ruangan (SRS UC-02, 3_SDD.md 3.5, 5.5).
 * - Ajukan: Mahasiswa saja (Dosen tidak meminjam ruangan). Approve/reject: Admin/Supervisor (Gate approve-peminjaman-ruangan).
 * - Validasi bentrok dipusatkan di JadwalRuanganService (dipakai saat ajukan & saat approve ulang).
 */
class PeminjamanRuanganController extends Controller
{
    public function __construct(
        private JadwalRuanganService $jadwal,
        private NotifikasiService $notifikasi,
    ) {}

    /**
     * List pengajuan: Admin/Supervisor lihat semua, role lain hanya miliknya (3_SDD.md 5.5).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $peminjaman = PeminjamanRuangan::query()
            ->with(['ruangan', 'user.mahasiswa', 'penyetuju'])
            ->when(
                ! in_array($user->role, ['admin', 'supervisor'], true),
                fn ($q) => $q->where('user_id', $user->id),
            )
            ->latest()
            ->get();

        return response()->json([
            'data' => $peminjaman,
            'message' => 'Berhasil mengambil data peminjaman ruangan.',
        ]);
    }

    /**
     * Data kalender ketersediaan: peminjaman disetujui + jadwal kelas_lab aktif (T3.8, 3_SDD.md 5.5).
     * Keduanya dibedakan agar frontend menampilkan dengan warna berbeda.
     */
    public function kalender(): JsonResponse
    {
        // Peminjaman disetujui yang masih relevan: dari awal minggu berjalan (Senin) ke depan.
        // Patokan awal minggu membuat daftar otomatis "ter-refresh" tiap pergantian minggu
        // (lewat Minggu 23.59) — peminjaman minggu lalu rontok — tanpa perlu cron/state.
        // Peminjaman mendatang (mis. minggu depan) tetap tampil agar slot yang sudah di-acc terlihat.
        $awalMinggu = Carbon::now()->startOfWeek()->toDateString();

        $peminjaman = PeminjamanRuangan::query()
            ->with(['ruangan', 'user.mahasiswa'])
            ->where('status', 'disetujui')
            ->where('tanggal', '>=', $awalMinggu)
            ->orderBy('tanggal')
            ->get();

        $kelasLab = KelasLab::query()
            ->with(['ruangan', 'mataKuliah', 'dosen.user'])
            ->orderBy('hari')
            ->get();

        return response()->json([
            'data' => [
                'peminjaman' => $peminjaman,
                'kelas_lab' => $kelasLab,
            ],
            'message' => 'Berhasil mengambil data ketersediaan ruangan.',
        ]);
    }

    /**
     * Ajukan peminjaman (Mahasiswa/Dosen). Validasi bentrok & status ruangan di Form Request.
     */
    public function store(StorePeminjamanRuanganRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'menunggu';
        $data['jam_mulai'] = $this->jadwal->jam($data['jam_mulai']);
        $data['jam_selesai'] = $this->jadwal->jam($data['jam_selesai']);

        // Notifikasi ke approver dilakukan dalam transaksi yang sama (SRS UC-07).
        $peminjaman = DB::transaction(function () use ($data, $request) {
            $peminjaman = PeminjamanRuangan::create($data);

            $this->notifikasi->kirimKeApprover(
                'Pengajuan peminjaman ruangan baru',
                $request->user()->name.' mengajukan peminjaman ruangan pada '.$peminjaman->tanggal->format('d-m-Y').'.',
                'pengajuan_masuk',
                $peminjaman->id,
            );

            return $peminjaman;
        });

        return response()->json([
            'data' => $peminjaman->load(['ruangan', 'user']),
            'message' => 'Pengajuan peminjaman ruangan berhasil dikirim, menunggu persetujuan.',
        ], 201);
    }

    /**
     * Setujui pengajuan (Admin/Supervisor). Wajib validasi ulang bentrok + status ruangan,
     * karena kondisi bisa berubah antara saat submit dan saat approve (T3.10, UC-02).
     */
    public function approve(PeminjamanRuangan $peminjamanRuangan): JsonResponse
    {
        Gate::authorize('approve-peminjaman-ruangan');

        if ($peminjamanRuangan->status === 'disetujui') {
            return response()->json(['message' => 'Pengajuan sudah disetujui sebelumnya.'], 422);
        }

        $ruangan = $peminjamanRuangan->ruangan;
        if (! $ruangan || $ruangan->status !== 'tersedia') {
            return response()->json([
                'message' => 'Ruangan tidak tersedia, pengajuan tidak dapat disetujui.',
            ], 422);
        }

        $bentrok = $this->jadwal->peminjamanBentrok(
            $peminjamanRuangan->ruangan_id,
            $peminjamanRuangan->tanggal,
            $peminjamanRuangan->jam_mulai,
            $peminjamanRuangan->jam_selesai,
            $peminjamanRuangan->id,
        );

        if ($bentrok) {
            return response()->json([
                'message' => 'Jadwal kini bentrok dengan peminjaman/Kelas Lab lain, tidak dapat disetujui.',
            ], 422);
        }

        DB::transaction(function () use ($peminjamanRuangan) {
            $peminjamanRuangan->update([
                'status' => 'disetujui',
                'disetujui_oleh' => request()->user()->id,
            ]);

            // Notifikasi ke pengaju dalam transaksi yang sama (SRS UC-07).
            $this->notifikasi->kirim(
                $peminjamanRuangan->user_id,
                'Peminjaman ruangan disetujui',
                'Pengajuan peminjaman ruangan Anda pada '.$peminjamanRuangan->tanggal->format('d-m-Y').' telah disetujui.',
                'status_pengajuan',
                $peminjamanRuangan->id,
            );
        });

        return response()->json([
            'data' => $peminjamanRuangan->load(['ruangan', 'user', 'penyetuju']),
            'message' => 'Pengajuan peminjaman disetujui.',
        ]);
    }

    /**
     * Tolak pengajuan (Admin/Supervisor).
     */
    public function reject(PeminjamanRuangan $peminjamanRuangan): JsonResponse
    {
        Gate::authorize('approve-peminjaman-ruangan');

        DB::transaction(function () use ($peminjamanRuangan) {
            $peminjamanRuangan->update([
                'status' => 'ditolak',
                'disetujui_oleh' => request()->user()->id,
            ]);

            $this->notifikasi->kirim(
                $peminjamanRuangan->user_id,
                'Peminjaman ruangan ditolak',
                'Pengajuan peminjaman ruangan Anda pada '.$peminjamanRuangan->tanggal->format('d-m-Y').' ditolak.',
                'status_pengajuan',
                $peminjamanRuangan->id,
            );
        });

        return response()->json([
            'data' => $peminjamanRuangan->load(['ruangan', 'user', 'penyetuju']),
            'message' => 'Pengajuan peminjaman ditolak.',
        ]);
    }

    /**
     * Hapus/batalkan pengajuan peminjaman.
     * - Pemilik boleh membatalkan miliknya selama masih 'menunggu' (SRS UC-02).
     * - Admin/Supervisor boleh menghapus kapan saja (mis. membersihkan riwayat).
     */
    public function destroy(PeminjamanRuangan $peminjamanRuangan): JsonResponse
    {
        $user = request()->user();
        $pemilikMenunggu = $peminjamanRuangan->user_id === $user->id
            && $peminjamanRuangan->status === 'menunggu';

        if (! $pemilikMenunggu && ! Gate::allows('approve-peminjaman-ruangan')) {
            return response()->json([
                'message' => 'Anda tidak berhak membatalkan pengajuan ini.',
            ], 403);
        }

        $peminjamanRuangan->delete();

        return response()->json(['message' => 'Pengajuan peminjaman dibatalkan.']);
    }
}
