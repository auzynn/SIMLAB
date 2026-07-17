# PETUNJUK PENEMPELAN — Revisi Laporan KP SIM Lab. Riset

File Word yang direvisi: `LaporanKP_ArisAdityaNugroho_SIMLAB_draft.docx`

## File draft yang tersedia (semua di folder SIMLAB ini)

| File | Menggantikan bagian di Word | Status bagian lama |
|---|---|---|
| `Ringkasan_Summary_SIMLAB_draft.md` | SUMMARY + RINGKASAN | Masih 100% konten Siswa Foundation |
| `Bab_II_SIMLAB_draft.md` | BAB II (2.1.1 – 2.1.6) | Sebagian besar sudah benar (profil lab); yang salah: judul "Struktur Organisasi **Siswa Foundation**", Gambar II.1 & II.2, dan Tahap Pelaksanaan |
| `Bab_III_SIMLAB_draft.md` | BAB III lengkap | Masih 100% konten Siswa Foundation (CMS/monolith) |
| `Bab_IV_SIMLAB_draft.md` | BAB IV + catatan Daftar Pustaka & Lampiran | Masih 100% konten Siswa Foundation |

Bagian yang **sudah beres** di draft Word (tidak perlu disentuh): Cover/judul, lembar pengesahan (nama pembimbing Bu Irani), Kata Pengantar, BAB I (Latar Belakang, Tujuan, Ruang Lingkup, Sasaran Kompetensi, Waktu Pelaksanaan).

## Cara menempel yang aman

1. Buka `.docx` → aktifkan panel Navigation (View → Navigation Pane) untuk lompat antar heading.
2. Tempel per sub-bab (bukan sekali blok besar) supaya style Heading & penomoran otomatis tidak rusak: blok isi paragraf lama → hapus → tempel teks baru → cek style-nya tetap "Normal"/"Body".
3. Tabel: di file .md tabel ditulis format markdown — di Word, buat/isi ulang tabel yang sudah ada, atau Insert → Table lalu salin sel per baris.
4. Blok kode: tempel sebagai teks font Consolas/Courier New ukuran 9–10, di dalam tabel 1 kolom (mengikuti pola lama "Tabel III.x Kode Program ...").
5. Setelah semua ditempel: klik kanan Daftar Isi/Gambar/Tabel → **Update Field → Update entire table**.

## Yang masih harus Anda siapkan sendiri (tidak bisa saya buatkan)

- **Gambar II.1** — struktur organisasi Lab Riset KK JKF (ganti bagan Siswa Foundation).
- **Gambar II.2** — flowchart alur pelaksanaan KP Anda.
- **Gambar III.3–III.5** — tangkapan layar aplikasi, PDF rekap, dan hasil `php artisan test` (hasil terakhir: **207 passed, 494 assertions** — sudah saya jalankan dan lulus 15 Juli 2026).

## Gambar yang sudah dibuatkan (SVG, tinggal Insert → Pictures di Word)

- **Gambar II.3** — `Gambar_II3_Metode_Inkremental.svg` (metode pengembangan inkremental, siklus per fase + deretan Fase 0–9).
- **Gambar III.1** — `Gambar_III1_Arsitektur.svg` (arsitektur: Pengguna → Vue 3 SPA ⇄ JSON + Bearer token ⇄ Laravel REST API berlapis Route/Form Request/Controller/Service/Model → MySQL, plus Google OAuth, Gate/Policy, Scheduler, dompdf/PhpSpreadsheet).
- **Gambar III.2** — `Gambar_III2_ERD.svg` (ERD lengkap 18 tabel + pivot: setiap tabel memuat daftar field + tipe data + tanda PK/FK/UQ, panah FK menunjuk tabel induk, kardinalitas 1/N, relasi opsional putus-putus, dan legenda).

Word 2019/365 mendukung penyisipan SVG langsung; bila versi Word menolak, buka file SVG di browser lalu screenshot, atau minta saya konversikan ke PNG.
- **Ringkasan Log Book** — tabel log book asli milik Anda (draft 3.1 hanya garis besar).
- **Daftar Pustaka** — daftar rujukan final (catatan di `Bab_IV_SIMLAB_draft.md`).
- Konfirmasi **status akreditasi prodi** & **tahun visi lab** (catatan di `Bab_II_SIMLAB_draft.md`).
