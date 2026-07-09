<!DOCTYPE html>
{{-- Template dokumen PDF Laporan Lab (SRS UC-06, 3_SDD.md 5.13). Dirender via dompdf, bukan halaman SPA. --}}
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        .periode { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        td.angka { text-align: right; width: 90px; }
        h2 { font-size: 14px; margin: 0 0 6px; }
    </style>
</head>
<body>
    <h1>Laporan Aktivitas Laboratorium Riset</h1>
    <div class="periode">Periode: {{ $rekap['periode']['dari'] }} s.d. {{ $rekap['periode']['sampai'] }}</div>

    <h2>Peminjaman Ruangan</h2>
    <table>
        <tr><th>Total Pengajuan</th><td class="angka">{{ $rekap['peminjaman_ruangan']['total_pengajuan'] }}</td></tr>
        <tr><th>Disetujui</th><td class="angka">{{ $rekap['peminjaman_ruangan']['total_disetujui'] }}</td></tr>
        <tr><th>Ditolak</th><td class="angka">{{ $rekap['peminjaman_ruangan']['total_ditolak'] }}</td></tr>
        <tr><th>Menunggu</th><td class="angka">{{ $rekap['peminjaman_ruangan']['total_menunggu'] }}</td></tr>
    </table>

    <h2>Peminjaman Perangkat</h2>
    <table>
        <tr><th>Total Pengajuan</th><td class="angka">{{ $rekap['peminjaman_perangkat']['total_pengajuan'] }}</td></tr>
        <tr><th>Disetujui</th><td class="angka">{{ $rekap['peminjaman_perangkat']['total_disetujui'] }}</td></tr>
        <tr><th>Ditolak</th><td class="angka">{{ $rekap['peminjaman_perangkat']['total_ditolak'] }}</td></tr>
        <tr><th>Dikembalikan</th><td class="angka">{{ $rekap['peminjaman_perangkat']['total_dikembalikan'] }}</td></tr>
    </table>

    <h2>Pengumpulan Tugas</h2>
    <table>
        <tr><th>Total Terkumpul</th><td class="angka">{{ $rekap['tugas']['total_terkumpul'] }}</td></tr>
        <tr><th>Mahasiswa Unik</th><td class="angka">{{ $rekap['tugas']['total_mahasiswa_unik'] }}</td></tr>
        <tr><th>Jumlah Kelas</th><td class="angka">{{ $rekap['tugas']['total_kelas'] }}</td></tr>
    </table>
</body>
</html>
