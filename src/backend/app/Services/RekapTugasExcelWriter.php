<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Menulis struktur Rekap Tugas (dari RekapTugasService::build) menjadi workbook .xlsx
 * ber-branding SIM Lab. Riset:
 *  - Sheet "Indeks"    : KPI global + daftar kelas ber-hyperlink ke sheet-nya.
 *  - Sheet "Ringkasan" : header band + KPI + tabel ringkasan (status pill, zebra, AutoFilter).
 *  - Satu sheet/kelas  : matriks peserta × pertemuan (titik berwarna tepat/telat/belum).
 *
 * Ditulis langsung ke output stream (dipanggil dari streamDownload).
 */
class RekapTugasExcelWriter
{
    // Palet brand aplikasi (src/assets/style/style.css).
    private const NAVY = '183861';
    private const YELLOW = 'FFB057';
    private const INK = '141E27';
    private const WHITE = 'FFFFFF';
    private const ZEBRA = 'FAFAFA';
    private const BORDER = 'E4E7EB';
    private const MUTED = '6B7280';

    // Warna status kepatuhan: [teks, fill lembut, titik].
    private const STATUS = [
        'perhatian' => ['teks' => 'C0392B', 'fill' => 'FDEDEC', 'dot' => 'E74C3C'],
        'berjalan' => ['teks' => '9A6A00', 'fill' => 'FEF9E7', 'dot' => 'F39C12'],
        'beres' => ['teks' => '1E8449', 'fill' => 'EAF7F0', 'dot' => '27AE60'],
    ];

    private const DOT_BELUM = 'B0B7BE';

    /**
     * @param  array{generated_at:string, ringkasan:array, detail:array}  $rekap
     */
    public function write(array $rekap): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $spreadsheet->getDefaultStyle()->getFont()->getColor()->setARGB('FF'.self::INK);

        // Judul sheet tiap kelas dihitung di awal agar hyperlink Indeks bisa menunjuk tepat.
        $dipakai = ['Indeks' => true, 'Ringkasan' => true];
        $judulMap = [];
        foreach ($rekap['detail'] as $kelas) {
            $judulMap[$kelas['kelas_lab_id']] = $this->judulSheet($kelas, $dipakai);
        }
        $statusKelas = [];
        foreach ($rekap['ringkasan'] as $r) {
            $statusKelas[$r['kelas_lab_id']] = $r['status'];
        }

        $this->sheetIndeks($spreadsheet->getActiveSheet(), $rekap, $judulMap, $statusKelas);
        $this->sheetRingkasan($spreadsheet->createSheet(), $rekap);
        foreach ($rekap['detail'] as $kelas) {
            $this->sheetKelas(
                $spreadsheet->createSheet(),
                $kelas,
                $judulMap[$kelas['kelas_lab_id']],
                $statusKelas[$kelas['kelas_lab_id']] ?? 'beres',
            );
        }

        $spreadsheet->setActiveSheetIndex(0);
        (new Xlsx($spreadsheet))->save('php://output');
        $spreadsheet->disconnectWorksheets();
    }

    // ===================== Sheet: Indeks =====================

    private function sheetIndeks(Worksheet $sheet, array $rekap, array $judulMap, array $statusKelas): void
    {
        $sheet->setTitle('Indeks');
        $this->band($sheet, 'J', 'Rekap Tugas Kelas Lab', 'SIM Lab. Riset KK JKF · Dibuat '.$rekap['generated_at'].' WIB', true);
        $this->stripKpi($sheet, $rekap['ringkasan']);

        // Daftar kelas ber-hyperlink.
        $sheet->setCellValue('A8', 'Daftar Kelas');
        $sheet->getStyle('A8')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF'.self::NAVY);

        $head = 9;
        $this->headerTabel($sheet, ['No', 'Mata Kuliah', 'Sesi', 'Dosen', 'Status', 'Buka'], 'A', 'F', $head);

        $baris = $head + 1;
        foreach ($rekap['ringkasan'] as $i => $r) {
            $id = $r['kelas_lab_id'];
            $sheet->setCellValue('A'.$baris, $i + 1);
            $sheet->setCellValue('B'.$baris, $r['mata_kuliah']);
            $sheet->setCellValue('C'.$baris, $r['nama_sesi']);
            $sheet->setCellValue('D'.$baris, $r['dosen'] ?? '-');
            $this->selStatus($sheet, 'E'.$baris, $r['status']);

            // Sel "Buka" = hyperlink internal ke sheet kelas.
            if (isset($judulMap[$id])) {
                $sheet->setCellValue('F'.$baris, 'Buka ▸');
                $sheet->getCell('F'.$baris)->getHyperlink()->setUrl("sheet://'".$judulMap[$id]."'!A1");
                $sheet->getStyle('F'.$baris)->getFont()->setBold(true)->setUnderline(true)->getColor()->setARGB('FF'.self::NAVY);
            }
            $this->zebra($sheet, 'A'.$baris.':F'.$baris, $i);
            $baris++;
        }

        $this->border($sheet, 'A'.$head.':F'.($baris - 1));
        $sheet->getStyle('A'.($head + 1).':A'.($baris - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->lebar($sheet, ['A' => 5, 'B' => 34, 'C' => 14, 'D' => 30, 'E' => 18, 'F' => 10]);
        $sheet->freezePane('A'.($head + 1));
        $this->rapikan($sheet, 'F');
    }

    // ===================== Sheet: Ringkasan =====================

    private function sheetRingkasan(Worksheet $sheet, array $rekap): void
    {
        $sheet->setTitle('Ringkasan');
        $this->band($sheet, 'K', 'Ringkasan Tugas Kelas Lab', 'Kepatuhan pengumpulan tugas per kelas · Dibuat '.$rekap['generated_at'].' WIB', true);
        $this->stripKpi($sheet, $rekap['ringkasan']);

        $kolom = ['Mata Kuliah', 'Sesi', 'Dosen', 'Hari', 'Jam', 'Peserta', 'Bertugas', 'Pertemuan', 'Tunggakan', 'Status', 'Deadline Terdekat'];
        $head = 8;
        $this->headerTabel($sheet, $kolom, 'A', 'K', $head);

        $baris = $head + 1;
        foreach ($rekap['ringkasan'] as $i => $r) {
            $sheet->setCellValue('A'.$baris, $r['mata_kuliah']);
            $sheet->setCellValue('B'.$baris, $r['nama_sesi']);
            $sheet->setCellValue('C'.$baris, $r['dosen'] ?? '-');
            $sheet->setCellValue('D'.$baris, ucfirst((string) $r['hari']));
            $sheet->setCellValue('E'.$baris, $r['jam']);
            $sheet->setCellValue('F'.$baris, $r['peserta_disetujui']);
            $sheet->setCellValue('G'.$baris, $r['pertemuan_bertugas'].'/16');
            $sheet->setCellValue('H'.$baris, $r['pertemuan_berjalan'].'/16');
            $sheet->setCellValue('I'.$baris, $r['tunggakan']);
            $this->selStatus($sheet, 'J'.$baris, $r['status']);
            $sheet->setCellValue('K'.$baris, $r['deadline_terdekat'] ?? '-');

            $this->zebra($sheet, 'A'.$baris.':K'.$baris, $i);
            // Status sudah diberi fill sendiri di selStatus → timpa ulang setelah zebra.
            $this->selStatus($sheet, 'J'.$baris, $r['status']);
            $baris++;
        }
        if (empty($rekap['ringkasan'])) {
            $sheet->setCellValue('A'.$baris, 'Belum ada kelas untuk direkap.');
            $baris++;
        }

        $this->border($sheet, 'A'.$head.':K'.($baris - 1));
        foreach (['F', 'G', 'H', 'I'] as $c) {
            $sheet->getStyle($c.($head + 1).':'.$c.($baris - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $this->lebar($sheet, ['A' => 30, 'B' => 12, 'C' => 28, 'D' => 10, 'E' => 12, 'F' => 9, 'G' => 10, 'H' => 11, 'I' => 11, 'J' => 16, 'K' => 20]);
        $sheet->setAutoFilter('A'.$head.':K'.max($head, $baris - 1));
        $sheet->freezePane('A'.($head + 1));
        $this->rapikan($sheet, 'K');
    }

    // ===================== Sheet: per kelas =====================

    private function sheetKelas(Worksheet $sheet, array $kelas, string $judul, string $status): void
    {
        $sheet->setTitle($judul);
        $pertemuan = $kelas['pertemuan'];
        $nCol = 2 + count($pertemuan) + 2; // NPM, Nama, P.., Total, Telat
        $kolomAkhir = $this->kolomExcel(max($nCol, 4));

        $this->band(
            $sheet,
            $kolomAkhir,
            $kelas['mata_kuliah'].' — '.$kelas['nama_sesi'],
            ($kelas['dosen'] ?? '-').' · '.ucfirst((string) $kelas['hari']).' '.$kelas['jam'],
            false,
        );

        if (empty($pertemuan)) {
            $sheet->setCellValue('A5', 'Belum ada pertemuan yang diberi tugas/deadline.');
            $sheet->getStyle('A5')->getFont()->setItalic(true)->getColor()->setARGB('FF'.self::MUTED);
            $sheet->getTabColor()->setARGB('FF'.(self::STATUS[$status]['dot'] ?? self::DOT_BELUM));
            $this->lebar($sheet, ['A' => 14, 'B' => 30]);
            $this->rapikan($sheet, $kolomAkhir);

            return;
        }

        // Header matriks.
        $head = 5;
        $judulKolom = ['NPM', 'Nama'];
        foreach ($pertemuan as $p) {
            $judulKolom[] = 'P'.$p['pertemuan'];
        }
        $judulKolom[] = 'Total';
        $judulKolom[] = 'Telat';
        $this->headerTabel($sheet, $judulKolom, 'A', $kolomAkhir, $head);

        $baris = $head + 1;
        foreach ($kelas['peserta'] as $i => $ps) {
            $sheet->setCellValueExplicit('A'.$baris, (string) $ps['npm'], DataType::TYPE_STRING);
            $sheet->setCellValue('B'.$baris, $ps['nama']);

            $idx = 3;
            foreach ($pertemuan as $p) {
                $sel = $ps['sel'][$p['pertemuan']] ?? ['status' => 'belum'];
                $col = $this->kolomExcel($idx);
                $this->titik($sheet, $col.$baris, $sel['status']);
                $idx++;
            }
            $sheet->setCellValue($this->kolomExcel($idx).$baris, $ps['total_kumpul']);
            $sheet->setCellValue($this->kolomExcel($idx + 1).$baris, $ps['telat']);
            $this->zebra($sheet, 'A'.$baris.':'.$kolomAkhir.$baris, $i);
            $baris++;
        }
        if (empty($kelas['peserta'])) {
            $sheet->setCellValue('A'.$baris, 'Belum ada peserta disetujui.');
            $baris++;
        }

        $this->border($sheet, 'A'.$head.':'.$kolomAkhir.($baris - 1));
        // Kolom P.. + Total/Telat rata tengah.
        $sheet->getStyle($this->kolomExcel(3).($head + 1).':'.$kolomAkhir.($baris - 1))
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Lebar: NPM & Nama tetap, kolom pertemuan seragam sempit.
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(28);
        for ($c = 3; $c <= $nCol; $c++) {
            $sheet->getColumnDimension($this->kolomExcel($c))->setWidth(6);
        }
        $sheet->freezePane('C'.($head + 1));

        // Legenda + tabel materi/deadline.
        $this->legenda($sheet, $pertemuan, $baris + 1);

        $sheet->getTabColor()->setARGB('FF'.(self::STATUS[$status]['dot'] ?? self::DOT_BELUM));
        $this->rapikan($sheet, $kolomAkhir);
    }

    // ===================== Komponen reusable =====================

    /** Band judul navy + subjudul + logo opsional (baris 1–3). */
    private function band(Worksheet $sheet, string $kolomAkhir, string $judul, string $subjudul, bool $logo): void
    {
        $sheet->mergeCells('A1:'.$kolomAkhir.'2');
        $sheet->mergeCells('A3:'.$kolomAkhir.'3');
        $this->fill($sheet, 'A1:'.$kolomAkhir.'3', self::NAVY);

        $sheet->setCellValue('A1', $judul);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB('FF'.self::WHITE);
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent($logo ? 7 : 1);

        $sheet->setCellValue('A3', $subjudul);
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setARGB('FF'.self::YELLOW);
        $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent($logo ? 7 : 1);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(14);
        $sheet->getRowDimension(3)->setRowHeight(16);

        $path = resource_path('reports/logo-unsil.png');
        if ($logo && file_exists($path)) {
            $draw = new Drawing;
            $draw->setPath($path);
            $draw->setHeight(44);
            $draw->setOffsetX(10);
            $draw->setOffsetY(6);
            $draw->setCoordinates('A1');
            $draw->setWorksheet($sheet);
        }
    }

    /** Strip 5 kartu KPI global (baris 5–6) dari data ringkasan. */
    private function stripKpi(Worksheet $sheet, array $ringkasan): void
    {
        $total = count($ringkasan);
        $perhatian = $berjalan = $beres = $tunggakan = 0;
        foreach ($ringkasan as $r) {
            $tunggakan += (int) $r['tunggakan'];
            $perhatian += $r['status'] === 'perhatian' ? 1 : 0;
            $berjalan += $r['status'] === 'berjalan' ? 1 : 0;
            $beres += $r['status'] === 'beres' ? 1 : 0;
        }

        $kartu = [
            ['Total Kelas', $total, self::NAVY],
            ['Perlu Perhatian', $perhatian, self::STATUS['perhatian']['dot']],
            ['Berjalan', $berjalan, self::STATUS['berjalan']['dot']],
            ['Beres', $beres, self::STATUS['beres']['dot']],
            ['Total Tunggakan', $tunggakan, self::NAVY],
        ];
        $col = 1;
        foreach ($kartu as [$label, $angka, $warna]) {
            $this->kartu($sheet, $col, $label, (string) $angka, $warna);
            $col += 2;
        }
        $sheet->getRowDimension(5)->setRowHeight(30);
        $sheet->getRowDimension(6)->setRowHeight(16);
    }

    /** Satu kartu KPI (2 kolom × 2 baris) mulai kolom index $colStart. */
    private function kartu(Worksheet $sheet, int $colStart, string $label, string $angka, string $warna): void
    {
        $c1 = $this->kolomExcel($colStart);
        $c2 = $this->kolomExcel($colStart + 1);
        $sheet->mergeCells("{$c1}5:{$c2}5");
        $sheet->mergeCells("{$c1}6:{$c2}6");
        $this->fill($sheet, "{$c1}5:{$c2}6", self::ZEBRA);
        $this->border($sheet, "{$c1}5:{$c2}6");

        $sheet->setCellValue($c1.'5', $angka);
        $sheet->getStyle($c1.'5')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB('FF'.$warna);
        $sheet->getStyle($c1.'5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue($c1.'6', strtoupper($label));
        $sheet->getStyle($c1.'6')->getFont()->setSize(8)->getColor()->setARGB('FF'.self::MUTED);
        $sheet->getStyle($c1.'6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /** Header tabel: fill navy, teks putih tebal, tengah, border. */
    private function headerTabel(Worksheet $sheet, array $kolom, string $colA, string $colZ, int $baris): void
    {
        $sheet->fromArray($kolom, null, $colA.$baris);
        $range = $colA.$baris.':'.$colZ.$baris;
        $this->fill($sheet, $range, self::NAVY);
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setARGB('FF'.self::WHITE);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($baris)->setRowHeight(22);
        $this->border($sheet, $range);
    }

    /** Sel status berupa label + fill lembut + teks berwarna. */
    private function selStatus(Worksheet $sheet, string $cell, string $status): void
    {
        $label = ['perhatian' => 'Perlu perhatian', 'berjalan' => 'Berjalan', 'beres' => 'Beres'][$status] ?? $status;
        $s = self::STATUS[$status] ?? ['teks' => self::INK, 'fill' => self::WHITE];
        $sheet->setCellValue($cell, $label);
        $this->fill($sheet, $cell, $s['fill']);
        $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setARGB('FF'.$s['teks']);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /** Titik berwarna untuk sel matriks (tepat/telat/belum). */
    private function titik(Worksheet $sheet, string $cell, string $status): void
    {
        $warna = ['tepat' => self::STATUS['beres']['dot'], 'telat' => self::STATUS['berjalan']['dot']][$status] ?? self::DOT_BELUM;
        $sheet->setCellValue($cell, '●');
        $sheet->getStyle($cell)->getFont()->setSize(12)->getColor()->setARGB('FF'.$warna);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /** Kotak legenda + tabel materi/deadline per pertemuan. */
    private function legenda(Worksheet $sheet, array $pertemuan, int $baris): void
    {
        $sheet->setCellValue('A'.$baris, 'Keterangan');
        $sheet->getStyle('A'.$baris)->getFont()->setBold(true)->getColor()->setARGB('FF'.self::NAVY);
        $baris++;
        foreach ([['tepat', 'Tepat waktu'], ['telat', 'Terlambat'], ['belum', 'Belum mengumpulkan']] as [$st, $teks]) {
            $this->titik($sheet, 'A'.$baris, $st);
            $sheet->getStyle('A'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->setCellValue('B'.$baris, $teks);
            $baris++;
        }

        $baris++;
        $this->headerTabel($sheet, ['Pertemuan', 'Materi', 'Deadline'], 'A', 'C', $baris);
        $awal = $baris;
        $baris++;
        foreach ($pertemuan as $i => $p) {
            $sheet->setCellValue('A'.$baris, 'P'.$p['pertemuan']);
            $sheet->setCellValue('B'.$baris, $p['materi'] ?? '(tanpa materi)');
            $sheet->setCellValue('C'.$baris, $p['deadline']);
            $this->zebra($sheet, 'A'.$baris.':C'.$baris, $i);
            $baris++;
        }
        $this->border($sheet, 'A'.$awal.':C'.($baris - 1));
    }

    // ===================== Util styling =====================

    private function fill(Worksheet $sheet, string $range, string $rgb): void
    {
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.$rgb);
    }

    private function border(Worksheet $sheet, string $range, string $rgb = self::BORDER): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF'.$rgb);
    }

    private function zebra(Worksheet $sheet, string $range, int $i): void
    {
        if ($i % 2 === 1) {
            $this->fill($sheet, $range, self::ZEBRA);
        }
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    /** @param array<string,int> $map kolom => lebar */
    private function lebar(Worksheet $sheet, array $map): void
    {
        foreach ($map as $kolom => $w) {
            $sheet->getColumnDimension($kolom)->setWidth($w);
        }
    }

    /** Sentuhan akhir tiap sheet: matikan gridline + setup cetak. */
    private function rapikan(Worksheet $sheet, string $kolomAkhir): void
    {
        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.4)->setLeft(0.4)->setRight(0.4)->setBottom(0.5);
        $sheet->getHeaderFooter()->setOddFooter('&L&"Calibri"&8SIM Lab. Riset KK JKF&RHal &P/&N');
    }

    /**
     * Nama sheet unik (maks 31 char, tanpa karakter terlarang Excel: \ / ? * : [ ]).
     */
    private function judulSheet(array $kelas, array &$dipakai): string
    {
        $dasar = trim(($kelas['mata_kuliah'] ?? 'Kelas').' '.($kelas['nama_sesi'] ?? ''));
        $dasar = preg_replace('/[\\\\\/\?\*\:\[\]]/', ' ', $dasar) ?: 'Kelas';
        $dasar = trim(mb_substr($dasar, 0, 28));

        $judul = $dasar;
        $i = 2;
        while (isset($dipakai[$judul])) {
            $suffix = ' '.$i;
            $judul = mb_substr($dasar, 0, 28 - mb_strlen($suffix)).$suffix;
            $i++;
        }
        $dipakai[$judul] = true;

        return $judul;
    }

    /**
     * Konversi indeks kolom 1-based → huruf kolom Excel (1→A, 27→AA).
     */
    private function kolomExcel(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }
}
