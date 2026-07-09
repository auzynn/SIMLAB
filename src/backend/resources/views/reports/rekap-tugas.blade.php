<!DOCTYPE html>
{{-- Template PDF Rekap Tugas Kelas Lab (2_SRS.md UC-06, 3_SDD.md 5.15). Dirender via dompdf (landscape). --}}
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        h2 { font-size: 12px; margin: 14px 0 4px; }
        .meta { color: #6b7280; margin-bottom: 12px; font-size: 10px; }
        .sub { color: #6b7280; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 3px 5px; text-align: left; }
        th { background: #f3f4f6; }
        td.c { text-align: center; }
        .st-perhatian { background: #f8d7da; }
        .st-berjalan { background: #fff3cd; }
        .st-beres { background: #d1e7dd; }
        .cell-tepat { background: #d1e7dd; text-align: center; }
        .cell-telat { background: #fff3cd; text-align: center; }
        .cell-belum { background: #f8d7da; text-align: center; }
        .legend { font-size: 9px; color: #6b7280; margin: 2px 0 10px; }
        .kelas { page-break-inside: avoid; }
        .kosong { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
    <h1>Rekap Tugas Kelas Lab</h1>
    <div class="meta">Dibuat: {{ $rekap['generated_at'] }} WIB</div>

    <h2>Ringkasan Kepatuhan per Kelas</h2>
    <table>
        <thead>
            <tr>
                <th>Mata Kuliah</th><th>Sesi</th><th>Dosen</th><th>Hari</th><th>Jam</th>
                <th>Peserta</th><th>Bertugas</th><th>Pertemuan</th><th>Tunggakan</th><th>Status</th><th>Deadline Terdekat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap['ringkasan'] as $r)
                <tr>
                    <td>{{ $r['mata_kuliah'] }}</td>
                    <td>{{ $r['nama_sesi'] }}</td>
                    <td>{{ $r['dosen'] }}</td>
                    <td>{{ ucfirst($r['hari']) }}</td>
                    <td>{{ $r['jam'] }}</td>
                    <td class="c">{{ $r['peserta_disetujui'] }}</td>
                    <td class="c">{{ $r['pertemuan_bertugas'] }}/16</td>
                    <td class="c">{{ $r['pertemuan_berjalan'] }}/16</td>
                    <td class="c">{{ $r['tunggakan'] }}</td>
                    <td class="st-{{ $r['status'] }}">
                        @php($label = ['perhatian' => 'Perlu perhatian', 'berjalan' => 'Berjalan', 'beres' => 'Beres'])
                        {{ $label[$r['status']] ?? $r['status'] }}
                    </td>
                    <td>{{ $r['deadline_terdekat'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="kosong">Tidak ada kelas.</td></tr>
            @endforelse
        </tbody>
    </table>

    @foreach ($rekap['detail'] as $kelas)
        <div class="kelas">
            <h2>{{ $kelas['mata_kuliah'] }} — {{ $kelas['nama_sesi'] }}</h2>
            <div class="sub">{{ $kelas['dosen'] ?? '-' }} · {{ ucfirst($kelas['hari']) }} {{ $kelas['jam'] }}</div>

            @if (empty($kelas['pertemuan']))
                <div class="kosong">Belum ada pertemuan yang diberi tugas/deadline.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>NPM</th><th>Nama</th>
                            @foreach ($kelas['pertemuan'] as $p)
                                <th class="c">P{{ $p['pertemuan'] }}</th>
                            @endforeach
                            <th class="c">Total</th><th class="c">Telat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kelas['peserta'] as $ps)
                            <tr>
                                <td>{{ $ps['npm'] }}</td>
                                <td>{{ $ps['nama'] }}</td>
                                @foreach ($kelas['pertemuan'] as $p)
                                    @php($sel = $ps['sel'][$p['pertemuan']] ?? ['status' => 'belum'])
                                    <td class="cell-{{ $sel['status'] }}">
                                        {{ ['tepat' => '✓', 'telat' => '!', 'belum' => '–'][$sel['status']] ?? '–' }}
                                    </td>
                                @endforeach
                                <td class="c">{{ $ps['total_kumpul'] }}</td>
                                <td class="c">{{ $ps['telat'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($kelas['pertemuan']) + 4 }}" class="kosong">Belum ada peserta disetujui.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="legend">
                    Keterangan: ✓ tepat waktu · ! telat · – belum mengumpulkan.
                    @foreach ($kelas['pertemuan'] as $p)
                        &nbsp;|&nbsp; P{{ $p['pertemuan'] }}: {{ $p['materi'] ?? '(tanpa materi)' }} (deadline {{ $p['deadline'] }})
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</body>
</html>
