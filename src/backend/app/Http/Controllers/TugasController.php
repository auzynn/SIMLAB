<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTugasRequest;
use App\Models\KelasLab;
use App\Models\Tugas;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengumpulan Tugas (menggantikan modul Presensi). Mahasiswa mengirim tautan tugas
 * untuk Kelas Lab yang diikutinya; Dosen pengampu (+ Supervisor/Admin) melihat & membuka.
 */
class TugasController extends Controller
{
    public function __construct(private NotifikasiService $notifikasi) {}

    /**
     * List tugas dengan cakupan sesuai role:
     * Mahasiswa → miliknya; Dosen → kelas yang ia ampu; Admin/Supervisor → semua.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $tugas = Tugas::query()
            ->with(['kelasLab.mataKuliah', 'mahasiswa.user'])
            ->when(
                $user->role === 'mahasiswa',
                fn ($q) => $q->where('mahasiswa_id', $user->mahasiswa?->id),
            )
            ->when(
                $user->role === 'dosen',
                fn ($q) => $q->whereIn('kelas_lab_id', KelasLab::where('dosen_id', $user->dosen?->id)->pluck('id')),
            )
            ->latest()
            ->get();

        return response()->json([
            'data' => $tugas,
            'message' => 'Berhasil mengambil data tugas.',
        ]);
    }

    /**
     * Kirim tugas (Mahasiswa). Kelayakan kelas divalidasi di StoreTugasRequest.
     * Memberi notifikasi ke dosen pengampu kelas bahwa ada tugas baru masuk (SRS UC-07).
     */
    public function store(StoreTugasRequest $request): JsonResponse
    {
        $user = $request->user();
        $kelas = KelasLab::with(['mataKuliah', 'dosen'])->findOrFail($request->validated('kelas_lab_id'));

        $tugas = DB::transaction(function () use ($request, $user, $kelas) {
            $tugas = Tugas::create([
                'mahasiswa_id' => $user->mahasiswa->id,
                'kelas_lab_id' => $kelas->id,
                'pertemuan' => $request->validated('pertemuan'),
                'judul' => $request->validated('judul'),
                'tautan' => $request->validated('tautan'),
            ]);

            // Notifikasi "tugas baru masuk" ke peninjau (transaksi sama, SRS UC-07):
            // dosen pengampu kelas + seluruh Supervisor. Null-safe untuk kelas tanpa dosen.
            $judul = 'Tugas baru masuk';
            $pesan = $user->name.' mengirim tugas "'.$tugas->judul.'" pada kelas '.$kelas->mataKuliah?->nama_mk.' — '.$kelas->nama_sesi.'.';

            if ($dosenUserId = $kelas->dosen?->user_id) {
                $this->notifikasi->kirim($dosenUserId, $judul, $pesan, 'pengajuan_masuk', $tugas->id);
            }
            $this->notifikasi->kirimKeRole(['supervisor'], $judul, $pesan, 'pengajuan_masuk', $tugas->id);

            return $tugas;
        });

        return response()->json([
            'data' => $tugas->load(['kelasLab.mataKuliah', 'mahasiswa.user']),
            'message' => 'Tugas berhasil dikirim.',
        ], 201);
    }

    /**
     * Hapus tugas: pemilik (mahasiswa) atau Admin/Supervisor.
     */
    public function destroy(Tugas $tugas): JsonResponse
    {
        $user = request()->user();
        $pemilik = $user->role === 'mahasiswa' && $tugas->mahasiswa_id === $user->mahasiswa?->id;

        if (! $pemilik && ! in_array($user->role, ['admin', 'supervisor'], true)) {
            return response()->json(['message' => 'Anda tidak berhak menghapus tugas ini.'], 403);
        }

        $tugas->delete();

        return response()->json(['message' => 'Tugas dihapus.']);
    }
}
