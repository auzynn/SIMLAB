<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Services\PengingatDeadlineService;
use App\Services\PengingatPengembalianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi in-app (SRS UC-07, 3_SDD.md 5.14).
 * Semua aksi hanya menyentuh notifikasi milik user yang login.
 * Pembuatan notifikasi TIDAK lewat sini — dilakukan internal via NotifikasiService.
 */
class NotifikasiController extends Controller
{
    /**
     * List notifikasi milik sendiri (terbaru dulu) + jumlah belum dibaca (SDD 5.14).
     */
    public function index(Request $request, PengingatPengembalianService $pengingat, PengingatDeadlineService $pengingatDeadline): JsonResponse
    {
        $user = $request->user();

        // Jalur "kotak pos pintar": buat pengingat milik mahasiswa saat ia membuka lonceng,
        // sehingga muncul otomatis tanpa cron. Keduanya idempotent (tak spam).
        if ($user->role === 'mahasiswa') {
            $pengingat->generate($user->id);          // tenggat pengembalian perangkat
            $pengingatDeadline->generate($user->id);  // tenggat tugas terlewati
        }

        $notifikasi = $user->notifikasi()->latest()->get();

        return response()->json([
            'data' => $notifikasi,
            'unread_count' => $notifikasi->where('is_read', false)->count(),
            'message' => 'Berhasil mengambil notifikasi.',
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca (hanya milik sendiri → 403).
     */
    public function read(Request $request, Notifikasi $notifikasi): JsonResponse
    {
        if ($notifikasi->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Notifikasi ini bukan milik Anda.'], 403);
        }

        $notifikasi->update(['is_read' => true]);

        return response()->json([
            'data' => $notifikasi,
            'message' => 'Notifikasi ditandai sudah dibaca.',
        ]);
    }

    /**
     * Tandai semua notifikasi milik sendiri sebagai sudah dibaca.
     */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->notifikasi()->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }

    /**
     * Hapus satu notifikasi milik sendiri (hanya milik sendiri → 403).
     */
    public function destroy(Request $request, Notifikasi $notifikasi): JsonResponse
    {
        if ($notifikasi->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Notifikasi ini bukan milik Anda.'], 403);
        }

        $notifikasi->delete();

        return response()->json(['message' => 'Notifikasi dihapus.']);
    }
}
