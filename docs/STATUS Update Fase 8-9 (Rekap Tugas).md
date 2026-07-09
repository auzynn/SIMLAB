# STATUS Update — Maintenance Fase 8/9: Rekap Tugas Kelas Lab

**Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset KK JKF)
**Branch**: `main` · **Per**: 2026-07-09
**Dokumen acuan**: `1_PRD.md`, `2_SRS.md` (UC-06a), `3_SDD.md` (5.15), `4_TASK_BREAKDOWN.md` (T8.5–T8.10)

> Sesi maintenance (semua Fase 0–9 sudah tuntas sebelumnya). Penambahan fitur **Rekap Tugas Kelas Lab** — rekap pengumpulan tugas per kelas & per pertemuan selama satu semester, unduh **PDF + Excel**, data selalu live. **Tahap 1 selesai & terverifikasi.** Google Sheets sync (Tahap 2) sengaja ditunda.

---

## 1. Yang dikerjakan

Rekap kepatuhan pengumpulan tugas, untuk **Admin/Supervisor (semua kelas)** dan **Dosen (kelas miliknya)**. Dua tingkat rincian:
- **Ringkasan** — satu baris per kelas: peserta, pertemuan bertugas (X/16), pertemuan berjalan (N/16), tunggakan, status kepatuhan (perhatian/berjalan/beres), deadline terdekat.
- **Matriks detail per kelas** — peserta (baris) × pertemuan bertugas (kolom): `tepat` / `telat` / `belum`, plus total kumpul & jumlah telat.

Output: **halaman web**, **unduh PDF** (landscape), **unduh Excel .xlsx berformat**. Data dihitung on-request → selalu mencerminkan tugas terbaru ("update terus-menerus" tanpa snapshot).

---

## 2. Ringkasan teknis — Backend

- **`RekapTugasService`** (`app/Services/RekapTugasService.php`) — sumber tunggal agregasi:
  - `ringkasan(User)` — kepatuhan per kelas (logika **dipindah** dari `KelasLabController::rekapTugas`; endpoint lama `/kelas-lab/rekap-tugas` sekarang **delegasi** ke sini, bentuk JSON dijaga sama termasuk alias `total_tugas` → badge di `kelas-lab.vue`/`home-page.vue` tetap jalan).
  - `build(User)` — ringkasan + matriks detail (peserta status `disetujui` × pertemuan yang punya `deadline`). Sel `tepat`/`telat` dari `tugas.created_at` vs `deadline_pertemuan.deadline`. Dosen di-scope `dosen_id`.
- **Gate `view-rekap-tugas`** (Admin/Supervisor/Dosen) di `AppServiceProvider`. **`RekapTugasRequest`** (authorize + filter opsional).
- **`RekapTugasController`** — `index` (JSON), `pdf` (dompdf, Blade `resources/views/reports/rekap-tugas.blade.php`, A4 landscape), `excel` (streamDownload).
- **`RekapTugasExcelWriter`** (phpspreadsheet) — sheet **Ringkasan** (header tebal, freeze baris, warna status) + **satu sheet per kelas** (matriks NPM/Nama/P-n/Total/Telat, freeze kolom kiri, NPM sebagai teks, nama sheet aman & unik ≤31 char).
- **Routes** (grup `auth:sanctum`): `GET /api/rekap-tugas`, `/api/rekap-tugas/pdf`, `/api/rekap-tugas/excel`.

## 3. Ringkasan teknis — Frontend

- **`views/rekap-tugas.vue`** (route `/rekap-tugas`, guard `['admin','supervisor','dosen']`) — tabel ringkasan (badge warna status) + matriks per kelas (accordion, kolom NPM/Nama sticky, sel berwarna + link tugas + tooltip materi/deadline) + tombol **Unduh PDF** & **Unduh Excel** (blob download, Bearer token via interceptor).
- **Service** `services/rekap-tugas.js` (`rekap`/`pdf`/`excel`).
- **Entri navigasi**: nav header **"Rekap Tugas"** (Admin/Supervisor/Dosen), link di `sidemenu-admin.vue`, tombol di `kelas-lab.vue` (grup aksi Dosen/Supervisor).

## 4. Dependency & lingkungan

- **Baru**: `phpoffice/phpspreadsheet ^5.8` (composer). **Prasyarat**: `ext-zip` — sebelumnya nonaktif, **diaktifkan di `php.ini`** (`extension=zip`, `php_zip.dll` sudah tersedia di Laragon PHP 8.5.7). Tanpa ini `composer require` gagal & export .xlsx tidak jalan.
- Tidak ada tabel/migrasi baru (memakai `tugas`, `deadline_pertemuan`, `kelas_lab_peserta` yang sudah ada).

---

## 5. Verifikasi

- **Test backend: 192 lulus, 458 assertion** (naik dari 147 di milestone sebelumnya; `RekapTugasTest` kini **15** — 8 lama untuk badge + **7 baru** untuk laporan). Seluruh suite hijau → refactor `rekapTugas` tidak memecah apa pun.
- **Frontend `vite build` hijau** — chunk `rekap-tugas-*.js`/`.css` terbentuk. (Guard OOM `NODE_OPTIONS=--max-old-space-size=6144` dipakai.)
- **Belum diuji manual di browser/PDF/xlsx nyata** dengan data MySQL `simlab` — direkomendasikan smoke-test: login Dosen & Supervisor, buka `/rekap-tugas`, unduh PDF & Excel, submit satu tugas lalu unduh ulang untuk memastikan sel matriks berubah.

---

## 6. Sisa pekerjaan

- **T8.10 — Google Sheets sync (Tahap 2, ditunda)**: sinkron otomatis rekap ke satu Google Sheet (service account, auto-update saat tugas masuk). Menunggu kredensial Google Cloud. `RekapTugasService` sudah mengembalikan array terstruktur yang mudah dipetakan ke Sheets API. Catatan: app menulis hanya ke tab `DATA_*` agar kustomisasi manual pengguna tidak tertimpa.
- Smoke-test manual end-to-end (poin verifikasi di atas).

> Catatan konsistensi dok: `3_SDD.md 5.13` masih menyebut blok `presensi` pada response `/api/report`, sedangkan implementasi report kini memakai blok `tugas` (presensi telah di-drop). Di luar scope milestone ini — dicatat agar bisa dirapikan terpisah.
