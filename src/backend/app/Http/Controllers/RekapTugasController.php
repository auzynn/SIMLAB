<?php

namespace App\Http\Controllers;

use App\Http\Requests\RekapTugasRequest;
use App\Services\RekapTugasExcelWriter;
use App\Services\RekapTugasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rekap Tugas Kelas Lab per pertemuan (2_SRS.md UC-06, 3_SDD.md 5.15).
 * Rekap ringkasan semua kelas + matriks detail per kelas, dalam JSON/PDF/Excel.
 * Akses Admin/Supervisor/Dosen (via RekapTugasRequest → Gate view-rekap-tugas);
 * Dosen otomatis di-scope ke kelas miliknya di RekapTugasService.
 */
class RekapTugasController extends Controller
{
    public function __construct(private RekapTugasService $rekap) {}

    /**
     * Rekap dalam bentuk JSON (ringkasan + detail matriks). Selalu data terkini.
     */
    public function index(RekapTugasRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->rekap->build($request->user()),
            'message' => 'Berhasil mengambil rekap tugas.',
        ]);
    }

    /**
     * Unduh rekap sebagai PDF (render Blade → dompdf), landscape agar matriks P1–P16 muat.
     */
    public function pdf(RekapTugasRequest $request): Response
    {
        $rekap = $this->rekap->build($request->user());

        return Pdf::loadView('reports.rekap-tugas', ['rekap' => $rekap])
            ->setPaper('a4', 'landscape')
            ->download('rekap-tugas-'.substr($rekap['generated_at'], 0, 10).'.pdf');
    }

    /**
     * Unduh rekap sebagai Excel .xlsx berformat (sheet Ringkasan + satu sheet per kelas).
     */
    public function excel(RekapTugasRequest $request, RekapTugasExcelWriter $writer): StreamedResponse
    {
        $rekap = $this->rekap->build($request->user());
        $namaFile = 'rekap-tugas-'.substr($rekap['generated_at'], 0, 10).'.xlsx';

        return response()->streamDownload(
            fn () => $writer->write($rekap),
            $namaFile,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );
    }
}
