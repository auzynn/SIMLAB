<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\PeminjamanPerangkat;
use App\Models\PeminjamanRuangan;
use App\Models\Tugas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laporan/Report (SRS UC-06, 3_SDD.md 5.13). Rekap aktivitas lab dalam rentang tanggal.
 * Akses Admin/Supervisor (via ReportRequest → Gate view-report).
 */
class ReportController extends Controller
{
    /**
     * Rekap dalam bentuk JSON (default 30 hari terakhir bila from/to kosong).
     */
    public function index(ReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->rekap($request),
            'message' => 'Berhasil mengambil rekap laporan.',
        ]);
    }

    /**
     * Unduh rekap sebagai PDF (render Blade → dompdf).
     */
    public function pdf(ReportRequest $request): Response
    {
        $rekap = $this->rekap($request);

        return Pdf::loadView('reports.lab', ['rekap' => $rekap])
            ->download('laporan-lab-'.$rekap['periode']['dari'].'_'.$rekap['periode']['sampai'].'.pdf');
    }

    /**
     * Susun agregasi rekap (dipakai bersama oleh index & pdf).
     * Peminjaman & tugas dihitung berdasarkan tanggal pengajuan/kirim (created_at).
     */
    private function rekap(ReportRequest $request): array
    {
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : Carbon::today();
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : $to->copy()->subDays(30);
        [$dari, $sampai] = [$from->toDateString(), $to->toDateString()];

        $ruangan = PeminjamanRuangan::query()->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $perangkat = PeminjamanPerangkat::query()->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $tugas = Tugas::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        return [
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'peminjaman_ruangan' => [
                'total_pengajuan' => (clone $ruangan)->count(),
                'total_disetujui' => (clone $ruangan)->where('status', 'disetujui')->count(),
                'total_ditolak' => (clone $ruangan)->where('status', 'ditolak')->count(),
                'total_menunggu' => (clone $ruangan)->where('status', 'menunggu')->count(),
            ],
            'peminjaman_perangkat' => [
                'total_pengajuan' => (clone $perangkat)->count(),
                'total_disetujui' => (clone $perangkat)->where('status', 'disetujui')->count(),
                'total_ditolak' => (clone $perangkat)->where('status', 'ditolak')->count(),
                'total_dikembalikan' => (clone $perangkat)->where('status', 'dikembalikan')->count(),
            ],
            'tugas' => [
                'total_terkumpul' => $tugas->count(),
                'total_mahasiswa_unik' => $tugas->pluck('mahasiswa_id')->unique()->count(),
                'total_kelas' => $tugas->pluck('kelas_lab_id')->unique()->count(),
            ],
        ];
    }
}
