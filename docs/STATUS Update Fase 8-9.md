# STATUS Update — Milestone Fase 8 & 9 (Final)

**Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset KK JKF)
**Branch**: `main` · **Per**: 2026-07-06
**Dokumen acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`, `4_TASK_BREAKDOWN.md`

> Dokumen serah-terima antar sesi. **Seluruh FASE 0–9 tuntas.** Tidak ada fase tersisa.

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
| **8 · Laporan (Report)** | rekap + unduh PDF | 4/4 | ✅ Selesai |
| **9 · Notifikasi In-App** | notifikasi + integrasi Fase 3/4/5 | 21/21 | ✅ Selesai |

**Total: 149 / 150 task (≈ 99%).** Satu-satunya yang *sebagian*: T1.10 (Policy RBAC terpusat) — otorisasi berjalan via Gate per-modul + scoping controller (fungsional & teruji), belum disatukan jadi Policy tunggal.

\* Fase 1 — lihat T1.10 di atas.

> **Enhancement di luar 150 task** (sudah jalan): Delegasi Aslab (`/api/aslab`), reset password akun Google (`POST /api/auth/reset-password`), unggah lampiran Info Lab (`POST /api/info-lab/upload`).

---

## 2. Ringkasan teknis Fase 8 — Laporan/Report (SRS UC-06)

Rekap aktivitas lab dalam rentang tanggal, khusus **Admin/Supervisor**.

**Backend**:
- `ReportController` — `index` (JSON) & `pdf` (unduh). Otorisasi + validasi lewat **`ReportRequest`** (`authorize()` → Gate `view-report`; `rules()` → `from`/`to` `date`, `to after_or_equal:from`).
- Gate baru **`view-report`** (Admin/Supervisor) di `AppServiceProvider`.
- Struktur rekap sesuai **3_SDD.md 5.13**: `periode {dari, sampai}`, `peminjaman_ruangan {total_pengajuan, total_disetujui, total_ditolak, total_menunggu}`, `peminjaman_perangkat {…, total_dikembalikan}`, `presensi {total_sesi, total_mahasiswa_unik, rata_rata_durasi_menit}`. Default **30 hari terakhir** bila `from`/`to` kosong. Peminjaman dihitung by `created_at`, presensi by `check_in`.
- **PDF**: dependency baru **`barryvdh/laravel-dompdf ^3.1`** (dikonfirmasi user). `Pdf::loadView('reports.lab', …)->download(...)`. Blade `resources/views/reports/lab.blade.php` — **satu-satunya Blade di proyek**, sah karena dokumen PDF (bukan halaman SPA).
- Route: `GET /api/report`, `GET /api/report/pdf`.

**Frontend**:
- `views/report.vue` (route `/report`, guard `['admin','supervisor']`) — filter tanggal, 3 kartu rekap, tombol **Unduh PDF** (fetch **blob** via `reportService.pdf` supaya header `Authorization` terkirim, lalu `URL.createObjectURL` → unduh).
- Service `services/report.js`. Entri: **nav header "Laporan"** (Admin/Supervisor) + kartu hub `admin-page.vue` + link `sidemenu-admin.vue`.

**Test**: `ReportTest` (7) — Admin/Supervisor 200; Dosen/Mahasiswa 403; guest 401; struktur JSON; agregasi status peminjaman; unduh PDF `content-type application/pdf`.

---

## 3. Ringkasan teknis Fase 9 — Notifikasi In-App (SRS UC-07)

Notifikasi dibuat **otomatis oleh sistem** sebagai efek samping aksi lain, **di dalam transaksi DB yang sama** (rollback → notifikasi ikut batal). Bukan endpoint publik.

**Tabel DB (migrasi baru `2026_07_06_000001_create_notifikasi_table`)**:
- `notifikasi` — `user_id` (FK cascade), `judul`, `pesan` (text), `tipe` enum (`pengajuan_masuk`, `status_pengajuan`, `pendaftaran`), `referensi_id` (unsignedBigInt nullable, tanpa FK — lintas tabel), `is_read` (bool default false). Index `(user_id, is_read)`.

**Service & integrasi**:
- `NotifikasiService::kirim(userId, judul, pesan, tipe, referensiId)` + `kirimKeApprover(...)` (semua Admin/Supervisor).
- **Pemicu → penerima** (sesuai tabel SRS UC-07), dipanggil dalam transaksi masing-masing:
  - Peminjaman ruangan: pengajuan baru → approver; approve/reject → pengaju.
  - Peminjaman perangkat: pengajuan baru → approver; approve/reject → pengaju.
  - Perpanjangan: pengajuan baru → approver; approve/reject → pengaju (pemilik peminjaman induk).
  - Kelas Lab `daftar` → mahasiswa pendaftar (konfirmasi terkirim).

**Endpoint (3_SDD.md 5.14)**: `GET /api/notifikasi` (+`unread_count`), `PATCH /api/notifikasi/read-all`, `PATCH /api/notifikasi/{id}/read`, `DELETE /api/notifikasi/{id}`. Semua hanya milik sendiri (403 jika bukan). `GET /api/auth/me` kini menyertakan **`unread_notifications_count`**.

**Frontend**:
- `components/notification-bell.vue` — lonceng + badge merah + panel dropdown (list, tandai satu/semua dibaca, hapus per item; unread background biru; klik-luar menutup). Dipasang di `header-component.vue` saat login.
- Store `stores/notifikasi.js` (Pinia) — `items`, `unreadCount`; badge **diseed dari `me()`**, daftar dimuat saat panel dibuka; `reset()` saat logout.
- Service `services/notifikasi.js`.

**Test**: `NotifikasiTest` (8) — termasuk T9.17 (approve→pengaju saja), T9.18 (pengajuan→approver, bukan pengaju), T9.19 (`me` unread count), T9.20 (read 403 bukan milik), **T9.21 (rollback atomik: `DB::transaction` + throw → 0 notifikasi orphan)**, plus scoping index, read-all, hapus.

---

## 4. Verifikasi

- **Test backend: 147 lulus, 335 assertion** (132 Fase 0–7 + **15 baru**: `ReportTest` 7 + `NotifikasiTest` 8). Semua hijau.
- Frontend **`vite build` hijau** (chunk `report` terbentuk; `notification-bell` masuk bundel header/index). Catatan: build sempat OOM 2×, **lulus pada percobaan ke-3** (pakai `NODE_OPTIONS=--max-old-space-size=6144`).
- **DB dev `simlab`**: migrasi `notifikasi` sudah dijalankan (`php artisan migrate`). `notifikasi` 0 baris (normal — diisi oleh aksi pemicu).

> ⚠️ Test memakai sqlite in-memory (`RefreshDatabase`) sehingga lulus tanpa menyentuh DB dev. **Migrasi `notifikasi` sudah di-run ke MySQL `simlab`** — halaman tidak akan error `Base table not found`.

---

## 5. Catatan lingkungan

- **DB dev**: MySQL 8.4.3 via Laragon (start manual), user `root` tanpa password, database `simlab`. Test pakai sqlite in-memory.
- **Dependency baru**: `barryvdh/laravel-dompdf ^3.1` (composer). Terpasang mulus (tidak kena isu Defender kali ini).
- **Memori ketat** (~8GB RAM): `vite build` bisa gagal `out of memory` (esbuild Go OOM / V8 heap OOM) — **ulangi perintah** (lulus pada percobaan ke-3). Pakai `NODE_OPTIONS=--max-old-space-size=6144`.

---

## 6. Status proyek

**Seluruh FASE 0–9 selesai.** Tidak ada fase backlog tersisa. Sisa pekerjaan opsional (bukan bagian 150 task):
- T1.10 — konsolidasi otorisasi ke Laravel Policy terpusat (saat ini via Gate per-modul; fungsional).
- Pemolesan UX/aksesibilitas, seed data demo, dan test frontend (composables/services) bila diinginkan.
