# STATUS Update — Milestone Fase 5

**Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset KK JKF)
**Branch**: `main` · **Per**: 2026-07-05
**Dokumen acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`, `4_TASK_BREAKDOWN.md`

> Dokumen ringkas untuk serah-terima antar sesi. **Fase 0–5 tuntas; Fase 6–9 belum dimulai.** Prioritas berikutnya: **Fase 6 — Katalog Sertifikasi (Informasional)**.

---

## 1. Status per Fase

| Fase | Modul utama | Progres | Status |
|---|---|---|---|
| **0 · Fondasi** | Laravel + Vue 3/Vite, Sanctum, CORS, Socialite | 10/10 | ✅ Selesai |
| **1 · Auth & Manajemen User** | users/dosen/mahasiswa, Google OAuth, login manual, Kelola User | 24/25 | ✅ Selesai\* |
| **2 · Halaman Informasi Lab** | info_lab, Daftar & Detail Dosen, panel konten (TipTap) | 13/13 | ✅ Selesai |
| **3 · Peminjaman Ruangan, Matkul & Kelas Lab** | Data Master, Peminjaman Ruangan, Kelas Lab | 39/39 | ✅ Selesai |
| **4 · Inventaris & Peminjaman Perangkat** | perangkat, peminjaman_perangkat, perpanjangan | 16/16 | ✅ Selesai |
| **5 · Presensi Lab** | presensi check-in/out, rekap | 10/10 | ✅ Selesai |
| **6 · Katalog Sertifikasi** | sertifikasi (informasional) | 0/6 | ⬜ Belum |
| **7 · Portofolio Mahasiswa** | portofolio | 0/6 | ⬜ Belum |
| **8 · Laporan (Report)** | rekap + unduh PDF | 0/4 | ⬜ Belum |
| **9 · Notifikasi In-App** | notifikasi + integrasi Fase 3/4/5 | 0/21 | ⬜ Belum |

**Total: 112 / 150 task (≈ 75%).**

\* Fase 1 — T1.10 (Policy RBAC terpusat) *sebagian*: otorisasi via Gate per-modul + scoping controller.

---

## 2. Ringkasan teknis Fase 5 (yang baru ditambahkan)

**Tabel DB (migrasi baru)**:
- `presensi` — `user_id` (FK users, cascade), `keperluan` (varchar), `check_in` (datetime, WIB), `check_out` (datetime, nullable), `dicatat_oleh` (FK users, nullable, set null). Index `['user_id','check_out']` untuk cek sesi aktif.

**Aturan bisnis kunci Fase 5 (SRS UC-04)**:
- **Check-in** hanya Mahasiswa (`StorePresensiRequest`). **Aturan kunci**: ditolak (422) bila user masih punya sesi `check_out IS NULL` (validasi di `withValidator`).
- **Timestamp WIB**: `check_in`/`check_out` disimpan `Carbon::now('Asia/Jakarta')` — app timezone `UTC`, jadi wall-clock WIB disimpan apa adanya (T5.10 diuji dengan `setTestNow`).
- **Check-out** hanya pemilik sesi; sesi harus masih aktif (`check_out` null), else 422/403.
- **Rekap (`GET /api/presensi`)** cakupan per-role: Mahasiswa → miliknya; Dosen → mahasiswa bimbingan (via `dosen.mahasiswaBimbingan` / `mahasiswa.dosen_pembimbing_id`); Admin/Supervisor → semua.
- **Koreksi/hapus entri** (`PATCH`/`DELETE /api/presensi/{id}`): Dosen hanya untuk bimbingannya, Admin/Supervisor untuk entri mana pun; menetapkan `dicatat_oleh`.
- Tidak ada Gate baru — otorisasi via `authorize()` Form Request + helper scoping controller (pola konsisten repo).

**Endpoint** (grup `auth:sanctum`, `routes/api.php`):
`GET /api/presensi`; `POST /api/presensi/check-in`; `PATCH /api/presensi/{id}/check-out`; `PATCH /api/presensi/{id}`; `DELETE /api/presensi/{id}`.

**Frontend**: `views/presensi.vue` (adaptif per-role: Mahasiswa check-in/out + riwayat sendiri; Dosen/Admin/Supervisor rekap + statistik + hapus). Service `presensi.js`. Route `/presensi` (requiresAuth). Entri via **kartu "Presensi Lab"** di hub `views/jadwal-lab.vue` (semua role login). Util `formatWaktu` & `durasiPresensi` di `utils/format.js`.

**Test**: **117 test backend lulus** (104 Fase 0–4 + 13 Fase 5: `PresensiTest` — termasuk T5.9 double check-in ditolak, T5.10 timestamp WIB, scoping mahasiswa/dosen/supervisor, check-out, koreksi dosen+`dicatat_oleh`, hapus). Frontend `vite build` hijau (bundle `presensi`).

---

## 2b. Maintenance Fase 5 — Presensi berbasis konteks jadwal ⚠️ DIGANTIKAN (lihat 2c)

> **Catatan**: modul Presensi di bawah ini **sudah dihapus total** dan digantikan modul **Pengumpulan Tugas** (bagian 2c). Disimpan sebagai riwayat.

Perubahan format presensi (per permintaan lab): presensi kini **terikat konteks jadwal**, bukan check-in/out teks bebas.

- **UI hub (`views/jadwal-lab.vue`)**: kartu **Presensi** dipindah ke **paling atas** kolom aksi (di atas Formulir Peminjaman Ruangan); tombol mahasiswa `Check-in / Check-out` → **`Cek Presensi`**.
- **Aturan baru**: presensi hanya boleh saat mahasiswa punya **Kelas Lab terjadwal hari ini** (peserta `disetujui`, hari cocok + dalam rentang semester) atau **peminjaman ruangan disetujui hari ini**. Tanpa konteks → tidak bisa presensi (tak ada keperluan bebas).
- **Waktu ikut jadwal**: `check_in`/`check_out` diturunkan dari jadwal (Kelas Lab: tanggal hari ini + jam sesi; peminjaman: tanggal + jam peminjaman), bukan waktu tombol ditekan. `keperluan` diisi otomatis (`"Kelas Lab: {mk} — {sesi}"` / `"Peminjaman Ruangan: {ruangan}"`). Mekanik check-out terpisah dihapus.
- **DB (migrasi `..._add_konteks_to_presensi_table`)**: `presensi` + `kelas_lab_id` (FK, nullable, null-on-delete) & `peminjaman_ruangan_id` (FK, nullable, null-on-delete).
- **Endpoint**: `GET /api/presensi/konteks` (konteks layak hari ini + tanda `sudah_presensi`); `POST /api/presensi` (`store`) menggantikan `POST /api/presensi/check-in`; `PATCH /api/presensi/{id}/check-out` **dihapus**.
- **Frontend**: `views/presensi.vue` — mahasiswa memilih konteks hari ini lalu **Cek Presensi** (tombol nonaktif bila sudah); riwayat/rekap berkolom **Kegiatan · Tanggal · Waktu · Ruangan**. Util `formatRentangWaktu` di `utils/format.js`. Service `presensi.js` (`konteks`, `cek`).
- **Test**: `PresensiTest` ditulis ulang ke model konteks (cek presensi kelas/peminjaman, tolak bukan-hari-ini/belum-disetujui/duplikat, konteks + tanda `sudah_presensi`, scoping & koreksi/hapus tetap). **119 test backend lulus**; `vite build` hijau.

---

## 2c. Presensi diganti total → modul Pengumpulan Tugas

Per permintaan lab, fitur **Presensi dihapus seluruhnya** dan diganti **Pengumpulan Tugas**: mahasiswa mengirim **tautan/URL** tugas untuk **Kelas Lab yang diikuti** (peserta `disetujui`); **Dosen pengampu kelas + Supervisor/Admin** melihat & membuka. Entri diletakkan sebagai tombol sederhana di halaman **Kelas Lab** (bukan lagi kartu di Jadwal Lab).

- **Dihapus (backend)**: `Presensi` model/controller/request, `PresensiTest`, migrasi create+alter presensi. **Migrasi baru** `2026_07_06_000001_drop_presensi_table` (drop tabel `presensi`).
- **Dihapus (frontend)**: `views/presensi.vue`, `services/presensi.js`, route `/presensi`, kartu Presensi di `views/jadwal-lab.vue`, util `formatWaktu`/`durasiPresensi`/`formatRentangWaktu`, item "Rekap Presensi" (`sidemenu-admin.vue`, `admin-page.vue`).
- **DB baru** (`2026_07_06_000002_create_tugas_table`): `tugas` — `kelas_lab_id` (FK kelas_lab, cascade), `mahasiswa_id` (FK mahasiswa, cascade), `judul` (string), `tautan` (string 2048), timestamps, index `kelas_lab_id`.
- **Backend**: `Tugas` model; `StoreTugasRequest` (role mahasiswa + wajib peserta `disetujui` pada kelas tujuan; `tautan` rule `url`); `TugasController` (`index` scoping per-role, `store`, `destroy` pemilik/Admin/Supervisor). **Endpoint**: `GET /api/tugas`, `POST /api/tugas`, `DELETE /api/tugas/{tugas}`.
- **Frontend**: `views/tugas.vue` (Mahasiswa: form Kirim Tugas + "Tugas Saya"; Dosen/Admin/Supervisor: "Tugas Masuk" + buka/hapus). `services/tugas.js`. Route `/tugas`. Tombol **Kirim Tugas** (mahasiswa) & **Tugas Masuk** (dosen/supervisor) di header `views/kelas-lab.vue`.
- **Notifikasi (SRS UC-07)**: `TugasController@store` mengirim notifikasi `pengajuan_masuk` ke **dosen pengampu kelas + seluruh Supervisor** saat mahasiswa mengirim tugas (via `NotifikasiService`, dalam transaksi; null-safe bila kelas tanpa dosen). Ditambahkan helper `NotifikasiService::kirimKeRole()`. Muncul di `notification-bell.vue`. Diuji `test_kirim_tugas_memberi_notifikasi_ke_dosen_pengampu` & `test_kirim_tugas_memberi_notifikasi_ke_supervisor`.
- **Laporan (Fase 8)**: rekap Presensi diganti **rekap Tugas** (`total_terkumpul`, `total_mahasiswa_unik`, `total_kelas`) di `ReportController`, `reports/lab.blade.php`, `views/report.vue`; `ReportTest` struktur `presensi` → `tugas`.
- **Test**: `TugasTest` (kirim oleh peserta disetujui; tolak non-peserta/menunggu/URL invalid/non-mahasiswa; scoping mahasiswa-sendiri/dosen-kelasnya/supervisor-semua; hapus pemilik/supervisor, tolak mahasiswa lain). **143 test backend lulus**; `vite build` hijau (bundle `tugas`, tanpa `presensi`).

### Tambahan: tautan pengumpulan dokumen (diisi dosen) + keterangan Tautan

- **DB** (`2026_07_06_000003_add_tautan_pengumpulan_to_kelas_lab_table`): kolom `tautan_pengumpulan` (string 2048, nullable di DB agar baris lama valid) di `kelas_lab`. Ditambahkan ke `$fillable` KelasLab.
- **Validasi wajib**: `tautan_pengumpulan` `required|url|max:2048` di `StoreKelasLabRequest` & `UpdateKelasLabRequest` (dosen wajib isi saat buka/edit kelas).
- **Kelola Kelas Lab** (`kelola-kelas-lab.vue`): field **"Tautan Pengumpulan Dokumen"** (`type=url`, wajib) — tempat mahasiswa mengunggah laporan (PDF/DOCX).
- **Kirim Tugas** (`tugas.vue`): saat kelas dipilih, muncul tombol **"Tempat unggah dokumen (PDF/DOCX)"** menuju `tautan_pengumpulan` kelas (referensi tempat unggah; mahasiswa tetap menempel tautan hasilnya di kolom Tautan). Field Tautan diberi keterangan: gunakan GDrive/GitHub/sejenis + pakai shortlink.
- **Test**: `KelasLabTest` — payload helper + inline diberi `tautan_pengumpulan`; tambah uji create tanpa tautan → 422 (`assertJsonValidationErrors`) & uji tersimpan. **145 test backend lulus**; `vite build` hijau.

---

## 3. Catatan lingkungan (untuk sesi berikutnya)

- **DB dev**: MySQL 8.4.3 via Laragon (start manual), user `root` tanpa password, database `simlab`. Test pakai sqlite in-memory (`RefreshDatabase`).
- **Memori ketat** (~8GB RAM): `php artisan test` / `vite build` bisa gagal `out of memory` — **ulangi perintah**; `vite build` pakai `NODE_OPTIONS=--max-old-space-size=6144`. (Fase 5 kedua verifikasi lolos sekali jalan.)
- **Defender**: kadang merusak file `node_modules` (mis. `package.json` jadi biner). Perbaiki dengan menulis ulang `package.json` paket tsb.

---

## 4. Langkah berikutnya — Fase 6 (Katalog Sertifikasi)

Rujukan task: `4_TASK_BREAKDOWN.md` bagian **FASE 6**, skema SDD **3.13 `sertifikasi`**, SRS **UC-05**.
Modul **murni informasional** (bukan transaksi pendaftaran). Backend: migrasi + model `sertifikasi`, CRUD `/api/sertifikasi` (CUD Admin/Supervisor via Gate, read semua role). Frontend: halaman katalog (sudah ada `credential-info.vue` sebagai placeholder publik) + panel kelola.
