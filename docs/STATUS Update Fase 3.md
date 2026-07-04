# STATUS Update — Milestone Fase 3

**Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset KK JKF)
**Tag rilis**: `v0.8.1` (patch) · **Branch**: `main` · **Per**: 2026-06-29
**Dokumen acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`, `4_TASK_BREAKDOWN.md`

> Dokumen ringkas untuk serah-terima antar sesi. **Fase 0–3 tuntas; Fase 4–9 belum dimulai.** Prioritas berikutnya: **Fase 4 — Peminjaman Perangkat**.

---

## 1. Status per Fase

| Fase | Modul utama | Progres | Status |
|---|---|---|---|
| **0 · Fondasi** | Laravel 13 + Vue 3/Vite, Sanctum, CORS, Socialite, Pint/ESLint, PHPUnit | 10/10 | ✅ Selesai |
| **1 · Auth & Manajemen User** | users/dosen/mahasiswa, Google OAuth, login manual, set/ubah password, avatar, edit profil, Kelola User | 24/25 | ✅ Selesai\* |
| **2 · Halaman Informasi Lab** | info_lab (beranda/visi-misi/kepala-lab/roadmap), Daftar & Detail Dosen, panel konten (TipTap) | 13/13 | ✅ Selesai |
| **3 · Peminjaman Ruangan, Mata Kuliah & Kelas Lab** | Data Master (ruangan/matkul), Peminjaman Ruangan (kalender/approve), Kelas Lab (buka/daftar/persetujuan/peserta) | 39/39 | ✅ Selesai |
| **4 · Inventaris & Peminjaman Perangkat** | perangkat, peminjaman_perangkat, perpanjangan | 0/16 | ⬜ Belum |
| **5 · Presensi Lab** | presensi check-in/out, rekap | 0/10 | ⬜ Belum |
| **6 · Katalog Sertifikasi** | sertifikasi (informasional) | 0/6 | ⬜ Belum |
| **7 · Portofolio Mahasiswa** | portofolio | 0/6 | ⬜ Belum |
| **8 · Laporan (Report)** | rekap + unduh PDF | 0/4 | ⬜ Belum |
| **9 · Notifikasi In-App** | notifikasi + integrasi ke Fase 3/4/5 | 0/21 | ⬜ Belum |

**Total: 86 / 150 task (≈ 57%).**

\* **Fase 1 — T1.10** (Policy RBAC generik) *sebagian*: otorisasi sudah jalan via **Gate per-modul** (`manage-users`, `manage-master-data`, `approve-peminjaman-ruangan`, `daftar-kelas-lab`, `manage-bidang-minat`) + `KelasLabPolicy`/`DosenPolicy`; Policy terpusat belum diseragamkan.

---

## 2. Fitur tambahan (di luar Fase 0–9 asli)

Dikerjakan atas permintaan, sudah masuk kode & dokumen:

| Fitur | Ringkasan | Status |
|---|---|---|
| **Delegasi Aslab** (khusus Admin) | `GET/POST/DELETE /api/aslab` — mahasiswa ↔ supervisor; halaman `/admin/aslab` | ✅ |
| **Profil 3-tab** | Akun / Data Pribadi / Data Akademik; `users.email_pribadi` (email cadangan, bukan login) + `dosen.credential`/`buku` (editable) | ✅ |
| **Kartu Kepala Lab** | `info_lab.dosen_id` → kartu identitas terstruktur (fallback ke konten bebas) | ✅ |
| **Dashboard Beranda** | ringkasan jadwal/kelas/kepala-lab/pengumuman/mitra + kartu profil per-role | ✅ |
| **Bidang Minat** | dipindah jadi tab di Data Master (panel terpisah dihapus) | ✅ |

---

## 3. Ringkasan teknis yang sudah ada (Fase 0–3)

**Tabel DB (migrasi aktif)**: `users` (+`email_pribadi`,`no_telp`), `dosen` (+`credential`,`buku`,`bidang_minat`), `mahasiswa`, `bidang_minat`/`dosen_bidang_minat`, `info_lab` (+`dosen_id`), `ruangan`, `mata_kuliah`, `peminjaman_ruangan`, `kelas_lab`, `kelas_lab_peserta` (+`status`,`disetujui_oleh`).

**Aturan bisnis kunci Fase 3**:
- Peminjaman ruangan **hanya Mahasiswa**; jam operasional **07.00–17.00 WIB**; validasi bentrok dua-arah (peminjaman titik-waktu ↔ kelas berulang) via `JadwalRuanganService`; approve = re-validasi bentrok.
- Kalender Informasi Jadwal Lab: peminjaman disetujui **awal minggu ke depan** (auto-refresh mingguan), dikelompokkan "Minggu ini / Mendatang".
- Kelas Lab: pendaftaran **butuh persetujuan** Dosen/Supervisor (`menunggu`→`disetujui`/`ditolak`); kuota memesan slot (menunggu+disetujui); **1 sesi per mata kuliah** + **tanpa bentrok**; Admin **tidak** boleh buka kelas.
- Mahasiswa batal hanya saat `menunggu`; Dosen/Supervisor **keluarkan** peserta (`DELETE …/pendaftaran/{peserta}`).

**Test**: **78 test backend lulus** (PHPUnit/sqlite). Frontend `vite build` hijau.

---

## 4. Konsistensi dokumen

| Dokumen | Sinkron s/d |
|---|---|
| `1_PRD.md` | Fase 0–3; peminjaman = Mahasiswa-only |
| `2_SRS.md` | RBAC + UC-01…UC-02a (UC-03…07 = rancangan Fase 4–9) |
| `3_SDD.md` | Skema DB + API Fase 0–3 + email_pribadi, credential/buku, `/api/aslab`, `info_lab.dosen_id` |
| `4_TASK_BREAKDOWN.md` | Fase 0–3 `[x]`; Fase 4–9 `[ ]` |

> Skema & endpoint **Fase 4–9 sudah didefinisikan** di SDD/SRS sebagai rancangan, **belum ada implementasi kode**.

---

## 5. Catatan lingkungan (untuk sesi berikutnya)

- **DB dev**: MySQL 8.4.3 via Laragon (start manual), user `root` tanpa password, database `simlab`. Test pakai sqlite in-memory (`RefreshDatabase`).
- **Build frontend**: `vite build` sering **OOM** — jalankan `NODE_OPTIONS=--max-old-space-size=4096` dan ulangi beberapa kali (biasanya berhasil).
- **Defender**: kadang **merusak file `node_modules`** (mis. `prosemirror-transform/package.json` jadi biner → build gagal "failed to resolve import"). Perbaiki dengan menulis ulang `package.json` paket tsb (contoh dari `prosemirror-state`).

---

## 6. Langkah berikutnya — Fase 4 (Peminjaman Perangkat)

Rujukan task: `4_TASK_BREAKDOWN.md` bagian **FASE 4** (T4.1–T4.16), skema SDD **3.9–3.11**, SRS **UC-03**.

Backend: migrasi `perangkat`/`peminjaman_perangkat`/`perpanjangan_peminjaman` + model, CRUD perangkat (Admin/Supervisor), ajukan/approve/reject peminjaman (Mahasiswa), perpanjangan (validasi tanggal kembali belum lewat; approve otomatis perbarui `tanggal_kembali_rencana`).
Frontend: daftar perangkat, form pengajuan, "Peminjaman Saya" + perpanjangan, panel approve/reject, panel kelola perangkat.
