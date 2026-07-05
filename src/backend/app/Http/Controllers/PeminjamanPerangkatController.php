<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeminjamanPerangkatRequest;
use App\Models\PeminjamanPerangkat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Pengajuan peminjaman perangkat (SRS UC-03, 3_SDD.md 3.10, 5.9).
 * - Ajukan: Mahasiswa saja (StorePeminjamanPerangkatRequest).
 * - Approve/reject/kembalikan & perpanjangan: Admin/Supervisor (Gate approve-peminjaman-perangkat).
 * - Status perangkat disinkronkan otomatis: approve → dipinjam, reject/kembalikan → tersedia.
 */
class PeminjamanPerangkatController extends Controller
{
    /**
     * List pengajuan: Admin/Supervisor lihat semua, role lain hanya miliknya (3_SDD.md 5.9).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $peminjaman = PeminjamanPerangkat::query()
            ->with(['perangkat', 'user.mahasiswa', 'penyetuju', 'perpanjangan.penyetuju'])
            ->when(
                ! in_array($user->role, ['admin', 'supervisor'], true),
                fn ($q) => $q->where('user_id', $user->id),
            )
            ->latest()
            ->get();

        return response()->json([
            'data' => $peminjaman,
            'message' => 'Berhasil mengambil data peminjaman perangkat.',
        ]);
    }

    /**
     * Ajukan peminjaman (Mahasiswa). Validasi ketersediaan perangkat di Form Request.
     */
    public function store(StorePeminjamanPerangkatRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'menunggu';

        $peminjaman = PeminjamanPerangkat::create($data);

        return response()->json([
            'data' => $peminjaman->load(['perangkat', 'user']),
            'message' => 'Pengajuan peminjaman perangkat berhasil dikirim, menunggu persetujuan.',
        ], 201);
    }

    /**
     * Setujui pengajuan (Admin/Supervisor). Re-validasi perangkat masih tersedia karena
     * kondisi bisa berubah antara submit dan approve; perangkat ditandai dipinjam.
     */
    public function approve(PeminjamanPerangkat $peminjamanPerangkat): JsonResponse
    {
        Gate::authorize('approve-peminjaman-perangkat');

        if ($peminjamanPerangkat->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses sebelumnya.'], 422);
        }

        $perangkat = $peminjamanPerangkat->perangkat;
        if (! $perangkat || $perangkat->status !== 'tersedia') {
            return response()->json([
                'message' => 'Perangkat tidak tersedia, pengajuan tidak dapat disetujui.',
            ], 422);
        }

        DB::transaction(function () use ($peminjamanPerangkat, $perangkat) {
            $peminjamanPerangkat->update([
                'status' => 'disetujui',
                'disetujui_oleh' => request()->user()->id,
            ]);
            $perangkat->update(['status' => 'dipinjam']);
        });

        return response()->json([
            'data' => $peminjamanPerangkat->load(['perangkat', 'user', 'penyetuju']),
            'message' => 'Pengajuan peminjaman disetujui.',
        ]);
    }

    /**
     * Tolak pengajuan (Admin/Supervisor).
     */
    public function reject(PeminjamanPerangkat $peminjamanPerangkat): JsonResponse
    {
        Gate::authorize('approve-peminjaman-perangkat');

        if ($peminjamanPerangkat->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses sebelumnya.'], 422);
        }

        $peminjamanPerangkat->update([
            'status' => 'ditolak',
            'disetujui_oleh' => request()->user()->id,
        ]);

        return response()->json([
            'data' => $peminjamanPerangkat->load(['perangkat', 'user', 'penyetuju']),
            'message' => 'Pengajuan peminjaman ditolak.',
        ]);
    }

    /**
     * Konfirmasi pengembalian perangkat (Admin/Supervisor). Perangkat kembali tersedia,
     * tanggal_kembali_aktual dicatat (3_SDD.md 3.10).
     */
    public function kembalikan(PeminjamanPerangkat $peminjamanPerangkat): JsonResponse
    {
        Gate::authorize('approve-peminjaman-perangkat');

        if ($peminjamanPerangkat->status !== 'disetujui') {
            return response()->json([
                'message' => 'Hanya peminjaman yang sedang berjalan (disetujui) yang dapat dikembalikan.',
            ], 422);
        }

        DB::transaction(function () use ($peminjamanPerangkat) {
            $peminjamanPerangkat->update([
                'status' => 'dikembalikan',
                'tanggal_kembali_aktual' => Carbon::today()->toDateString(),
            ]);
            $peminjamanPerangkat->perangkat?->update(['status' => 'tersedia']);
        });

        return response()->json([
            'data' => $peminjamanPerangkat->load(['perangkat', 'user', 'penyetuju']),
            'message' => 'Pengembalian perangkat dikonfirmasi.',
        ]);
    }

    /**
     * Batalkan/hapus pengajuan peminjaman perangkat.
     * - Pemilik (Mahasiswa) boleh membatalkan miliknya selama masih 'menunggu' (SRS UC-03).
     * - Admin/Supervisor boleh menghapus kapan saja (mis. membersihkan riwayat).
     */
    public function destroy(PeminjamanPerangkat $peminjamanPerangkat): JsonResponse
    {
        $user = request()->user();
        $pemilikMenunggu = $peminjamanPerangkat->user_id === $user->id
            && $peminjamanPerangkat->status === 'menunggu';

        if (! $pemilikMenunggu && ! Gate::allows('approve-peminjaman-perangkat')) {
            return response()->json([
                'message' => 'Anda tidak berhak membatalkan pengajuan ini.',
            ], 403);
        }

        $peminjamanPerangkat->delete();

        return response()->json(['message' => 'Pengajuan peminjaman perangkat dibatalkan.']);
    }

    /**
     * Ajukan perpanjangan waktu pinjam (Mahasiswa pemilik). Aturan kunci UC-03:
     * ditolak bila tanggal_kembali_rencana sudah lewat dari hari ini (T4.8).
     */
    public function ajukanPerpanjangan(Request $request, PeminjamanPerangkat $peminjamanPerangkat): JsonResponse
    {
        $user = $request->user();

        // Hanya pemilik peminjaman (Mahasiswa) yang boleh mengajukan perpanjangan.
        if ($user->role !== 'mahasiswa' || $peminjamanPerangkat->user_id !== $user->id) {
            return response()->json(['message' => 'Anda tidak berhak mengajukan perpanjangan ini.'], 403);
        }

        if ($peminjamanPerangkat->status !== 'disetujui') {
            return response()->json([
                'message' => 'Perpanjangan hanya untuk peminjaman yang sedang berjalan (disetujui).',
            ], 422);
        }

        // Aturan kunci UC-03: tolak bila rencana kembali sudah lewat hari ini.
        if ($peminjamanPerangkat->tanggal_kembali_rencana->isBefore(Carbon::today())) {
            return response()->json([
                'message' => 'Tidak dapat mengajukan perpanjangan karena tanggal kembali rencana sudah lewat.',
            ], 422);
        }

        $data = $request->validate([
            'tanggal_kembali_baru' => [
                'required',
                'date',
                'after:'.$peminjamanPerangkat->tanggal_kembali_rencana->toDateString(),
            ],
        ]);

        $perpanjangan = $peminjamanPerangkat->perpanjangan()->create([
            'tanggal_kembali_baru' => $data['tanggal_kembali_baru'],
            'status' => 'menunggu',
        ]);

        return response()->json([
            'data' => $perpanjangan,
            'message' => 'Pengajuan perpanjangan berhasil dikirim, menunggu persetujuan.',
        ], 201);
    }
}
