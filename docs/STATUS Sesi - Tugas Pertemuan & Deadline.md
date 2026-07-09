# STATUS Serah-Terima Sesi — Modul Tugas: Pertemuan, Deadline & Materi

**Produk**: SIM Lab. Riset KK JKF · **Branch**: `main` · **Per**: 2026-07-08
**Acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`, `4_TASK_BREAKDOWN.md`, `docs/STATUS Update Fase 8-9.md`

> **Konteks besar**: Seluruh **Fase 0–9 sudah tuntas** (149/150 task — lihat `STATUS Update Fase 8-9.md`). Modul **Presensi (Fase 5) sudah diganti** menjadi modul **Pengumpulan Tugas** (`tugas`). Sesi ini **bukan fase baru** — semuanya **enhancement UX + fitur** di atas modul Tugas/Kelas Lab yang sudah ada.
>
> **Semua verifikasi hijau**: backend **185/185 test lulus**, `vite build` sukses. **Belum ada commit** — 92 file ter-modifikasi/untracked di working tree.

---

## 1. Apa yang dikerjakan sesi ini (kronologis, sudah selesai semua)

1. **Halaman Detail Kelas Lab** (`views/detail-kelas-lab.vue`, route `/kelaslab/:id/detail`) — detail sesi + daftar tugas. Link "Lihat Detail Kelas Lab" dari baris katalog (`kelas-lab.vue`) untuk semua role.
2. **16 Pertemuan** untuk tugas: kolom `pertemuan` (1–16) di tabel `tugas`; dropdown Pertemuan di form Kirim Tugas; 1 tugas per pertemuan per mahasiswa.
3. **Tombol "Kembali ke Kelas Lab"** di halaman Pengumpulan Tugas (semua role).
4. **Halaman Detail Pertemuan** (`views/detail-pertemuan.vue`, route `/kelaslab/:id/pertemuan/:pertemuan`, reviewer) — daftar mahasiswa Sudah/Belum mengumpulkan.
5. **Deadline per pertemuan** diatur manual Dosen/Supervisor/Admin (tabel `deadline_pertemuan`). Aturan **"tanpa deadline = tidak ada tugas"**.
6. **Peringatan keterlambatan**: badge & teks merah bila lewat deadline (form tugas, accordion, detail pertemuan). Util `sudahLewatDeadline`, `dikirimTerlambat`.
7. **Notifikasi in-app** ke mahasiswa saat melewati deadline (`PengingatDeadlineService` + command `pengingat:deadline` + hook lazy di `NotifikasiController@index`).
8. **Beranda kartu ketiga adaptif**: Mahasiswa → *Informasi Tugas* (tugas belum dikumpulkan); Dosen/Supervisor/Admin → *Informasi Pemberian Tugas*; Tamu → *Kepala Lab*. Ada placeholder "Memuat" agar tak berkedip. Daftar bisa **scroll**.
9. **Rekap kepatuhan (Opsi B)**: badge **"Perlu perhatian · N belum" / "Beres"** di Beranda & katalog `/kelaslab`. Endpoint `GET /api/kelas-lab/rekap-tugas`.
10. **Progres A+B** di kartu Beranda peninjau: `X/16 diberi tugas` (A) + `Pertemuan berjalan N/16` (B, dari jadwal mingguan) + bar progres.
11. **Nama Materi per pertemuan** (berdiri sendiri, bisa tanpa deadline): kolom `materi`, `deadline` dibuat nullable. Ditampilkan di form Kirim Tugas, Detail Pertemuan, accordion Detail Kelas. Diatur bareng deadline oleh reviewer.
12. Berbagai perbaikan teks/jarak UI + format nama file tugas **`NamaTugas_NPM_Nama`** (tanpa contoh data asli).
13. **Rapikan kartu "Informasi Pemberian Tugas" di Beranda** (`views/home-page.vue`): **(a)** hapus bar progres pertemuan berjalan (markup `.prog-track/.prog-fill` + CSS-nya) karena kartu terasa penuh & bar memakan tempat — angka `Pertemuan berjalan N/16` tetap sebagai teks; **(b)** badge "Perlu perhatian" tidak lagi memakai `.badge-late` (merah solid, terlalu mencolok) — dibuat class baru `.badge-perhatian` bergaya soft amber (`#8a5a00` di `#fdf0d5`, `0.78em`), senada dengan `.badge-beres` yang juga diturunkan ke `0.78em`. Merah solid kini khusus kondisi kritis "Terlambat".
14. **Status kepatuhan 3-tingkat (ganti badge "Beres" yang menyesatkan)**: dulu badge hanya `perlu_perhatian`(merah)/`Beres`(hijau), dan "Beres" muncul walau **deadline belum jatuh tempo & belum ada yang kirim** (karena tunggakan hanya dihitung dari deadline yang sudah lewat). Ditambah status **"Berjalan"** (netral biru). Backend `KelasLabController@rekapTugas` kini mengembalikan field baru **`status`** = `'perhatian'` (ada tunggakan jatuh tempo) / `'berjalan'` (tak ada tunggakan tapi masih ada deadline mendatang) / `'beres'` (semua deadline lewat & tuntas). Field lama `perlu_perhatian` tetap ada (kompatibilitas). Frontend `home-page.vue` & `kelas-lab.vue` pakai `status` untuk memilih badge (+class `.badge-berjalan`/`.pantau-badge.berjalan` warna `#3a5a8c`/`#e8eef7`); di katalog badge tetap digerbang `k.tugas_count` agar kelas tanpa tugas tak menampilkan "Beres". Test `RekapTugasTest` diperbarui (assert `status`, +`test_deadline_belum_lewat_berstatus_berjalan`). Suite backend **185/185** tetap lulus.
15. **Tombol "Kembali" di halaman Peserta Kelas Lab** (`views/peserta-kelas-lab.vue`): dulu `router-link` hardcoded ke `/kelaslab/kelola`. Karena halaman ini dijangkau dari **2 halaman** (Kelola Kelas Lab & Detail Kelas Lab), diubah jadi tombol `@click="kembali"` → `router.back()` (fallback `/kelaslab/kelola` bila tak ada history, mis. buka via URL langsung). Label jadi "← Kembali".
16. **Perjelas tanggal di kartu "Jadwal Hari Ini"** (`views/home-page.vue`): `.card-date` dulu abu pudar kecil (`0.8em`, `#9aa0a6`) → dibuat pil navy (`0.82em`, `600`, teks `--bs-navy` di `#eef1f7`) agar tanggal lebih terbaca.
17. **Pengelompokan tabel "Kelola Kelas Lab" per mata kuliah** (`views/kelola-kelas-lab.vue`): dulu baris tak terurut sehingga mata kuliah sama (mis. "Praktikum Jaringan Komputer" Kelas A & B) berpencar → sulit dibaca. Diterapkan **Bentuk B**: baris tetap dikelompokkan di bawah **baris judul mata kuliah berlatar abu** (`.grup-head`, border-kiri navy) + hitungan `· N kelas`; sesi menjorok (`.sesi-cell`). Urutan: **Nama MK A→Z, lalu Hari (Senin→Sabtu) & Jam mulai** (computed `grupTampil` + peta `URUT_HARI`). Kolom **"Sesi" dihapus** (nama kelas pindah ke kolom pertama "Mata Kuliah / Kelas"); filter `sesi` ikut dibuang (kolom tabel jadi 5). Filter mk/jadwal/ruangan tetap jalan (grouping dihitung dari hasil filter).

---

## 2. Perubahan Backend (`src/backend`)

### Migrasi baru (semua sudah di-`migrate`)
- `2026_07_07_000002_add_pertemuan_to_tugas_table` — `tugas.pertemuan` (unsignedTinyInteger 1–16, default 1).
- `2026_07_07_000003_create_deadline_pertemuan_table` — `id, kelas_lab_id (FK cascade), pertemuan, deadline, timestamps`, unique `(kelas_lab_id, pertemuan)`.
- `2026_07_07_000004_add_materi_to_deadline_pertemuan_table` — tambah `materi` (nullable), ubah `deadline` jadi **nullable**.

### Model
- `Tugas`: `pertemuan` di fillable + cast int.
- `KelasLab`: relasi `deadlinePertemuan(): HasMany`.
- `DeadlinePertemuan`: fillable `kelas_lab_id, pertemuan, materi, deadline`; cast `deadline => datetime`.

### Controller / Service
- **`DeadlinePertemuanController`**: `index` (semua role login), `upsert` (PUT — materi &/atau deadline; keduanya kosong → hapus record), `destroy`. Otorisasi: Dosen pengampu / Supervisor / Admin.
- **`KelasLabController`**:
  - `index`: `tugas_count` = `withCount deadlinePertemuan whereNotNull('deadline')`.
  - `rekapTugas` (baru): return per kelas `{ kelas_lab_id, total_tugas, pertemuan_bertugas (A), pertemuan_berjalan (B), peserta_disetujui, tunggakan, perlu_perhatian, deadline_terdekat }`. Scope: Dosen→kelasnya, Supervisor/Admin→semua, lainnya→kosong. "bertugas"/tunggakan hanya untuk `deadline != null`.
- **`TugasController@store`**: simpan `pertemuan`.
- **`StoreTugasRequest`**: `pertemuan` required 1–16 + tolak duplikat (kelas, pertemuan, mahasiswa).
- **`PengingatDeadlineService`** (baru) + **`app/Console/Commands/KirimPengingatDeadline.php`** (`pengingat:deadline`, dijadwalkan `hourly` di `bootstrap/app.php`). Notifikasi tipe `pengingat` ke mahasiswa peserta disetujui yang belum kumpul untuk deadline lewat; idempotent per (user, deadline). Hook lazy juga di `NotifikasiController@index`.

### Routes (`routes/api.php`, dalam grup `auth:sanctum`)
- `GET /api/kelas-lab/rekap-tugas` (sebelum apiResource `kelas-lab`).
- `GET /api/kelas-lab/{kelasLab}/deadline`, `PUT .../deadline/{pertemuan}`, `DELETE .../deadline/{pertemuan}`.

### Test (Feature) — semua lulus (total suite 185)
`TugasTest` (+pertemuan), `DeadlinePertemuanTest` (+materi tanpa deadline / bersamaan / kosong→hapus), `RekapTugasTest` (perlu-perhatian/beres/progres A-B/materi tak terhitung/scope), `PengingatDeadlineTest`.

---

## 3. Perubahan Frontend (`src/frontend/app-labriset`)

### View baru
- `views/detail-kelas-lab.vue` — detail sesi; accordion 16 pertemuan (mahasiswa: status kirim + materi + deadline; reviewer: form Materi+Deadline, tombol Detail Pertemuan, tombol Daftar Peserta). Kartu "Tugas Masuk" + "Unggah Dokumen Laporan" (+format nama file).
- `views/detail-pertemuan.vue` — reviewer; materi + deadline + tabel Sudah/Belum (badge terlambat).

### View/berkas diubah
- `router/index.js`: route `/kelaslab/:id/detail`, `/kelaslab/:id/pertemuan/:pertemuan`; `/kelaslab/:id/peserta` roles + `admin`.
- `services/kelas-lab.js`: `deadlineList`, `setDeadline(id, pertemuan, {materi, deadline})`, `removeDeadline`, `rekapTugas`.
- `views/kelas-lab.vue`: link Detail + badge `tugas_count` (Opsi A) + badge Perlu perhatian/Beres (Opsi B); baris 3-kolom (info · tengah note/kuota · kanan detail).
- `views/tugas.vue`: dropdown Pertemuan, hint deadline (merah bila lewat), materi pertemuan terpilih, format `NamaTugas_NPM_Nama`, tombol Kembali.
- `views/home-page.vue`: kartu ketiga adaptif + placeholder + scroll + badge Opsi B + progres A/B (teks `bertugas/16` & `berjalan/16`). Loader `muatTugasBelum` (mhs) & `muatPemberianTugas` (reviewer, pakai `rekapTugas`). **Bar progres pertemuan berjalan sudah dihapus** (kartu terlalu penuh); badge "Perlu perhatian" pakai `.badge-perhatian` soft amber (bukan `.badge-late` merah).
- `utils/format.js`: `formatDeadline`, `toDatetimeLocal`, `sudahLewatDeadline`, `dikirimTerlambat`.

---

## 4. Aturan bisnis kunci (mudah lupa)
- **"Tanpa deadline = tidak ada tugas"**: status Sudah/Belum & tunggakan hanya untuk pertemuan yang **punya deadline**. Materi-saja **tidak** dihitung tugas.
- **Materi berdiri sendiri**: boleh ada tanpa deadline (silabus). Record dihapus bila materi & deadline dua-duanya kosong.
- **Deadline manual** oleh reviewer (bukan otomatis dari jadwal). **Hanya tampil**, tidak memblokir pengiriman terlambat (mahasiswa tetap bisa kirim, ditandai "Terlambat").
- **Waktu**: deadline disimpan wall-clock **WIB** (app tz UTC); perbandingan front-end asумsi browser = WIB (konsisten seluruh app).
- **Progres B** (pertemuan berjalan): `floor(minggu sejak tanggal_mulai_semester)+1`, clamp 0–16; libur tidak digeser.

---

## 5. Lingkungan (penting)
- **DB dev**: MySQL 8.4.3 via Laragon (start manual), `root` tanpa password, db `simlab`. Test pakai sqlite in-memory.
- **Memori ketat**: `php artisan test` & `vite build` bisa gagal OOM → **ulangi**; build pakai `NODE_OPTIONS=--max-old-space-size=6144`.
- **Defender** kadang merusak file `vendor`/`node_modules` (jadi biner). Sesi ini `vendor/laravel/framework/.../BuildsQueries.php` sempat korup → dipulihkan user via `composer reinstall laravel/framework`. Bila test error "unexpected token"/file biner, curigai ini.

## 6. Belum dikerjakan / kandidat lanjutan
- **Belum commit** (92 file). Semua terverifikasi hijau; tinggal review & commit bila diinginkan.
- Opsi: blokir pengiriman setelah deadline (kini hanya menandai). Perlu tambahan validasi di `StoreTugasRequest` + test.
- Opsi: sembunyikan kelas "Beres" di Beranda (user memilih **tetap tampilkan semua** — jangan diubah tanpa diminta).
- T1.10 (Policy RBAC terpusat) masih via Gate per-modul (fungsional).

## 7. Verifikasi ulang (perintah)
```
cd "src/backend" && php artisan migrate && php artisan test
cd "src/frontend/app-labriset" && NODE_OPTIONS=--max-old-space-size=6144 npx vite build
```
