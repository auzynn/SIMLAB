# STATUS Update — Milestone Fase 4

**Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset KK JKF)
**Branch**: `main` · **Per**: 2026-07-05
**Dokumen acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`, `4_TASK_BREAKDOWN.md`

> Dokumen ringkas untuk serah-terima antar sesi. **Fase 0–4 tuntas; Fase 5–9 belum dimulai.** Prioritas berikutnya: **Fase 5 — Presensi Laboratorium**.

---

## 1. Status per Fase

| Fase | Modul utama | Progres | Status |
|---|---|---|---|
| **0 · Fondasi** | Laravel + Vue 3/Vite, Sanctum, CORS, Socialite | 10/10 | ✅ Selesai |
| **1 · Auth & Manajemen User** | users/dosen/mahasiswa, Google OAuth, login manual, Kelola User | 24/25 | ✅ Selesai\* |
| **2 · Halaman Informasi Lab** | info_lab, Daftar & Detail Dosen, panel konten (TipTap) | 13/13 | ✅ Selesai |
| **3 · Peminjaman Ruangan, Matkul & Kelas Lab** | Data Master, Peminjaman Ruangan, Kelas Lab | 39/39 | ✅ Selesai |
| **4 · Inventaris & Peminjaman Perangkat** | perangkat, peminjaman_perangkat, perpanjangan | 16/16 | ✅ Selesai |
| **5 · Presensi Lab** | presensi check-in/out, rekap | 0/10 | ⬜ Belum |
| **6 · Katalog Sertifikasi** | sertifikasi (informasional) | 0/6 | ⬜ Belum |
| **7 · Portofolio Mahasiswa** | portofolio | 0/6 | ⬜ Belum |
| **8 · Laporan (Report)** | rekap + unduh PDF | 0/4 | ⬜ Belum |
| **9 · Notifikasi In-App** | notifikasi + integrasi Fase 3/4/5 | 0/21 | ⬜ Belum |

**Total: 102 / 150 task (≈ 68%).**

\* Fase 1 — T1.10 (Policy RBAC terpusat) *sebagian*: otorisasi via Gate per-modul; kini bertambah `approve-peminjaman-perangkat`.

---

## 2. Ringkasan teknis Fase 4 (yang baru ditambahkan)

**Tabel DB (migrasi baru)**:
- `perangkat` — `nama_perangkat`, `nomor_seri` (unique), `kategori` (nullable), `status` enum(`tersedia`,`dipinjam`,`perbaikan`).
- `peminjaman_perangkat` — `perangkat_id`, `user_id`, `tanggal_pinjam`, `tanggal_kembali_rencana`, `tanggal_kembali_aktual` (nullable), `status` enum(`menunggu`,`disetujui`,`ditolak`,`dikembalikan`), `disetujui_oleh`.
- `perpanjangan_peminjaman` — `peminjaman_perangkat_id`, `tanggal_kembali_baru`, `status` enum(`menunggu`,`disetujui`,`ditolak`), `disetujui_oleh`.

**Aturan bisnis kunci Fase 4 (SRS UC-03)**:
- Ajukan peminjaman **hanya Mahasiswa** (`StorePeminjamanPerangkatRequest`); perangkat harus `tersedia` saat diajukan.
- **Approve** (Admin/Supervisor, Gate `approve-peminjaman-perangkat`): re-validasi perangkat masih `tersedia`, lalu DB transaction → peminjaman `disetujui` + perangkat `dipinjam`.
- **Kembalikan** (tambahan): status → `dikembalikan`, `tanggal_kembali_aktual` = hari ini, perangkat → `tersedia`.
- **Batalkan/hapus pengajuan** (`DELETE`): pemilik (Mahasiswa) boleh membatalkan miliknya selama masih `menunggu`; Admin/Supervisor boleh menghapus kapan saja.
- **Perpanjangan**: hanya pemilik & saat status `disetujui`; **ditolak bila `tanggal_kembali_rencana` sudah lewat** (aturan kunci T4.8). Saat perpanjangan **disetujui**, `tanggal_kembali_rencana` induk diperbarui otomatis via DB transaction (T4.9).
- **Hapus perangkat**: ditolak (422) bila status bukan `tersedia` atau masih ada peminjaman aktif (`menunggu`/`disetujui`).
- **Seeder** `PerangkatSeeder` (10 perangkat contoh JKF, idempotent via `nomor_seri`) terdaftar di `DatabaseSeeder`.

**Endpoint** (grup `auth:sanctum`, `routes/api.php`):
`GET/POST/PATCH/DELETE /api/perangkat`; `GET/POST/DELETE /api/peminjaman-perangkat`; `PATCH …/{id}/approve|reject|kembalikan`; `POST …/{id}/perpanjangan`; `PATCH /api/perpanjangan/{id}/approve|reject`.

**Frontend**: `views/perangkat.vue` (katalog perangkat). Peminjaman & persetujuan perangkat **disatukan sebagai tab** ke halaman yang sudah ada — `views/peminjaman-saya.vue` (tab Ruangan/Perangkat: form + riwayat + perpanjangan + batal) dan `views/persetujuan-peminjaman.vue` (tab Ruangan/Perangkat: approve/reject/kembalikan + perpanjangan) via query `?tab=perangkat`; path lama `/peminjaman-perangkat` & `/persetujuan-perangkat` di-**redirect**. Tab **Perangkat** di `admin-data-master.vue`. Service `perangkat.js` & `peminjaman-perangkat.js`. Nav header menu "Perangkat".

**Test**: **104 test backend lulus** (78 Fase 0–3 + 26 Fase 4: `PerangkatTest` 7, `PeminjamanPerangkatTest` 14 — termasuk alur batal/hapus, `PerpanjanganTest` 5). Frontend `vite build` hijau (per sesi Fase 4).

---

## 3. Catatan lingkungan (untuk sesi berikutnya)

- **DB dev**: MySQL 8.4.3 via Laragon (start manual), user `root` tanpa password, database `simlab`. Test pakai sqlite in-memory (`RefreshDatabase`).
- **Memori sangat ketat** (~8GB RAM, page file 4GB): `php artisan test` **dan** `vite build` sering gagal `VirtualAlloc … paging file too small / out of memory`. **Solusinya: ulangi perintah** — biasanya lolos saat memori sempat lega (kedua verifikasi Fase 4 lolos setelah retry). `vite build` pakai `NODE_OPTIONS=--max-old-space-size=4096`.
- **Defender**: kadang merusak file `node_modules` (mis. `prosemirror-transform/package.json` jadi biner). Perbaiki dengan menulis ulang `package.json` paket tsb.

---

## 4. Langkah berikutnya — Fase 5 (Presensi Laboratorium)

Rujukan task: `4_TASK_BREAKDOWN.md` bagian **FASE 5**, skema SDD **3.12 `presensi`**, SRS **UC-04**.
Backend: migrasi + model `presensi`, endpoint check-in/check-out, rekap. Frontend: halaman presensi & rekap.
