# System Design Document (SDD) — Final

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Unit Terkait**: Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) — Prodi Informatika
**Dokumen Acuan**: `1_PRD_FINAL.md`, `2_SRS_FINAL.md`

> Dokumen final ini memaparkan **arsitektur sistem, skema basis data, dan struktur API** dari aplikasi yang telah selesai dibangun.

---

## 1. Arsitektur Sistem

```
┌─────────────────────┐         HTTP/JSON          ┌──────────────────────┐
│   Vue 3 SPA          │  ────────────────────────▶ │   Laravel 13 API      │
│   (src/frontend)      │  ◀──────────────────────── │   (src/backend)       │
│   Vite + Axios        │      Bearer Token           │   PHP 8.5             │
└─────────────────────┘      (Laravel Sanctum)       └──────────┬───────────┘
                                                                   │
                                                                   ▼
                                                          ┌─────────────────┐
                                                          │   MySQL          │
                                                          └─────────────────┘
```

- **Backend**: Laravel 13 sebagai REST API — seluruh response berbentuk JSON; tidak ada Blade view untuk halaman aplikasi (Blade hanya dipakai untuk render dokumen PDF laporan)
- **Frontend**: Vue 3 SPA (Composition API) terpisah penuh, dibangun dengan Vite, berkomunikasi ke backend lewat Axios
- **Autentikasi**: Laravel Sanctum (SPA token authentication) + Google OAuth 2.0 via Laravel Socialite, dibatasi domain `@unsil.ac.id` dan `@student.unsil.ac.id`
- **CORS**: Backend mengizinkan origin frontend secara eksplisit di `config/cors.php`, `supports_credentials` aktif untuk Sanctum SPA auth

---

## 2. Alur Autentikasi

1. User menekan **Login dengan Google** di frontend
2. Frontend redirect ke endpoint backend yang memulai alur Google OAuth (Socialite)
3. User memilih akun Google institusi
4. Google mengembalikan data user (email, nama, foto) ke backend
5. Backend memvalidasi domain email:
   - Jika bukan `@unsil.ac.id`/`@student.unsil.ac.id` → **tolak login**, pesan "Gunakan email institusi UNSIL"
   - Jika `@student.unsil.ac.id` → role = **Mahasiswa**
   - Jika `@unsil.ac.id` → role = **Dosen**
6. Backend mengecek apakah email sudah terdaftar di `users`:
   - **Belum ada** → buat baru:
     - Insert ke `users` dengan role sesuai domain
     - **Role Dosen** → insert entri `dosen`, di-link via `user_id`
     - **Role Mahasiswa** → insert entri `mahasiswa`, di-link via `user_id`. Kolom `npm` diisi otomatis dari local-part email (mis. `197006028@student.unsil.ac.id` → `npm = "197006028"`)
   - **Sudah ada** → lanjut login biasa
7. Backend membuat Sanctum token, mengembalikan token + data user (termasuk role) ke frontend
8. Frontend menyimpan token, menyertakan di header `Authorization: Bearer ...` untuk request berikutnya, dan mengarahkan user ke dashboard sesuai role

**Catatan implementasi**:
- Akun **Admin** dan **Supervisor** disiapkan lewat **Database Seeder**, bukan endpoint publik — agar role berkuasa tidak muncul lewat self-registration
- Login Google adalah cara pertama sebuah akun dibuat. Tidak ada endpoint registrasi manual email+password

### 2.1 Login Manual (Alternatif Setelah Akun Ada)

Selain Google OAuth, sistem menyediakan **login manual** (email + password) sebagai alternatif — bukan pengganti, dan bukan cara membuat akun baru.

**Alur set password pertama kali**:
1. User login lewat Google OAuth
2. Di halaman **Profil**, user mengisi "Atur Password Login" (cukup password baru + konfirmasi)
3. Backend menyimpan password (di-hash) ke kolom `password` pada `users`
4. Sejak itu, user bisa login lewat Google OAuth **atau** email + password

**Alur login manual**:
1. User membuka halaman Login → "Login dengan Email & Password"
2. Mengisi email + password
3. Backend mencari user berdasarkan email:
   - **Email tidak ditemukan** → tolak, pesan umum "Email atau password salah"
   - **Email ditemukan tapi `password` masih NULL** → tolak: *"Akun ini belum mengaktifkan login manual. Silakan login dengan Google UNSIL, lalu atur password di halaman Profil."*
   - **Email ditemukan dan `password` terisi** → validasi cocok → buat Sanctum token

---

## 3. Skema Database

### 3.1 `users`
Akun login semua role (dibedakan kolom `role`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `name` | varchar | Diambil dari profil Google saat registrasi |
| `email` | varchar, unique | Email institusi. **Immutable** |
| `email_pribadi` | varchar, nullable | Email cadangan/kontak (bukan untuk login) |
| `no_telp` | varchar(32), nullable | Nomor telepon, diisi lewat Edit Profil |
| `google_id` | varchar, nullable, unique | ID akun Google |
| `avatar` | varchar, nullable | URL foto profil (dari Google atau unggahan) |
| `role` | enum(`admin`,`supervisor`,`dosen`,`mahasiswa`) | Ditentukan otomatis dari domain email (kecuali admin/supervisor) |
| `password` | varchar, nullable | Hash password login manual. **NULL** default; terisi setelah user set sendiri |
| `email_verified_at` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamp | |

### 3.2 `dosen`
Profil publik dosen, dibuat otomatis saat user `@unsil.ac.id` registrasi pertama.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id`, unique | `on delete cascade` |
| `nidn` | varchar, nullable | |
| `jenis_kelamin` | varchar, nullable | `Laki-laki` / `Perempuan` |
| `jabatan_fungsional` | varchar, nullable | Mis. `Lektor`, `Lektor Kepala` |
| `tempat_lahir` | varchar, nullable | |
| `tanggal_lahir` | date, nullable | |
| `biografi` | text, nullable | |
| `credential` | text, nullable | Sertifikasi/keahlian dosen |
| `roadmap_riset` | text, nullable | Peta jalan riset pribadi (halaman Roadmap Penelitian Dosen) |
| `publikasi` | text, nullable | |
| `buku` | text, nullable | |
| `foto` | varchar, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Catatan**: `name`, `email`, `avatar` tidak diduplikasi di tabel ini — diambil lewat relasi ke `users`. **Bidang Minat** disimpan sebagai relasi many-to-many (3.2a). Endpoint dosen meng-eager-load relasi `user` & `bidangMinat`.

### 3.2a `bidang_minat` & `dosen_bidang_minat`
Master **Bidang Minat** — dikelola Admin/Supervisor, dipilih Dosen (boleh lebih dari satu) di Edit Profil.

**`bidang_minat`** (master): `id` (PK), `nama` (varchar, unique), timestamps.
**`dosen_bidang_minat`** (pivot): `dosen_id` (FK, cascade) + `bidang_minat_id` (FK, cascade) — PK komposit.

**Akses**: CRUD master via Gate `manage-bidang-minat` (Admin/Supervisor); read terbuka untuk semua role login. Pemilihan dosen disinkronkan lewat `PATCH /api/auth/profile` (`bidang_minat_ids[]`).

### 3.3 `mahasiswa`
Profil mahasiswa, dibuat otomatis saat user `@student.unsil.ac.id` registrasi pertama.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id`, unique | `on delete cascade` |
| `dosen_pembimbing_id` | bigint, FK → `dosen.id`, nullable | `on delete set null`; untuk validasi bimbingan |
| `npm` | varchar, unique | **Diisi otomatis** dari local-part email. **Immutable** |
| `prodi` | varchar, nullable | Diisi menyusul |
| `angkatan` | varchar(4) | **Diisi otomatis** dari 2 digit awal NPM + prefix "20" (string concatenation) |
| `foto` | varchar, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Aturan**: Update profil mahasiswa mengabaikan/menolak perubahan `npm` & `angkatan` (immutable, divalidasi di backend).

### 3.4 `ruangan`
Data master ruangan lab: `id` (PK), `nama_ruangan`, `kapasitas` (int, nullable — jumlah peminjaman paralel yang diizinkan pada jam sama; `NULL`/`0` = 1/eksklusif), `status` (enum `tersedia`/`dipakai`/`perbaikan`), timestamps.

### 3.5 `peminjaman_ruangan`
Pengajuan peminjaman ruangan oleh Mahasiswa.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `ruangan_id` | bigint, FK → `ruangan.id` | `on delete cascade` |
| `user_id` | bigint, FK → `users.id` | Pengaju (Mahasiswa), `on delete cascade` |
| `tanggal` | date | |
| `jam_mulai` | time | |
| `jam_selesai` | time | |
| `keperluan` | text | |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`,`kadaluarsa`) | Default `menunggu`. `kadaluarsa` = gugur otomatis saat approve karena slot penuh (dibedakan dari `ditolak` manual) |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | `on delete set null` |
| `created_at`, `updated_at` | timestamp | |

**Constraint (UC-02, berbasis kapasitas)**: ruangan boleh dipakai beberapa peminjaman `disetujui` pada jam tumpang tindih **selama jumlahnya belum mencapai `ruangan.kapasitas`** (1 peminjaman = 1 slot). Slot penuh/bentrok bila hitungan sudah mencapai kapasitas, atau ada jadwal `kelas_lab` aktif pada ruangan/hari/jam sama (Kelas Lab memblok ruangan penuh). Peminjaman hanya diizinkan bila `ruangan.status = 'tersedia'`. Jam wajib **07.00–17.00 WIB**. Validasi di Form Request (`JadwalRuanganService`) dan divalidasi ulang saat approve dalam transaksi ber-`lockForUpdate`; bila slot ternyata penuh, status otomatis `kadaluarsa` + notifikasi ke pengaju.

### 3.6 `mata_kuliah`
Data master mata kuliah — induk yang mengelompokkan sesi paralel Kelas Lab: `id` (PK), `kode_mk` (nullable, unique), `nama_mk`, `sks` (nullable), timestamps.

**Akses**: CRUD dikelola Admin/Supervisor. Dosen memilih dari daftar yang tersedia saat membuka Kelas Lab.

### 3.7 `kelas_lab`
Jadwal Kelas Lab/Praktikum — satu sesi terjadwal mingguan selama satu semester (terpisah dari `peminjaman_ruangan`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `mata_kuliah_id` | bigint, FK → `mata_kuliah.id` | `on delete cascade` |
| `dosen_id` | bigint, FK → `dosen.id` | Pemilik/pengampu kelas, `on delete cascade` |
| `ruangan_id` | bigint, FK → `ruangan.id` | `on delete cascade` |
| `dibuat_oleh` | bigint, FK → `users.id` | Dosen sendiri atau Supervisor, `on delete cascade` |
| `nama_sesi` | varchar | Label sesi paralel (mis. "Kelas A") |
| `hari` | enum(`senin`…`sabtu`) | Pola berulang mingguan |
| `jam_mulai` | time | |
| `jam_selesai` | time | |
| `tanggal_mulai_semester` | date | |
| `tanggal_selesai_semester` | date | |
| `kuota` | int | Maks. 30-40 (divalidasi Form Request) |
| `tautan_pengumpulan` | varchar(2048), nullable | Tautan tempat unggah dokumen laporan; **wajib diisi** lewat validasi |
| `created_at`, `updated_at` | timestamp | |

**Catatan**: Sesi paralel (Kelas A/B/C) = baris terpisah dengan `mata_kuliah_id` sama, kuota independen. Backend memvalidasi bentrok jadwal terhadap `peminjaman_ruangan` & sesama `kelas_lab` (ruangan + hari + rentang jam overlap, dalam rentang semester); ruangan harus `tersedia`. Jam wajib **07.00–17.00 WIB**.

### 3.8 `kelas_lab_peserta`
Pendaftaran mahasiswa sebagai peserta suatu sesi. **Butuh persetujuan** Dosen pengampu/Supervisor.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kelas_lab_id` | bigint, FK → `kelas_lab.id` | `on delete cascade` |
| `mahasiswa_id` | bigint, FK → `mahasiswa.id` | `on delete cascade` |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`) | Default `menunggu` |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | `on delete set null` |
| `created_at`, `updated_at` | timestamp | |

**Aturan (UC-02a)**:
- `(kelas_lab_id, mahasiswa_id)` **unique**. Baris `ditolak` boleh diajukan ulang (status kembali `menunggu`)
- **Kuota memesan slot**: `menunggu` + `disetujui` tidak boleh melebihi `kuota`
- **Satu sesi per mata kuliah**; boleh ambil mata kuliah berbeda selama tidak bentrok jadwal
- `sisa_kuota` = `kuota − (menunggu + disetujui)`
- Mahasiswa membatalkan hanya saat `menunggu`; setelah `disetujui`, hanya Dosen/Supervisor yang mengeluarkan peserta

### 3.9 `perangkat`
Data master perangkat lab: `id` (PK), `nama_perangkat`, `nomor_seri` (unique), `kategori` (nullable), `status` (enum `tersedia`/`dipinjam`/`perbaikan`), timestamps.

### 3.10 `peminjaman_perangkat`
Pengajuan peminjaman perangkat oleh Mahasiswa.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `perangkat_id` | bigint, FK → `perangkat.id` | `on delete cascade` |
| `user_id` | bigint, FK → `users.id` | Mahasiswa, `on delete cascade` |
| `tanggal_pinjam` | date | |
| `tanggal_kembali_rencana` | date | |
| `tanggal_kembali_aktual` | date, nullable | Diisi saat pengembalian dikonfirmasi |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`,`dikembalikan`) | |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | `on delete set null` |
| `created_at`, `updated_at` | timestamp | |

### 3.11 `perpanjangan_peminjaman`
Pengajuan perpanjangan waktu pinjam perangkat.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `peminjaman_perangkat_id` | bigint, FK → `peminjaman_perangkat.id` | `on delete cascade` |
| `tanggal_kembali_baru` | date | Usulan tanggal kembali baru |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`) | |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | `on delete set null` |
| `created_at`, `updated_at` | timestamp | |

**Aturan (UC-03)**: Insert ditolak bila `tanggal_kembali_rencana` induk sudah lewat. Saat perpanjangan `disetujui`, `tanggal_kembali_rencana` induk diperbarui otomatis (DB Transaction).

### 3.12 `tugas`
Pengumpulan tugas mahasiswa (tautan/URL hasil) untuk sebuah pertemuan pada sesi Kelas Lab yang diikutinya.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kelas_lab_id` | bigint, FK → `kelas_lab.id` | `on delete cascade`; ada index |
| `pertemuan` | tinyint unsigned | Pertemuan 1–16 (default 1) |
| `mahasiswa_id` | bigint, FK → `mahasiswa.id` | `on delete cascade` |
| `judul` | varchar | Judul tugas |
| `tautan` | varchar(2048) | URL hasil tugas, divalidasi `url` |
| `created_at`, `updated_at` | timestamp | `created_at` dibandingkan tepat/telat vs `deadline_pertemuan.deadline` |

**Aturan (UC-04)**:
- Hanya mahasiswa peserta **`disetujui`** pada kelas tujuan yang boleh mengirim
- **Satu tugas per (`kelas_lab_id`, `pertemuan`, `mahasiswa_id`)**
- Deadline tidak memblokir pengiriman; keterlambatan ditandai dengan membandingkan `created_at` (WIB) vs `deadline`
- Saat tugas dibuat, sistem mengirim notifikasi `pengajuan_masuk` ke **dosen pengampu + semua Supervisor** (transaksi sama)

### 3.12a `deadline_pertemuan`
Materi &/atau deadline pengumpulan tugas untuk satu pertemuan (1–16) sebuah Kelas Lab, ditetapkan Dosen pengampu/Supervisor/Admin.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kelas_lab_id` | bigint, FK → `kelas_lab.id` | `on delete cascade` |
| `pertemuan` | tinyint unsigned | 1–16; unique bersama `kelas_lab_id` |
| `materi` | varchar, nullable | Nama materi/silabus — boleh berdiri sendiri tanpa deadline |
| `deadline` | datetime, nullable | Tenggat (WIB). Pertemuan tanpa `deadline` **tidak dihitung sebagai tugas** |
| `created_at`, `updated_at` | timestamp | |

**Aturan**: Unique `(kelas_lab_id, pertemuan)` (upsert via `PUT`). Record valid bila minimal salah satu `materi`/`deadline` terisi; bila keduanya kosong, record dihapus.

### 3.13 `sertifikasi`
Katalog informasi sertifikasi eksternal (bukan transaksi pendaftaran): `id` (PK), `nama_sertifikasi`, `penyelenggara`, `jadwal` (nullable), `persyaratan` (text, nullable), `tautan_pendaftaran` (nullable), `created_by` (FK → `users.id`, nullable, `on delete set null` — pembuat entri, dasar kepemilikan Dosen via `SertifikasiPolicy`; entri lama/seeder `NULL`), timestamps. **Murni katalog informasi — satu-satunya relasi ke `users` adalah `created_by`.**

### 3.14 `portofolio`
Hasil riset/proyek/publikasi milik mahasiswa.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id` | Pemilik (Mahasiswa), `on delete cascade` |
| `judul` | varchar | |
| `deskripsi` | text, nullable | |
| `tautan` | varchar, nullable | |
| `tanggal` | date, nullable | |
| `created_at`, `updated_at` | timestamp | |

### 3.15 `info_lab`
Konten halaman informasi lab (Beranda, Visi-Misi, Profil Kepala Lab, Roadmap Lab tingkat KK) — dikelola Admin.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `tipe` | enum(`beranda`,`visi_misi`,`kepala_lab`,`roadmap_kk`) | Satu baris per tipe |
| `judul` | varchar, nullable | |
| `konten` | longtext | Rich text (HTML dari editor TipTap; Markdown legacy juga didukung) |
| `gambar` | varchar, nullable | |
| `dosen_id` | bigint, FK → `dosen.id`, nullable | Khusus tipe `kepala_lab`, `on delete set null` |
| `updated_by` | bigint, FK → `users.id`, nullable | Admin terakhir yang mengubah |
| `created_at`, `updated_at` | timestamp | |

**Profil Kepala Lab**: bila baris `kepala_lab` punya `dosen_id`, halaman publik dirender sebagai kartu identitas terstruktur dari profil dosen tertaut; bila kosong, jatuh ke konten bebas.

### 3.16 `notifikasi`
Notifikasi in-app. Dibuat otomatis oleh sistem sebagai efek samping aksi lain (dalam transaksi yang sama) atau lewat penjadwalan berkala (pengingat). Tidak ada endpoint pembuatan publik.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id` | Penerima, `on delete cascade` |
| `judul` | varchar | |
| `pesan` | text | |
| `tipe` | enum(`pengajuan_masuk`,`status_pengajuan`,`pendaftaran`,`pengingat`) | Menentukan ikon/warna. `pengingat` = tenggat tugas/pengembalian perangkat (terjadwal) |
| `referensi_id` | bigint unsigned, nullable | ID entitas pemicu untuk navigasi — **sengaja tanpa FK (by design)**: merujuk tabel berbeda-beda tergantung `tipe`, sehingga FK tunggal tidak mungkin; integritas dijaga di level aplikasi |
| `is_read` | boolean, default false | |
| `created_at`, `updated_at` | timestamp | Index komposit `(user_id, is_read)` |

**Aturan (UC-07)**: Insert dalam **DB transaction yang sama** dengan aksi pemicunya (rollback → notifikasi ikut batal). `GET /api/auth/me` menyertakan `unread_notifications_count`. Notifikasi `pengingat` **idempoten** (tidak menduplikasi pengingat sama).

### 3.17 Penjadwalan (Scheduler) & Pengingat
Dua command terjadwal (didaftarkan di `bootstrap/app.php`, dijalankan oleh `schedule:run`/`schedule:work`):

| Command | Jadwal | Fungsi |
|---|---|---|
| `pengingat:deadline` | tiap jam (`hourly`) | Kirim notifikasi `pengingat` ke mahasiswa peserta `disetujui` yang belum mengumpulkan untuk `deadline_pertemuan` yang sudah lewat; idempoten per (user, deadline) |
| `pengingat:pengembalian` | harian 07.00 (`dailyAt`) | Kirim notifikasi `pengingat` pengembalian perangkat yang jatuh tempo |

---

## 4. Diagram Relasi (ERD Ringkas)

```
users (1) ──── (1) dosen
users (1) ──── (1) mahasiswa
users (1) ──── (M) peminjaman_ruangan
users (1) ──── (M) peminjaman_perangkat
users (1) ──── (M) portofolio
users (1) ──── (M) notifikasi

dosen (1) ──── (M) mahasiswa     → mahasiswa bimbingan (via dosen_pembimbing_id)
dosen (M) ──── (M) bidang_minat  → via pivot dosen_bidang_minat
ruangan (1) ──── (M) peminjaman_ruangan
ruangan (1) ──── (M) kelas_lab
mata_kuliah (1) ──── (M) kelas_lab
dosen (1) ──── (M) kelas_lab
kelas_lab (1) ──── (M) kelas_lab_peserta
mahasiswa (1) ──── (M) kelas_lab_peserta
kelas_lab (1) ──── (M) tugas                → pengumpulan tugas per pertemuan
mahasiswa (1) ──── (M) tugas
kelas_lab (1) ──── (M) deadline_pertemuan   → materi/deadline per pertemuan (1–16)

perangkat (1) ──── (M) peminjaman_perangkat
peminjaman_perangkat (1) ──── (M) perpanjangan_peminjaman

sertifikasi   → created_by ke users (nullable) untuk kepemilikan entri Dosen; selain itu murni katalog
info_lab      → relasi updated_by ke users; opsional dosen_id (tipe kepala_lab)
notifikasi    → referensi_id lintas tabel (sengaja tanpa FK — by design); hanya user_id yang ber-FK
```

---

## 5. Struktur API (REST Endpoints)

Semua endpoint berprefix `/api`, dilindungi `auth:sanctum` kecuali ditandai **(publik)**. Otorisasi per role mengacu matriks RBAC di `2_SRS_FINAL.md` Bagian 1.

### 5.1 Autentikasi
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/auth/google/redirect` | **(publik)** Mulai alur Google OAuth |
| GET | `/api/auth/google/callback` | **(publik)** Callback Google, registrasi/login otomatis |
| POST | `/api/auth/login` | **(publik)** Login manual (email + password) |
| POST | `/api/auth/logout` | Hapus token Sanctum aktif |
| GET | `/api/auth/me` | Data user yang login; menyertakan `unread_notifications_count` |
| POST | `/api/auth/set-password` | Atur password pertama kali |
| PATCH | `/api/auth/change-password` | Ubah password (wajib password lama) |
| POST | `/api/auth/reset-password` | Atur ulang password tanpa password lama — khusus akun tertaut Google UNSIL |
| POST | `/api/auth/avatar` | Unggah/ganti foto profil (multipart; `jpeg/jpg/png/webp`, maks 2 MB) |
| PATCH | `/api/auth/profile` | Edit profil sendiri. Semua role: `name`, `no_telp`, `email_pribadi`. Dosen: `nidn`, `jabatan_fungsional`, `tempat_lahir`, `tanggal_lahir`, `bidang_minat_ids[]`, `biografi`, `credential`, `publikasi`, `buku`, `roadmap_riset`. Mahasiswa: `prodi`. `email`/`role`/`npm`/`angkatan` immutable |

### 5.2 User & Role (Admin only) + Delegasi Aslab
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/users` | List semua user (filter by role) |
| POST | `/api/users` | Buat user manual (Dosen/Supervisor/Admin) |
| PATCH | `/api/users/{id}` | Update data/role user |
| DELETE | `/api/users/{id}` | Hapus user |
| GET | `/api/aslab` | `{ kandidat: [mahasiswa], aslab: [supervisor dari mahasiswa] }` |
| POST | `/api/aslab/{user}` | Jadikan mahasiswa → Supervisor (profil mahasiswa dipertahankan) |
| DELETE | `/api/aslab/{user}` | Kembalikan Supervisor (dari mahasiswa) → Mahasiswa |

### 5.3 Dosen
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/dosen` | **(publik)** List dosen, eager load `user` & `bidangMinat` |
| GET | `/api/dosen/{id}` | **(publik)** Detail profil satu dosen |
| PATCH | `/api/dosen/{id}` | Update profil — pemilik atau Admin/Supervisor |

### 5.4 Bidang Minat (Master)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/bidang-minat` | List (semua role login) |
| POST/PATCH/DELETE | `/api/bidang-minat[/{id}]` | CUD via Gate `manage-bidang-minat` (Admin/Supervisor) |

### 5.5 Ruangan & Peminjaman Ruangan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET/POST/PATCH/DELETE | `/api/ruangan[/{id}]` | Read semua role login; CUD Admin/Supervisor. Hapus ditolak bila masih dirujuk peminjaman/kelas aktif |
| GET | `/api/peminjaman-ruangan` | List pengajuan (milik sendiri / semua untuk Admin/Supervisor) |
| GET | `/api/peminjaman-ruangan/kalender` | `kelas_lab` aktif + peminjaman `disetujui` dari awal minggu berjalan ke depan |
| POST | `/api/peminjaman-ruangan` | Ajukan (**Mahasiswa saja**) |
| PATCH | `/api/peminjaman-ruangan/{id}/approve` | Setujui (Admin/Supervisor) — validasi ulang bentrok/kapasitas & status ruangan dalam transaksi ber-lock; slot penuh → status otomatis `kadaluarsa` |
| PATCH | `/api/peminjaman-ruangan/{id}/reject` | Tolak (Admin/Supervisor) |
| DELETE | `/api/peminjaman-ruangan/{id}` | Batalkan/hapus — pemilik saat masih `menunggu`; Admin/Supervisor kapan saja |

### 5.6 Mata Kuliah (Data Master)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/mata-kuliah` | List (semua role) |
| POST/PATCH/DELETE | `/api/mata-kuliah[/{id}]` | CUD Admin/Supervisor |

### 5.7 Kelas Lab/Praktikum
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/kelas-lab` | List (semua role); filter `mata_kuliah_id` |
| GET | `/api/kelas-lab/{id}` | Detail satu sesi + sisa kuota — staf bebas; Mahasiswa hanya bila pendaftarannya `disetujui` (`KelasLabPolicy`) |
| POST | `/api/kelas-lab` | Buka kelas — **Dosen** (dirinya) atau **Admin/Supervisor** (wajib isi `dosen_id`) |
| PATCH | `/api/kelas-lab/{id}` | Update jadwal/kuota — **Admin/Supervisor** (semua) atau pemilik (`dosen_id`) |
| DELETE | `/api/kelas-lab/{id}` | Hapus kelas — **Admin/Supervisor** (semua) atau pemilik (`dosen_id`) |
| POST | `/api/kelas-lab/{id}/daftar` | Mahasiswa mendaftar — status `menunggu` |
| DELETE | `/api/kelas-lab/{id}/daftar` | Mahasiswa batal — hanya saat `menunggu` |
| GET | `/api/kelas-lab/{id}/peserta` | List peserta + status (pemilik/Supervisor/Admin) |
| GET | `/api/kelas-lab/pendaftaran` | List pendaftaran untuk persetujuan — Dosen (kelasnya) / Supervisor; filter `status` |
| PATCH | `/api/kelas-lab/pendaftaran/{peserta}/approve` | Setujui pendaftaran |
| PATCH | `/api/kelas-lab/pendaftaran/{peserta}/reject` | Tolak pendaftaran |
| DELETE | `/api/kelas-lab/pendaftaran/{peserta}` | Keluarkan peserta |
| GET | `/api/kelas-lab/rekap-tugas` | Rekap kepatuhan pengumpulan per kelas (status perhatian/berjalan/beres) — badge |

### 5.8 Perangkat, Peminjaman & Perpanjangan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET/POST/PATCH/DELETE | `/api/perangkat[/{id}]` | Read semua role login; CUD Admin/Supervisor. Hapus ditolak bila status ≠ `tersedia` atau ada peminjaman aktif |
| GET | `/api/peminjaman-perangkat` | List pengajuan (milik sendiri / semua) |
| POST | `/api/peminjaman-perangkat` | Ajukan (Mahasiswa) |
| DELETE | `/api/peminjaman-perangkat/{id}` | Batalkan sendiri saat `menunggu` / hapus (Admin/Supervisor) |
| PATCH | `/api/peminjaman-perangkat/{id}/approve` | Setujui (Admin/Supervisor) |
| PATCH | `/api/peminjaman-perangkat/{id}/reject` | Tolak (Admin/Supervisor) |
| PATCH | `/api/peminjaman-perangkat/{id}/kembalikan` | Konfirmasi pengembalian |
| POST | `/api/peminjaman-perangkat/{id}/perpanjangan` | Ajukan perpanjangan (Mahasiswa) |
| PATCH | `/api/perpanjangan/{id}/approve` | Setujui — otomatis perbarui tanggal kembali induk |
| PATCH | `/api/perpanjangan/{id}/reject` | Tolak |

### 5.9 Pengumpulan Tugas & Deadline Pertemuan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/tugas` | List — Mahasiswa → miliknya; Dosen → kelasnya; Admin/Supervisor → semua |
| POST | `/api/tugas` | Kirim (Mahasiswa peserta `disetujui`) — `kelas_lab_id`, `pertemuan` (1–16), `judul`, `tautan` |
| DELETE | `/api/tugas/{tugas}` | Hapus — pemilik atau Admin/Supervisor |
| GET | `/api/kelas-lab/{id}/deadline` | List materi/deadline pertemuan (semua role login) |
| PUT | `/api/kelas-lab/{id}/deadline/{pertemuan}` | Upsert materi &/atau deadline — Dosen pengampu/Supervisor/Admin. Kosong keduanya → record dihapus |
| DELETE | `/api/kelas-lab/{id}/deadline/{pertemuan}` | Hapus materi & deadline pertemuan |

### 5.10 Sertifikasi (Katalog)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/sertifikasi` | List katalog (semua role login) |
| POST | `/api/sertifikasi` | Tambah — Admin/Supervisor/Dosen (`created_by` diisi otomatis) — `SertifikasiPolicy` |
| PATCH/DELETE | `/api/sertifikasi/{id}` | Ubah/hapus — Admin/Supervisor (semua) atau Dosen pemilik (`created_by`) — `SertifikasiPolicy` |

### 5.11 Portofolio
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/portofolio` | List (semua, atau filter `user_id`) |
| POST | `/api/portofolio` | Tambah (Mahasiswa, milik sendiri) |
| PATCH | `/api/portofolio/{id}` | Update milik sendiri |
| DELETE | `/api/portofolio/{id}` | Hapus milik sendiri |

### 5.12 Informasi Lab
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/info-lab/{tipe}` | **(publik)** Ambil konten (beranda/visi_misi/kepala_lab/roadmap_kk) |
| PATCH | `/api/info-lab/{tipe}` | Update konten (Admin/Supervisor, Gate `manage-info-lab`) |
| POST | `/api/info-lab/upload` | Unggah lampiran konten (Admin/Supervisor) |

### 5.13 Laporan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/report?from=&to=` | Data rekap (Admin/Supervisor); default 30 hari terakhir |
| GET | `/api/report/pdf?from=&to=` | Unduh PDF rekap (Admin/Supervisor) |

**Struktur response `GET /api/report`** (field `data`):
- `periode` — `{ dari, sampai }`
- `peminjaman_ruangan` — `{ total_pengajuan, total_disetujui, total_ditolak, total_menunggu }`
- `peminjaman_perangkat` — `{ total_pengajuan, total_disetujui, total_ditolak, total_dikembalikan }`
- `tugas` — `{ total_terkumpul, total_mahasiswa_unik, total_kelas }` (peminjaman & tugas dihitung berdasarkan `created_at`)

### 5.14 Notifikasi In-App
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/notifikasi` | List milik user login, terbaru dulu; menyertakan `unread_count` |
| PATCH | `/api/notifikasi/read-all` | Tandai semua milik user dibaca |
| PATCH | `/api/notifikasi/{id}/read` | Tandai satu dibaca |
| DELETE | `/api/notifikasi/{id}` | Hapus satu milik sendiri |

**Aturan akses**: semua role mengakses notifikasi milik sendiri saja. Pembuatan dilakukan internal backend (bukan endpoint publik).

### 5.15 Rekap Tugas Kelas Lab
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/rekap-tugas` | Rekap JSON (ringkasan semua kelas + matriks detail per kelas) — Admin/Supervisor/Dosen |
| GET | `/api/rekap-tugas/pdf` | Unduh PDF (landscape) — Admin/Supervisor/Dosen |
| GET | `/api/rekap-tugas/excel` | Unduh Excel `.xlsx` (sheet Ringkasan + satu sheet per kelas) — Admin/Supervisor/Dosen |

**Aturan akses**: Gate `view-rekap-tugas` (Admin/Supervisor/Dosen). Dosen di-scope otomatis ke kelas `dosen_id`-nya; Admin/Supervisor semua kelas. Data dihitung on-request.

**Struktur response `GET /api/rekap-tugas`** (field `data`):
- `generated_at` — waktu rekap dibuat (WIB)
- `ringkasan[]` — per kelas: `{ kelas_lab_id, mata_kuliah, nama_sesi, dosen, hari, jam, peserta_disetujui, total_tugas, pertemuan_bertugas, pertemuan_berjalan, tunggakan, status ('perhatian'|'berjalan'|'beres'), deadline_terdekat }`
- `detail[]` — per kelas: `{ kelas_lab_id, …, pertemuan[], peserta[] }`
  - `pertemuan[]` — kolom matriks (hanya pertemuan berdeadline): `{ pertemuan, materi, deadline }`
  - `peserta[]` — baris matriks (peserta `disetujui`): `{ npm, nama, prodi, sel{ <pertemuan>: { status ('tepat'|'telat'|'belum'), judul, tautan } }, total_kumpul, telat }`

---

## 6. Format Response API (Konvensi)

**Sukses**:
```json
{ "data": { ... }, "message": "Berhasil mengambil data" }
```
**Error validasi (HTTP 422)**:
```json
{ "message": "Data tidak valid", "errors": { "field_name": ["Pesan error"] } }
```
**Error otorisasi (HTTP 403)**:
```json
{ "message": "Anda tidak memiliki akses untuk tindakan ini" }
```

---

## 7. Lingkup di Luar Dokumen Ini
- Visi produk & alur pengguna → `1_PRD_FINAL.md`
- Aturan validasi & RBAC rinci → `2_SRS_FINAL.md`
- Daftar fitur/modul final → `4_RINGKASAN_FITUR.md`
