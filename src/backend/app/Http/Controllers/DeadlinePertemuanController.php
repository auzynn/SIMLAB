<?php

namespace App\Http\Controllers;

use App\Models\DeadlinePertemuan;
use App\Models\KelasLab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Deadline pengumpulan tugas per pertemuan (1–16) sebuah Kelas Lab.
 * - Lihat: staf kelas + mahasiswa peserta DISETUJUI (KelasLabPolicy::view) — materi/deadline
 *   tidak boleh bocor ke mahasiswa yang belum disetujui.
 * - Atur/hapus: Dosen pengampu / Supervisor / Admin (KelasLabPolicy::update + Admin).
 */
class DeadlinePertemuanController extends Controller
{
    /**
     * Daftar deadline yang sudah ditetapkan untuk sebuah kelas.
     */
    public function index(KelasLab $kelasLab): JsonResponse
    {
        Gate::authorize('view', $kelasLab);

        $deadline = DeadlinePertemuan::where('kelas_lab_id', $kelasLab->id)
            ->orderBy('pertemuan')
            ->get(['pertemuan', 'materi', 'deadline']);

        return response()->json([
            'data' => $deadline,
            'message' => 'Berhasil mengambil daftar deadline.',
        ]);
    }

    /**
     * Tetapkan/ubah materi &/atau deadline sebuah pertemuan (upsert). Hanya peninjau kelas.
     * Materi bisa berdiri sendiri (silabus) tanpa deadline; minimal salah satu terisi.
     */
    public function upsert(Request $request, KelasLab $kelasLab, int $pertemuan): JsonResponse
    {
        $this->pastikanBolehKelola($request, $kelasLab);

        if ($pertemuan < 1 || $pertemuan > 16) {
            return response()->json(['message' => 'Pertemuan harus antara 1 sampai 16.'], 422);
        }

        $data = $request->validate([
            'materi' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
        ]);

        $materi = $data['materi'] ?? null;
        $deadlineInput = $data['deadline'] ?? null;

        // Minimal salah satu terisi; jika keduanya kosong, hapus record (kembali "kosong").
        if ($materi === null && $deadlineInput === null) {
            DeadlinePertemuan::where('kelas_lab_id', $kelasLab->id)
                ->where('pertemuan', $pertemuan)
                ->delete();

            return response()->json(['data' => null, 'message' => 'Materi & deadline pertemuan dikosongkan.']);
        }

        $deadline = DeadlinePertemuan::updateOrCreate(
            ['kelas_lab_id' => $kelasLab->id, 'pertemuan' => $pertemuan],
            ['materi' => $materi, 'deadline' => $deadlineInput],
        );

        return response()->json([
            'data' => $deadline->only(['pertemuan', 'materi', 'deadline']),
            'message' => 'Materi/deadline pertemuan disimpan.',
        ]);
    }

    /**
     * Hapus materi & deadline sebuah pertemuan (kembali ke "kosong").
     */
    public function destroy(Request $request, KelasLab $kelasLab, int $pertemuan): JsonResponse
    {
        $this->pastikanBolehKelola($request, $kelasLab);

        DeadlinePertemuan::where('kelas_lab_id', $kelasLab->id)
            ->where('pertemuan', $pertemuan)
            ->delete();

        return response()->json(['message' => 'Deadline pertemuan dihapus.']);
    }

    /**
     * Otorisasi kelola: Dosen pengampu / Supervisor / Admin (403 bila bukan).
     */
    private function pastikanBolehKelola(Request $request, KelasLab $kelasLab): void
    {
        $user = $request->user();
        $boleh = in_array($user->role, ['admin', 'supervisor'], true)
            || ($user->role === 'dosen' && $user->dosen?->id === $kelasLab->dosen_id);

        abort_unless($boleh, 403, 'Anda tidak berhak mengatur deadline kelas ini.');
    }
}
