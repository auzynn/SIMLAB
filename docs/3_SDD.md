# 3. System Design Document (SDD)

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Unit Terkait**: Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF)
**Versi Dokumen**: 1.0
**Dokumen Acuan**: `1_PRD.md`, `2_SRS.md`

> Dokumen ini adalah **sumber kebenaran** untuk skema database, struktur API, dan arsitektur sistem. AI Agent **wajib** merujuk dokumen ini sebelum membuat migration, model, atau route — lihat `.clinerules/agent.md`. AI Agent **dilarang** mengasumsikan struktur data di luar yang didefinisikan di sini.

---

## 1. Arsitektur Sistem

```
┌─────────────────────┐         HTTP/JSON          ┌──────────────────────┐
│   Vue 3 SPA          │  ────────────────────────▶ │   Laravel 13.16 API   │
│   (src/frontend)      │  ◀──────────────────────── │   (src/backend)       │
│   Vite + Axios        │      Bearer Token           │   PHP 8.5.7            │
└─────────────────────┘      (Laravel Sanctum)       └──────────┬───────────┘
                                                                   │
                                                                   ▼
                                                          ┌─────────────────┐
                                                          │   MySQL          │
                                                          └─────────────────┘
```

- **Backend**: Laravel 13.16 sebagai REST API murni — seluruh response berbentuk JSON, tidak ada Blade view untuk halaman aplikasi
- **Frontend**: Vue 3 SPA (Composition API) terpisah penuh, dibangun dengan Vite, berkomunikasi ke backend lewat Axios
- **Autentikasi**: Laravel Sanctum (SPA token authentication) + Google OAuth 2.0 via Laravel Socialite, dibatasi domain `@unsil.ac.id` dan `@student.unsil.ac.id`
- **CORS**: Backend mengizinkan origin frontend secara eksplisit di `config/cors.php`, `supports_credentials` aktif untuk Sanctum SPA auth

---

## 2. Alur Autentikasi (Detail)

Ini bagian paling kritis karena menentukan bagaimana role ditentukan secara otomatis.

1. User menekan tombol **Login dengan Google** di frontend
2. Frontend redirect ke endpoint backend yang memulai alur Google OAuth (Socialite)
3. User memilih akun Google institusi miliknya
4. Google mengembalikan data user (email, nama, foto) ke backend
5. Backend memvalidasi domain email:
   - Jika bukan `@unsil.ac.id` atau `@student.unsil.ac.id` → **tolak login**, kembalikan error "Gunakan email institusi UNSIL"
   - Jika `@student.unsil.ac.id` → role = **Mahasiswa**
   - Jika `@unsil.ac.id` → role = **Dosen**
6. Backend mengecek apakah email sudah pernah terdaftar di `users`:
   - **Belum ada** → buat baru:
     - Insert ke `users` dengan role sesuai domain
     - **Jika role Dosen** → sekaligus insert entri baru ke tabel `dosen`, di-link via `user_id` (lihat Bagian 3.2)
     - **Jika role Mahasiswa** → sekaligus insert entri baru ke tabel `mahasiswa`, di-link via `user_id`. Kolom `nim` diisi otomatis dengan mengekstrak local-part dari email (bagian sebelum `@`), mis. email `197006028@student.unsil.ac.id` → `nim = "197006028"` (lihat Bagian 3.3)
   - **Sudah ada** → lanjut ke langkah 7 (login biasa)
7. Backend membuat Sanctum token, mengembalikan token + data user (termasuk role) ke frontend
8. Frontend menyimpan token, menyertakan di header `Authorization: Bearer ...` untuk semua request API berikutnya, dan mengarahkan user ke dashboard sesuai role

**Catatan implementasi penting**:
- Akun **Admin** dan **Supervisor** **tidak pernah dibuat lewat alur ini**. Keduanya dibuat manual saat development lewat **Database Seeder** (`UserSeeder`), tidak ada endpoint publik untuk membuatnya. Ini sengaja agar role berkuasa tidak bisa muncul lewat celah self-registration.
- Login Google **wajib** menjadi cara pertama kali sebuah akun dibuat. Tidak ada endpoint registrasi manual (`register` dengan email+password) — akun **hanya** lahir lewat alur Google OAuth di atas.

### 2.1 Login Manual (Alternatif Setelah Akun Ada)

Selain Google OAuth, sistem juga menyediakan **login manual** (email + password) sebagai alternatif — **bukan pengganti**, dan **bukan cara untuk membuat akun baru**.

**Alur set password pertama kali**:
1. User login lewat Google OAuth (seperti biasa)
2. Di halaman **Profil**, user mengisi form "Atur Password Login" — cukup `password baru` + `konfirmasi password` (tidak perlu password lama, karena memang belum pernah ada)
3. Backend menyimpan password (di-hash) ke kolom `password` pada `users`
4. Sejak saat ini, user bisa login lewat dua cara: Google OAuth, **atau** email + password manual

**Alur ganti password (setelah pernah di-set)**:
1. User membuka halaman Profil → "Ubah Password"
2. Sistem meminta `password lama` + `password baru` + `konfirmasi password baru`
3. Backend memvalidasi `password lama` cocok sebelum mengizinkan perubahan

**Alur login manual**:
1. User membuka halaman Login → memilih tab/opsi "Login dengan Email & Password"
2. User mengisi email + password
3. Backend mencari user berdasarkan email:
   - **Email tidak ditemukan** → tolak, pesan umum "Email atau password salah" (tidak membocorkan apakah email terdaftar)
   - **Email ditemukan tapi kolom `password` masih NULL** (belum pernah di-set) → tolak dengan pesan eksplisit: *"Akun ini belum mengaktifkan login manual. Silakan login dengan Google UNSIL, lalu atur password di halaman Profil."*
   - **Email ditemukan dan `password` terisi** → validasi password cocok → jika cocok, buat Sanctum token seperti alur Google (langkah 7-8 di atas)

**Catatan keamanan (disesuaikan konteks)**: Karena sistem ini hanya dipakai di lingkup internal kampus (bukan publik luas) dan akun **hanya bisa ada** lewat login Google UNSIL terlebih dahulu, risiko penyalahgunaan login manual oleh pihak luar sangat rendah — tidak ada cara membuat kombinasi email+password tanpa terlebih dulu memiliki akses ke akun Google UNSIL terkait. Proteksi tambahan seperti rate limiting atau syarat kompleksitas password **tidak diwajibkan** untuk MVP ini, namun tetap disarankan menggunakan hashing standar Laravel (bcrypt) untuk penyimpanan password.

---

## 3. Skema Database

### 3.1 `users`
Akun login untuk semua role (Admin, Supervisor, Dosen, Mahasiswa dalam satu tabel, dibedakan kolom `role`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `name` | varchar | Diambil dari profil Google saat registrasi pertama |
| `email` | varchar, unique | Email institusi (`@unsil.ac.id` / `@student.unsil.ac.id`) |
| `google_id` | varchar, nullable, unique | ID akun Google, untuk re-login |
| `avatar` | varchar, nullable | URL foto profil dari Google |
| `role` | enum(`admin`,`supervisor`,`dosen`,`mahasiswa`) | Ditentukan otomatis dari domain email saat registrasi (kecuali admin/supervisor: manual) |
| `password` | varchar, nullable | Hash password untuk login manual. **NULL secara default** saat akun pertama dibuat (selalu lewat Google OAuth) — terisi hanya setelah user mengatur sendiri lewat halaman Profil. Selama NULL, login manual untuk akun tersebut ditolak |
| `email_verified_at` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamp | |

### 3.2 `dosen`
Profil publik dosen (ditampilkan di halaman Daftar Dosen). Dibuat otomatis bersamaan saat user dengan email `@unsil.ac.id` registrasi pertama kali.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id`, unique | Selalu terisi (dibuat otomatis saat registrasi Dosen) |
| `nidn` | varchar, nullable | Diisi manual menyusul oleh dosen/admin |
| `bidang_riset` | varchar, nullable | |
| `roadmap_riset` | text, nullable | Peta jalan riset pribadi dosen (PRD 3.7) |
| `publikasi` | text, nullable | Ringkasan/daftar publikasi ilmiah |
| `foto` | varchar, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Catatan**: `user_id` wajib (`not null`) karena keputusan final menyatakan entri `dosen` **selalu** lahir bersamaan dengan akun `users`, tidak ada lagi dosen "profil saja tanpa akun". Kolom `name`, `email`, dan `avatar` **tidak diduplikasi** di tabel ini — selalu diambil lewat relasi ke `users` (`dosen->user->name`). Endpoint `GET /api/dosen` dan `GET /api/dosen/{id}` **wajib** memuat (eager load) relasi `user` agar nama dan foto profil ikut tampil di response.

### 3.3 `mahasiswa`
Profil mahasiswa, dibuat otomatis bersamaan saat user dengan email `@student.unsil.ac.id` registrasi pertama kali — simetris dengan pola tabel `dosen`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id`, unique | Selalu terisi (dibuat otomatis saat registrasi Mahasiswa) |
| `nim` | varchar, unique | **Diisi otomatis** dari local-part email saat registrasi (mis. `197006028@student.unsil.ac.id` → `197006028`). **Immutable** — tidak dapat diubah lewat endpoint update profil, hanya bisa dikoreksi langsung di database oleh Admin jika terjadi kesalahan data dari pihak kampus |
| `prodi` | varchar, nullable | Diisi menyusul oleh mahasiswa/admin (tidak bisa diekstrak otomatis dari email) |
| `angkatan` | varchar(4) | **Diisi otomatis** dari 2 digit awal `nim` saat registrasi, digabung dengan prefix `"20"` (format NPM UNSIL: 2 digit pertama = tahun angkatan). Mis. `nim = "197006028"` → 2 digit awal `"19"` → `angkatan = "20" . "19"` = `"2019"`. **Wajib digabung sebagai string** (concatenation), bukan operasi penjumlahan angka |
| `foto` | varchar, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Aturan implementasi penting**: Form Request untuk endpoint update profil mahasiswa (`PATCH /api/mahasiswa/{id}`) **wajib** mengabaikan/menolak perubahan pada field `nim` dan `angkatan` meskipun dikirim di request body — keduanya diturunkan otomatis saat registrasi, validasi ini di level backend, bukan hanya disembunyikan di frontend. Kolom `name`, `email`, dan `avatar` **tidak diduplikasi** di tabel ini — selalu diambil lewat relasi ke `users` (`mahasiswa->user->name`), sama seperti pola tabel `dosen`.

### 3.4 `ruangan`
Data master ruangan lab.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `nama_ruangan` | varchar | |
| `kapasitas` | int, nullable | |
| `status` | enum(`tersedia`,`dipakai`,`perbaikan`) | Dikelola Admin/Supervisor |
| `created_at`, `updated_at` | timestamp | |

### 3.5 `peminjaman_ruangan`
Pengajuan peminjaman ruangan oleh Mahasiswa/Dosen.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `ruangan_id` | bigint, FK → `ruangan.id` | |
| `user_id` | bigint, FK → `users.id` | Pengaju (Mahasiswa atau Dosen) |
| `tanggal` | date | |
| `jam_mulai` | time | |
| `jam_selesai` | time | |
| `keperluan` | text | |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`) | Default `menunggu` |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | Supervisor/Admin yang memproses |
| `created_at`, `updated_at` | timestamp | |

**Constraint penting (SRS UC-02)**: kombinasi `ruangan_id` + `tanggal` + rentang `jam_mulai`–`jam_selesai` dengan status `disetujui` **tidak boleh tumpang tindih** dengan pengajuan lain berstatus `disetujui`. Divalidasi di Form Request backend, bukan hanya constraint database.

### 3.6 `perangkat`
Data master perangkat lab (PC, Router, Switch, IoT Kit, dll).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `nama_perangkat` | varchar | |
| `nomor_seri` | varchar, unique | |
| `kategori` | varchar, nullable | Mis. "Router", "IoT Kit" |
| `status` | enum(`tersedia`,`dipinjam`,`perbaikan`) | |
| `created_at`, `updated_at` | timestamp | |

### 3.7 `peminjaman_perangkat`
Pengajuan peminjaman perangkat oleh Mahasiswa.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `perangkat_id` | bigint, FK → `perangkat.id` | |
| `user_id` | bigint, FK → `users.id` | Selalu Mahasiswa (SRS Bagian 1) |
| `tanggal_pinjam` | date | |
| `tanggal_kembali_rencana` | date | |
| `tanggal_kembali_aktual` | date, nullable | Diisi saat pengembalian dikonfirmasi |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`,`dikembalikan`) | |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | |
| `created_at`, `updated_at` | timestamp | |

### 3.8 `perpanjangan_peminjaman`
Pengajuan perpanjangan waktu pinjam perangkat (SRS UC-03).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `peminjaman_perangkat_id` | bigint, FK → `peminjaman_perangkat.id` | |
| `tanggal_kembali_baru` | date | Usulan tanggal kembali yang baru |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`) | |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Aturan (SRS UC-03)**: backend menolak insert baru di tabel ini jika `tanggal_kembali_rencana` pada `peminjaman_perangkat` terkait sudah lewat dari tanggal hari ini.

### 3.9 `presensi`
Log kehadiran mahasiswa di lab.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id` | Mahasiswa yang presensi |
| `keperluan` | varchar | Dipilih saat check-in |
| `check_in` | datetime | Disimpan dalam waktu lokal WIB |
| `check_out` | datetime, nullable | Null selama sesi masih berlangsung |
| `dicatat_oleh` | bigint, FK → `users.id`, nullable | Diisi jika entri dikoreksi Dosen/Admin |
| `created_at`, `updated_at` | timestamp | |

**Aturan (SRS UC-04)**: backend menolak `check_in` baru dari user yang sama jika masih ada entri miliknya dengan `check_out IS NULL`.

### 3.10 `sertifikasi`
Katalog informasi sertifikasi eksternal (**bukan** transaksi pendaftaran — lihat SRS Bagian 3, UC-05).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `nama_sertifikasi` | varchar | Mis. "Mikrotik Certified Network Associate" |
| `penyelenggara` | varchar | Mis. "Mikrotik", "Oracle", "Cisco" |
| `jadwal` | varchar atau date, nullable | Bisa berupa rentang/teks bebas jika jadwal belum pasti |
| `persyaratan` | text, nullable | |
| `tautan_pendaftaran` | varchar, nullable | Link/kontak eksternal untuk mendaftar |
| `created_at`, `updated_at` | timestamp | |

### 3.11 `portofolio`
Hasil riset/proyek/publikasi milik mahasiswa.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id` | Pemilik (Mahasiswa) |
| `judul` | varchar | |
| `deskripsi` | text, nullable | |
| `tautan` | varchar, nullable | Link ke repo/dokumen/demo |
| `tanggal` | date, nullable | |
| `created_at`, `updated_at` | timestamp | |

### 3.12 `info_lab`
Konten halaman informasi lab (Beranda, Visi-Misi, Profil Kepala Lab, Roadmap Lab tingkat KK) — dikelola Admin (PRD 2.5).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `tipe` | enum(`beranda`,`visi_misi`,`kepala_lab`,`roadmap_kk`) | Satu baris per tipe konten |
| `judul` | varchar, nullable | |
| `konten` | longtext | Rich text/markdown |
| `gambar` | varchar, nullable | |
| `updated_by` | bigint, FK → `users.id`, nullable | Admin terakhir yang mengubah |
| `created_at`, `updated_at` | timestamp | |

---

## 4. Diagram Relasi (ERD Ringkas)

```
users (1) ──── (1) dosen
users (1) ──── (1) mahasiswa
users (1) ──── (M) peminjaman_ruangan
users (1) ──── (M) peminjaman_perangkat
users (1) ──── (M) presensi
users (1) ──── (M) portofolio

ruangan (1) ──── (M) peminjaman_ruangan
perangkat (1) ──── (M) peminjaman_perangkat
peminjaman_perangkat (1) ──── (M) perpanjangan_peminjaman

sertifikasi   → berdiri sendiri, tidak ada relasi ke users (murni katalog)
info_lab      → berdiri sendiri, hanya relasi updated_by ke users
```

---

## 5. Struktur API (REST Endpoints)

Semua endpoint berprefix `/api`, dilindungi `auth:sanctum` kecuali ditandai **(publik)**. Otorisasi per role diimplementasikan via Laravel Policy mengacu matriks RBAC di `2_SRS.md` Bagian 1.

### 5.1 Autentikasi
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/auth/google/redirect` | **(publik)** Mulai alur Google OAuth |
| GET | `/api/auth/google/callback` | **(publik)** Callback Google, proses registrasi/login otomatis (lihat Bagian 2) |
| POST | `/api/auth/login` | **(publik)** Login manual (email + password). Ditolak jika `password` user masih NULL (lihat Bagian 2.1) |
| POST | `/api/auth/logout` | Hapus token Sanctum aktif |
| GET | `/api/auth/me` | Ambil data user yang sedang login |
| POST | `/api/auth/set-password` | Atur password pertama kali (hanya `password` baru + konfirmasi, untuk user yang `password`-nya masih NULL) |
| PATCH | `/api/auth/change-password` | Ubah password yang sudah ada (wajib sertakan password lama) |

### 5.2 User & Role (Admin only)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/users` | List semua user (filter by role) |
| PATCH | `/api/users/{id}` | Update data/role user |
| DELETE | `/api/users/{id}` | Hapus user |

### 5.3 Dosen
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/dosen` | List semua dosen (untuk halaman Daftar Dosen) |
| GET | `/api/dosen/{id}` | Detail profil satu dosen |
| PATCH | `/api/dosen/{id}` | Update profil — milik sendiri (Dosen) atau Admin/Supervisor |

### 5.4 Mahasiswa
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/mahasiswa/{id}` | Detail profil satu mahasiswa (milik sendiri, atau Admin/Dosen pembimbing) |
| PATCH | `/api/mahasiswa/{id}` | Update profil milik sendiri — field `nim` diabaikan/ditolak meski dikirim di body (lihat SDD 3.3) |

### 5.5 Ruangan & Peminjaman Ruangan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/ruangan` | List ruangan + status |
| POST | `/api/ruangan` | Tambah ruangan (Admin/Supervisor) |
| PATCH | `/api/ruangan/{id}` | Update status/data ruangan |
| GET | `/api/peminjaman-ruangan` | List pengajuan (milik sendiri, atau semua untuk Admin/Supervisor) |
| GET | `/api/peminjaman-ruangan/kalender` | Data kalender ketersediaan |
| POST | `/api/peminjaman-ruangan` | Ajukan peminjaman (Mahasiswa/Dosen) |
| PATCH | `/api/peminjaman-ruangan/{id}/approve` | Setujui (Admin/Supervisor) |
| PATCH | `/api/peminjaman-ruangan/{id}/reject` | Tolak (Admin/Supervisor) |

### 5.6 Perangkat, Peminjaman & Perpanjangan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/perangkat` | List perangkat + status |
| POST | `/api/perangkat` | Tambah perangkat (Admin/Supervisor) |
| PATCH | `/api/perangkat/{id}` | Update data/status perangkat |
| GET | `/api/peminjaman-perangkat` | List pengajuan (milik sendiri / semua untuk Admin/Supervisor) |
| POST | `/api/peminjaman-perangkat` | Ajukan peminjaman (Mahasiswa) |
| PATCH | `/api/peminjaman-perangkat/{id}/approve` | Setujui (Admin/Supervisor) |
| PATCH | `/api/peminjaman-perangkat/{id}/reject` | Tolak (Admin/Supervisor) |
| POST | `/api/peminjaman-perangkat/{id}/perpanjangan` | Ajukan perpanjangan (Mahasiswa) |
| PATCH | `/api/perpanjangan/{id}/approve` | Setujui perpanjangan (Admin/Supervisor) |
| PATCH | `/api/perpanjangan/{id}/reject` | Tolak perpanjangan (Admin/Supervisor) |

### 5.7 Presensi
| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/presensi/check-in` | Check-in (Mahasiswa) |
| PATCH | `/api/presensi/{id}/check-out` | Check-out (Mahasiswa) |
| GET | `/api/presensi` | List presensi (milik sendiri / mahasiswa bimbingan untuk Dosen / rekap untuk Admin-Supervisor) |
| PATCH | `/api/presensi/{id}` | Koreksi entri (Dosen, untuk mahasiswa bimbingan) |
| DELETE | `/api/presensi/{id}` | Hapus entri (Dosen) |

### 5.8 Sertifikasi (Katalog)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/sertifikasi` | List katalog sertifikasi |
| POST | `/api/sertifikasi` | Tambah entri (Admin/Supervisor) |
| PATCH | `/api/sertifikasi/{id}` | Update entri |
| DELETE | `/api/sertifikasi/{id}` | Hapus entri |

### 5.9 Portofolio
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/portofolio` | List (semua, atau filter `user_id`) |
| POST | `/api/portofolio` | Tambah (Mahasiswa, milik sendiri) |
| PATCH | `/api/portofolio/{id}` | Update milik sendiri |
| DELETE | `/api/portofolio/{id}` | Hapus milik sendiri |

### 5.10 Informasi Lab
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/info-lab/{tipe}` | Ambil konten (beranda/visi_misi/kepala_lab/roadmap_kk) |
| PATCH | `/api/info-lab/{tipe}` | Update konten (Admin) |

### 5.11 Laporan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/report?from=&to=` | Data rekap (Admin/Supervisor) |
| GET | `/api/report/pdf?from=&to=` | Unduh PDF rekap |

---

## 6. Format Response API (Konvensi)

Seluruh response API mengikuti format konsisten:

**Sukses (single/list)**:
```json
{
  "data": { ... } ,
  "message": "Berhasil mengambil data"
}
```

**Error validasi (HTTP 422)**:
```json
{
  "message": "Data tidak valid",
  "errors": { "field_name": ["Pesan error"] }
}
```

**Error otorisasi (HTTP 403)**:
```json
{
  "message": "Anda tidak memiliki akses untuk tindakan ini"
}
```

---

## 7. Lingkup di Luar Dokumen Ini

Dokumen ini sengaja **tidak** membahas:
- Visi produk, persona, dan alur pengguna tingkat tinggi → lihat `1_PRD.md`
- Aturan validasi bisnis detail per use case → lihat `2_SRS.md`
- Breakdown task implementasi → lihat `4_TASK_BREAKDOWN.md`
