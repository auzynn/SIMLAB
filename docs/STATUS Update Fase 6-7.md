# STATUS Update — Milestone Fase 6 & 7

**Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset KK JKF)
**Branch**: `main` · **Per**: 2026-07-06
**Dokumen acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`, `4_TASK_BREAKDOWN.md`

> Dokumen ringkas untuk serah-terima antar sesi. **Fase 0–7 tuntas; Fase 8–9 belum dimulai.** Prioritas berikutnya: **Fase 8 — Laporan (Report)**.

---

## 1. Status per Fase

| Fase | Modul utama | Progres | Status |
|---|---|---|---|
| **0 · Fondasi** | Laravel + Vue 3/Vite, Sanctum, CORS, Socialite | 10/10 | ✅ Selesai |
| **1 · Auth & Manajemen User** | users/dosen/mahasiswa, Google OAuth, login manual, Kelola User | 24/25 | ✅ Selesai\* |
| **2 · Halaman Informasi Lab** | info_lab, Daftar & Detail Dosen, panel konten (TipTap) | 13/13 | ✅ Selesai |
| **3 · Peminjaman Ruangan, Matkul & Kelas Lab** | Data Master, Peminjaman Ruangan, Kelas Lab | 39/39 | ✅ Selesai |
| **4 · Inventaris & Peminjaman Perangkat** | perangkat, peminjaman_perangkat, perpanjangan | 16/16 | ✅ Selesai |
| **5 · Presensi Lab** | presensi berbasis konteks jadwal, rekap | 10/10 | ✅ Selesai |
| **6 · Katalog Sertifikasi** | sertifikasi (informasional) | 6/6 | ✅ Selesai |
| **7 · Portofolio Mahasiswa** | portofolio | 6/6 | ✅ Selesai |
| **8 · Laporan (Report)** | rekap + unduh PDF | 0/4 | ⬜ Belum |
| **9 · Notifikasi In-App** | notifikasi + integrasi Fase 3/4/5 | 0/21 | ⬜ Belum |

**Total: 124 / 150 task (≈ 83%).** Hanya **Fase 8 (Laporan)** & **Fase 9 (Notifikasi In-App)** yang tersisa.

\* Fase 1 — T1.10 (Policy RBAC terpusat) *sebagian*: otorisasi via Gate per-modul + scoping controller.

**Enhancement di luar backlog fase** (sudah jalan, tidak dihitung dalam 150 task): **Delegasi Asisten Lab** (`/api/aslab` promote/demote mahasiswa↔supervisor, `admin-aslab.vue`, `AslabTest`), **reset password** akun Google (`POST /api/auth/reset-password`), **unggah lampiran** konten Info Lab (`POST /api/info-lab/upload`). Lihat `4_TASK_BREAKDOWN.md` → Catatan Progres.

---

## 2. Ringkasan teknis Fase 6 — Katalog Sertifikasi (informasional)

Modul **murni informasional** (SRS UC-05): sistem hanya menampilkan katalog sertifikasi eksternal (Mikrotik, Cisco, Oracle, EC-Council, dll) sebagai referensi. Tidak ada transaksi pendaftaran — pendaftaran dilakukan langsung ke penyelenggara.

**Tabel DB (migrasi baru `2026_07_05_000003_create_sertifikasi_table`)**:
- `sertifikasi` — **berdiri sendiri, tanpa relasi ke `users`**. Kolom: `nama_sertifikasi`, `penyelenggara`, `jadwal` (string nullable — boleh teks bebas), `persyaratan` (text nullable), `tautan_pendaftaran` (string nullable).

**Aturan bisnis / otorisasi**:
- **Read** (`GET /api/sertifikasi`) terbuka untuk semua role login.
- **CUD** (`store`/`update`/`destroy`) via Gate **`manage-master-data`** (Admin/Supervisor) — dipakai bersama katalog master lain (ruangan/mata kuliah/perangkat), tanpa Gate baru.
- Catatan RBAC: matriks SRS menandai Dosen `–` untuk Sertifikasi; konsisten dengan pola repo, `–` diperlakukan sebagai "tanpa CUD" — **read tetap dibuka** untuk semua role login (sama seperti Data Master lain).

**Endpoint** (grup `auth:sanctum`): `apiResource('sertifikasi')` → `index`, `store`, `update`, `destroy`.

**Frontend**:
- `views/sertifikasi.vue` (route `/sertifikasi`, semua login) — grid kartu (nama, penyelenggara, jadwal, persyaratan) + tombol **"Info Pendaftaran ↗"** ke tautan eksternal.
- `views/admin-sertifikasi.vue` (route `/admin/sertifikasi`, guard `['admin','supervisor']`) — CRUD penuh + paginasi lokal.
- Service `services/sertifikasi.js`. Nav header **"Sertifikasi"**; link aktif di `sidemenu-admin.vue` & kartu hub `admin-page.vue` (placeholder "Segera hadir" dilepas).

**Seeder**: `SertifikasiSeeder` (4 entri: MTCNA, CCNA, CEH, Oracle SQL — idempotent via `updateOrCreate`), dipanggil di `DatabaseSeeder`.

**Test**: `SertifikasiTest` (6) — read mahasiswa/guest, CUD admin & supervisor, mahasiswa CUD → 403, validasi field wajib.

---

## 3. Ringkasan teknis Fase 7 — Portofolio Mahasiswa

Tempat mahasiswa mengunggah hasil riset/proyek/publikasi (PRD 3.7). Read terbuka semua role login (mis. Dosen mencari topik TA); CUD hanya pemilik.

**Tabel DB (migrasi baru `2026_07_05_000004_create_portofolio_table`)**:
- `portofolio` — `user_id` (FK users, **cascade on delete**), `judul`, `deskripsi` (text nullable), `tautan` (string nullable), `tanggal` (date nullable). Model cast `tanggal` → date; relasi `Portofolio::user()` belongsTo, `User::portofolio()` hasMany.

**Aturan bisnis / otorisasi**:
- **Read** (`GET /api/portofolio`) semua role login; filter opsional `?user_id=` (dipakai tab "Portofolio Saya").
- **Create** (`StorePortofolioRequest`): **Mahasiswa saja**; `user_id` di-set dari user login (bukan input).
- **Update** (`UpdatePortofolioRequest`): **hanya pemilik** (`user_id` cocok dengan user login).
- **Delete**: cek kepemilikan di controller — 403 bila bukan pemilik.
- Tidak ada Gate baru — otorisasi via `authorize()` Form Request + cek kepemilikan (pola konsisten repo).

**Endpoint** (grup `auth:sanctum`): `apiResource('portofolio')` → `index`, `store`, `update`, `destroy`.

**Frontend**:
- `views/portofolio.vue` (route `/portofolio`, semua login) — tab **"Portofolio Saya"** (Mahasiswa: form tambah/edit judul/deskripsi/tautan/tanggal + hapus, grid kartu) & tab **"Jelajah Semua"** (grid kartu semua portofolio + nama pemilik; default untuk non-Mahasiswa).
- Service `services/portofolio.js`. Nav header **"Portofolio"**. Format tanggal pakai `formatTanggalId` di `utils/format.js`.

**Test**: `PortofolioTest` (7) — read semua role/guest, mahasiswa tambah miliknya (`user_id` dari login), dosen tambah → 403, validasi judul, pemilik ubah/hapus, mahasiswa lain ubah/hapus → 403.

---

## 4. Verifikasi

- **Test backend: 132 lulus** (119 Fase 0–5 + 13 baru: `SertifikasiTest` 6 + `PortofolioTest` 7), 283 assertion.
- Frontend **`vite build` hijau** — chunk `sertifikasi`, `admin-sertifikasi`, `portofolio` terbentuk.
- **DB dev `simlab`**: migrasi `sertifikasi` & `portofolio` sudah dijalankan (`php artisan migrate`); `SertifikasiSeeder` sudah di-run (4 baris). Portofolio 0 baris (normal — diisi mahasiswa).

> ⚠️ Catatan operasional: test memakai sqlite in-memory (`RefreshDatabase`) sehingga lulus tanpa menyentuh DB dev. **Setiap migrasi baru wajib dijalankan manual** (`php artisan migrate`) ke MySQL `simlab` agar halaman tidak error `Base table not found` — inilah yang sempat terjadi pada Fase 6/7 sebelum migrasi di-run.

---

## 5. Catatan lingkungan (untuk sesi berikutnya)

- **DB dev**: MySQL 8.4.3 via Laragon (start manual), user `root` tanpa password, database `simlab`. Test pakai sqlite in-memory (`RefreshDatabase`). **Jalankan `php artisan migrate` setiap ada migrasi baru.**
- **Memori ketat** (~8GB RAM): `php artisan test` / `vite build` bisa gagal `out of memory` — **ulangi perintah**; `vite build` pakai `NODE_OPTIONS=--max-old-space-size=6144`.
- **Defender**: kadang merusak file `node_modules` (mis. `package.json` jadi biner). Perbaiki dengan menulis ulang `package.json` paket tsb.

---

## 6. Langkah berikutnya — Fase 8 (Laporan/Report)

Rujukan task: `4_TASK_BREAKDOWN.md` bagian **FASE 8**, SRS **UC-06**, PRD **3.9**.
- Backend: `GET /api/report?from=&to=` (agregasi peminjaman/presensi/aktivitas lab) + `GET /api/report/pdf` (generate PDF). **Akses hanya Admin/Supervisor.**
- ⚠️ PDF butuh dependency baru (mis. `barryvdh/laravel-dompdf`) — **konfirmasi ke user sebelum install** (sesuai `agent.md` Bagian 3).
- Frontend: halaman Report (filter rentang tanggal, rekap, tombol Download PDF).

Setelah itu **Fase 9 — Notifikasi In-App** (efek samping approve/reject Fase 3/4/5 + bell navbar).
