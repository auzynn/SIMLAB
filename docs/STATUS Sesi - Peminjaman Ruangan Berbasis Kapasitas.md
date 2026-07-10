# STATUS Serah-Terima Sesi — Peminjaman Ruangan Berbasis Kapasitas (Berbagi Ruangan)

**Produk**: SIM Lab. Riset KK JKF · **Branch**: `main` · **Per**: 2026-07-10
**Acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`, `docs/final/*`

> **Konteks besar**: Seluruh **Fase 0–9 sudah tuntas**. Sesi ini **bukan fase baru** — pengayaan modul **Peminjaman Ruangan (UC-02)**. Berawal dari pertanyaan: *"Jika beberapa mahasiswa meminjam ruangan yang sama, bukankah bisa berbagi karena banyak komputer?"* Ternyata aplikasi memperlakukan ruangan **eksklusif per slot** (`kapasitas` disimpan tapi tak dipakai di logika bentrok). Sesi ini mengubahnya menjadi **berbasis kapasitas** + penanganan konkurensi & status yang benar.
>
> **Semua verifikasi hijau**: backend **202/202 test lulus** (486 assertions). Migrasi MySQL dev **sudah dijalankan**. **Belum ada commit** — perubahan ada di working tree.

---

## 1. Empat perubahan inti (bertahap)

| # | Perubahan | Ringkas |
|---|---|---|
| 1 | **Berbagi ruangan berbasis kapasitas** | `1 peminjaman disetujui = 1 kursi`. Slot penuh hanya bila jumlah peminjaman disetujui yang overlap `>= kapasitas`. |
| 2 | **Auto-gugur saat approve** | Approve yang kalah slot tidak dibiarkan `menunggu` — ditandai gugur + notifikasi ke pemohon; UI antrian auto-refresh. |
| 3 | **Lock anti-race (TOCTOU)** | `approve()` mengunci baris ruangan lalu cek+update dalam satu transaksi → dua approver tak bisa melebihi kuota. |
| 4 | **Status `ditolak` vs `kadaluarsa`** | `ditolak` = penolakan manual approver; `kadaluarsa` = otomatis gugur karena slot penuh. |

**Keputusan penting (dari sesi tanya-jawab):**
- **Model kuota** = `1 peminjaman = 1 kursi` (bukan input jumlah komputer per pengajuan). Paling sederhana, tanpa field baru.
- **Kelas Lab tetap memblok ruangan PENUH** (praktikum tak dibagi peminjaman lain). Hanya sesama peminjaman mahasiswa yang berbagi kapasitas.
- `kapasitas` `null`/`0` → diperlakukan **1 (eksklusif)** agar aman & backward-compatible untuk data lama.
- Pembedaan `kadaluarsa` dipilih (bukan sekadar `ditolak`) agar: pesan ke mahasiswa jelas ("kalah cepat, bukan ditolak subjektif"), antrian approver bersih, dan data operasional bisa membedakan "pengajuan buruk" vs "ruangan kurang".

---

## 2. Perubahan Backend (`src/backend`)

### Logika inti — `app/Services/JadwalRuanganService.php`
- `peminjamanBentrok()` ditulis ulang:
  1. **Kelas Lab** overlap → tetap `return true` (blok penuh).
  2. **Peminjaman**: `->count()` peminjaman `disetujui` yang overlap; `kapasitas = max(1, ruangan.kapasitas ?? 1)`; `return $terpakai >= $kapasitas`.
- `kelasBentrok()` **tidak diubah** (Kelas Lab tetap butuh ruangan eksklusif).
- Komentar mencatat: hitungan bersifat **konservatif** (jumlah overlap, bukan konkurensi maksimum sweep-line) — bisa sedikit lebih ketat, tapi **tak pernah over-booking**.

### Controller — `app/Http/Controllers/PeminjamanRuanganController.php`
- `approve()` **direstrukturisasi**: `Ruangan::whereKey(...)->lockForUpdate()->first()` + cek bentrok + update, semua dalam **satu `DB::transaction`**. Closure mengembalikan boolean `kadaluarsa` untuk menentukan respons.
- Saat penuh/bentrok: status → `kadaluarsa`, `disetujui_oleh` diisi, notifikasi khusus ke pemohon, balas **HTTP 422**.
- Import `App\Models\Ruangan` ditambah.

### Validasi — `app/Http/Requests/StorePeminjamanRuanganRequest.php`
- Pesan bentrok disesuaikan: *"Kuota ruangan pada slot ini sudah penuh atau bentrok dengan Kelas Lab…"*.

### Migrasi — enum status `+kadaluarsa`
- `..._create_peminjaman_ruangan_table.php`: enum diperluas `['menunggu','disetujui','ditolak','kadaluarsa']` (agar fresh install & test SQLite memuat nilai di CHECK constraint).
- **Migrasi baru** `2026_07_10_000002_add_kadaluarsa_to_peminjaman_ruangan_status.php`: `ALTER TABLE ... MODIFY ENUM(...)` khusus MySQL (sqlite dilewati). Mengikuti pola `2026_07_07_000001_add_pengingat_to_notifikasi_tipe.php`. **Sudah di-`migrate`** (kolom MySQL terverifikasi memuat `kadaluarsa`).

---

## 3. Perubahan Frontend (`src/frontend/app-labriset/src`)

- `views/jadwal-lab.vue`: dropdown ruangan menampilkan `— {kapasitas} komputer` + hint bahwa ruangan bisa dipakai beberapa peminjam bersamaan.
- `views/persetujuan-peminjaman.vue`: `roomAksi()` memanggil `loadRoom()` juga di blok `catch` → antrian approver refresh otomatis setelah aksi gagal (pengajuan yang jadi `kadaluarsa` hilang dari daftar "menunggu").
- `utils/format.js`: `statusLabel()` +`kadaluarsa: 'Kadaluarsa'` (util shared → label konsisten di semua view).
- `views/peminjaman-saya.vue`: CSS badge `.status-kadaluarsa` (amber `#fdecc8`/`#6c5400`, dibedakan dari `.status-ditolak` merah).

---

## 4. Test backend (`src/backend/tests/Feature/PeminjamanRuanganTest.php`)

- `test_pengajuan_bentrok_dengan_peminjaman_disetujui_ditolak` **diganti** → `test_peminjaman_overlap_diterima_selama_kapasitas_tersisa` (kini `assertCreated`, perilaku berbagi).
- +`test_pengajuan_ditolak_saat_kapasitas_penuh` (kapasitas 2 terisi 2 → pengajuan ke-3 di-store 422).
- +`test_kapasitas_null_diperlakukan_eksklusif`.
- `test_approve_menjalankan_ulang_validasi_bentrok`: assert status akhir kini **`kadaluarsa`** (dulu `menunggu`).
- +`test_approve_saat_kuota_penuh_otomatis_kadaluarsa` (422 + status `kadaluarsa` + notifikasi terkirim).
- +`test_reject_manual_bernilai_ditolak_bukan_kadaluarsa` (mengunci pembedaan dua status).

---

## 5. Verifikasi (semua hijau)

1. `php artisan test --filter=PeminjamanRuanganTest` → **17/17 lulus**.
2. **Suite penuh backend: 202/202 lulus** (486 assertions).
3. `php artisan migrate --force` (MySQL dev) → migrasi enum DONE; `SHOW COLUMNS` memverifikasi `enum('menunggu','disetujui','ditolak','kadaluarsa')`.

> **Isu lingkungan (bukan bug kode):** ESLint frontend gagal jalan karena `node_modules/eslint` korup (pola Defender — lihat memory `node-modules-defender-corruption`). Diagnostik IDE `Undefined type Illuminate\...` = language server tak mengindeks `vendor/`, bukan error nyata. **Frontend belum di-`vite build` sesi ini** — perubahan Vue kecil (dropdown, badge, auto-refresh); disarankan build + smoke test per role sebelum rilis.

---

## 6. Alur end-to-end (hasil akhir)

- **Mahasiswa A & B** meminjam ruangan sama, jam overlap → **keduanya bisa** selama kursi tersisa (`kuota` belum habis).
- **Approver** menyetujui saat slot sudah penuh → pengajuan otomatis **`kadaluarsa`**, approver dapat pesan "kuota penuh", antrian refresh, pengajuan hilang dari "menunggu".
- **Mahasiswa** yang kalah melihat status `Kadaluarsa` + notifikasi → bebas mengajukan ulang di waktu/tanggal lain.
- **Dua approver bersamaan** untuk ruangan sama → di-serialkan oleh `lockForUpdate`, kuota tak terlampaui.

---

## 7. Ringkas: yang MASIH konsisten (tidak berubah)

- Kelas Lab/Praktikum tetap memblok ruangan **penuh** (tidak dibagi).
- Hanya **Mahasiswa** yang mengajukan; approve/reject tetap Admin/Supervisor (Gate `approve-peminjaman-ruangan`).
- Alur "ruangan tidak tersedia" (perbaikan) tetap 422 tanpa mengubah status pengajuan.
- Tidak ada perubahan pada modul Perangkat, Kelas Lab (selain interaksi bentrok yang memang sudah ada), Tugas, Portofolio, Notifikasi, Report.

---

## 8. Tindak lanjut opsional (belum dikerjakan)

- **Presisi sweep-line** untuk kapasitas (hitung konkurensi maksimum sesungguhnya) — hanya jika over-strict jadi masalah nyata.
- **Kolom `alasan`** bila ingin catatan teks bebas per penolakan (saat ini pembedaan sudah cukup via status `ditolak`/`kadaluarsa`).
- **`vite build` + smoke test** frontend per role.
- **Commit** perubahan working tree.
