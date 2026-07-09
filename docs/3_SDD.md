# 3. System Design Document (SDD)

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Unit Terkait**: Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) — Prodi Informatika
**Versi Dokumen**: 1.1
**Dokumen Acuan**: `1_PRD.md`, `2_SRS.md`

> **Perubahan v1.1 (per 2026-07-09)**: skema `presensi` (3.12) **dihapus**, digantikan `tugas` (3.12) + `deadline_pertemuan` (3.12a). `kelas_lab` (3.7) mendapat kolom `tautan_pengumpulan`. `notifikasi` (3.16) menambah nilai enum `pengingat`. ERD & daftar endpoint (5.9 Pengumpulan Tugas, 5.13 Laporan) diselaraskan. Ditambah 3.16 `notifikasi`, 3.17 `deadline_pertemuan` catatan penjadwalan, dan endpoint Rekap Tugas (5.15) + Pengingat terjadwal.

> Dokumen ini adalah **sumber kebenaran** untuk skema database, struktur API, dan arsitektur sistem. Semua AI Agent **wajib** merujuk dokumen ini sebelum membuat migration, model, atau route — lihat `.clinerules/agent.md`. AI Agent **dilarang** mengasumsikan struktur data di luar yang didefinisikan di sini.

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
     - **Jika role Mahasiswa** → sekaligus insert entri baru ke tabel `mahasiswa`, di-link via `user_id`. Kolom `npm` diisi otomatis dengan mengekstrak local-part dari email (bagian sebelum `@`), mis. email `197006028@student.unsil.ac.id` → `npm = "197006028"` (lihat Bagian 3.3)
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
| `email` | varchar, unique | Email institusi (`@unsil.ac.id` / `@student.unsil.ac.id`). **Immutable** — tidak dapat diubah lewat Edit Profil (acuan identitas + alur Google OAuth) |
| `email_pribadi` | varchar, nullable | Email cadangan/kontak yang diisi sendiri di tab **Akun**. **Bukan** untuk login (login hanya via email institusi + password) — murni info kontak |
| `no_telp` | varchar(32), nullable | Nomor telepon/HP. Dipakai semua role, diisi sendiri oleh pemilik akun lewat Edit Profil (`PATCH /api/auth/profile`) |
| `google_id` | varchar, nullable, unique | ID akun Google, untuk re-login |
| `avatar` | varchar, nullable | URL foto profil. Awalnya diisi URL dari Google saat registrasi; pemilik akun dapat menggantinya dengan unggah file lewat `POST /api/auth/avatar` (disimpan di disk publik, kolom menyimpan URL absolut) |
| `role` | enum(`admin`,`supervisor`,`dosen`,`mahasiswa`) | Ditentukan otomatis dari domain email saat registrasi (kecuali admin/supervisor: manual) |
| `password` | varchar, nullable | Hash password untuk login manual. **NULL secara default** saat akun pertama dibuat (selalu lewat Google OAuth) — terisi hanya setelah user mengatur sendiri lewat halaman Profil. Selama NULL, login manual untuk akun tersebut ditolak |
| `email_verified_at` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamp | |

### 3.2 `dosen`
Profil publik dosen (ditampilkan di halaman Daftar Dosen). Dibuat otomatis bersamaan saat user dengan email `@unsil.ac.id` registrasi pertama kali.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id`, unique | Selalu terisi (dibuat otomatis saat registrasi Dosen), `on delete cascade` |
| `nidn` | varchar, nullable | Diisi manual menyusul oleh dosen/admin |
| `jenis_kelamin` | varchar, nullable | `Laki-laki` / `Perempuan` — ditampilkan di halaman Detail Dosen |
| `jabatan_fungsional` | varchar, nullable | Mis. `Lektor`, `Lektor Kepala` |
| `tempat_lahir` | varchar, nullable | Komponen "Tempat, Tanggal Lahir" di Detail Dosen |
| `tanggal_lahir` | date, nullable | Diformat ke teks Indonesia di frontend (mis. `10 Desember 1972`) |
| `biografi` | text, nullable | Narasi biografi singkat dosen (halaman Detail Dosen) |
| `credential` | text, nullable | Sertifikasi/keahlian dosen (mis. CEH, CHFI) — diedit di tab **Data Akademik** Profil |
| `roadmap_riset` | text, nullable | Peta jalan riset pribadi dosen (PRD 3.7) |
| `publikasi` | text, nullable | Ringkasan/daftar publikasi ilmiah |
| `buku` | text, nullable | Daftar buku/karya — diedit di tab **Data Akademik** Profil |
| `foto` | varchar, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Catatan**: `user_id` wajib (`not null`) karena keputusan final menyatakan entri `dosen` **selalu** lahir bersamaan dengan akun `users`, tidak ada lagi dosen "profil saja tanpa akun". Kolom `name`, `email`, dan `avatar` **tidak diduplikasi** di tabel ini — selalu diambil lewat relasi ke `users` (`dosen->user->name`). Endpoint `GET /api/dosen` and `GET /api/dosen/{id}` **wajib** memuat (eager load) relasi `user` **dan** `bidangMinat` agar nama, foto, serta Bidang Minat ikut tampil di response. **Bidang Minat** dosen disimpan sebagai relasi many-to-many (lihat 3.2a), bukan kolom di tabel ini. Kolom `roadmap_riset` dipakai halaman **Roadmap Penelitian Dosen** (berbeda dari Roadmap Laboratorium di `info_lab.roadmap_kk`).

### 3.2a `bidang_minat` & `dosen_bidang_minat` (Bidang Minat)
Master **Bidang Minat** — daftar bidang yang dikelola Admin/Supervisor dan dipilih Dosen (boleh lebih dari satu) di Edit Profil. Penamaan konsisten `bidang_minat` di seluruh lapisan (tabel, pivot, model `BidangMinat`, route `/api/bidang-minat`, Gate `manage-bidang-minat`, service `bidangMinatService`).

**`bidang_minat`** (master)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `nama` | varchar, unique | Mis. `Digital Forensik`, `Internet of Things` |
| `created_at`, `updated_at` | timestamp | |

**`dosen_bidang_minat`** (pivot banyak-ke-banyak `dosen ↔ bidang_minat`)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `dosen_id` | bigint, FK → `dosen.id` | `on delete cascade`; PK komposit bersama `bidang_minat_id` |
| `bidang_minat_id` | bigint, FK → `bidang_minat.id` | `on delete cascade` |

**Akses**: CRUD master via Gate `manage-bidang-minat` (Admin/Supervisor); read terbuka untuk semua role yang login (dipakai dropdown Edit Profil). Pemilihan dosen disinkronkan (`sync`) lewat `PATCH /api/auth/profile` (`bidang_minat_ids[]`).

### 3.3 `mahasiswa`
Profil mahasiswa, dibuat otomatis bersamaan saat user dengan email `@student.unsil.ac.id` registrasi pertama kali — simetris dengan pola tabel `dosen`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id`, unique | Selalu terisi (dibuat otomatis saat registrasi Mahasiswa), `on delete cascade` |
| `dosen_pembimbing_id` | bigint, FK → `dosen.id`, nullable | Dosen pembimbing riset/akademik untuk validasi bimbingan, `on delete set null` |
| `npm` | varchar, unique | **Diisi otomatis** dari local-part email saat registrasi (mis. `197006028@student.unsil.ac.id` → `197006028`). **Immutable** — tidak dapat diubah lewat endpoint update profil, hanya bisa dikoreksi langsung di database oleh Admin jika terjadi kesalahan data dari pihak kampus |
| `prodi` | varchar, nullable | Diisi menyusul oleh mahasiswa/admin (tidak bisa diekstrak otomatis dari email) |
| `angkatan` | varchar(4) | **Diisi otomatis** dari 2 digit awal `npm` saat registrasi, digabung dengan prefix `"20"` (format NPM UNSIL: 2 digit pertama = tahun angkatan). Mis. `npm = "197006028"` → 2 digit awal `"19"` → `angkatan = "20" . "19"` = `"2019"`. **Wajib digabung sebagai string** (concatenation), bukan operasi penjumlahan angka |
| `foto` | varchar, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Aturan implementasi penting**: Form Request untuk endpoint update profil mahasiswa (`PATCH /api/mahasiswa/{id}`) **wajib** mengabaikan/menolak perubahan pada field `npm` dan `angkatan` meskipun dikirim di request body — keduanya diturunkan otomatis saat registrasi, validasi ini di level backend, bukan hanya disembunyikan di frontend. Kolom `name`, `email`, dan `avatar` **tidak diduplikasi** di tabel ini — selalu diambil lewat relasi ke `users` (`mahasiswa->user->name`), sama seperti pola tabel `dosen`. Kolom `dosen_pembimbing_id` digunakan oleh backend Policy untuk otorisasi akses bimbingan.

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
Pengajuan peminjaman ruangan oleh Mahasiswa (Dosen tidak meminjam ruangan — SRS UC-02).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `ruangan_id` | bigint, FK → `ruangan.id` | `on delete cascade` |
| `user_id` | bigint, FK → `users.id` | Pengaju (Mahasiswa), `on delete cascade` |
| `tanggal` | date | |
| `jam_mulai` | time | |
| `jam_selesai` | time | |
| `keperluan` | text | |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`) | Default `menunggu` |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | Supervisor/Admin yang memproses, `on delete set null` |
| `created_at`, `updated_at` | timestamp | |

**Constraint penting (SRS UC-02)**: kombinasi `ruangan_id` + `tanggal` + rentang `jam_mulai`–`jam_selesai` dengan status `disetujui` **tidak boleh tumpang tindih** dengan pengajuan lain berstatus `disetujui`, **maupun** dengan jadwal `kelas_lab` (3.6) yang aktif pada ruangan, hari, dan jam yang sama. Peminjaman hanya diizinkan jika `ruangan.status = 'tersedia'`. **Jam wajib dalam rentang operasional lab 07.00–17.00 WIB** (`jam_mulai ≥ 07:00`, `jam_selesai ≤ 17:00`). Validasi dilakukan di Form Request backend, bukan hanya constraint database.

### 3.6 `mata_kuliah`
Data master mata kuliah/praktikum — induk yang mengelompokkan sesi-sesi Kelas Lab (Kelas A/B/C) yang merupakan sesi paralel dari mata kuliah yang sama.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kode_mk` | varchar, nullable, unique | Mis. "JKF301", jika ada kodefikasi resmi dari kampus |
| `nama_mk` | varchar | Mis. "Praktikum Jaringan Komputer" |
| `sks` | int, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Aturan akses (konsisten dengan pola data master lain)**: CRUD data `mata_kuliah` dikelola **Admin/Supervisor** (lihat `ruangan`, `perangkat`). Dosen **tidak** membuat entri mata kuliah baru sendiri — saat membuka Kelas Lab, Dosen memilih dari daftar `mata_kuliah` yang sudah tersedia.

### 3.7 `kelas_lab`
Jadwal Kelas Lab/Praktikum — satu sesi terjadwal tetap (umumnya mingguan) selama satu semester, merupakan satu sesi dari sebuah `mata_kuliah`. Mekanisme **terpisah** dari `peminjaman_ruangan` (SRS UC-02a).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `mata_kuliah_id` | bigint, FK → `mata_kuliah.id` | Mata kuliah/praktikum induk, `on delete cascade` |
| `dosen_id` | bigint, FK → `dosen.id` | Pemilik/pengampu kelas, `on delete cascade` |
| `ruangan_id` | bigint, FK → `ruangan.id` | `on delete cascade` |
| `dibuat_oleh` | bigint, FK → `users.id` | User yang membuat entri — Dosen sendiri, atau Supervisor (atas permintaan Dosen), `on delete cascade` |
| `nama_sesi` | varchar | Label pembeda sesi paralel, mis. "Kelas A", "Kelas B" — digabung dengan `mata_kuliah.nama_mk` saat ditampilkan (mis. "Praktikum Jaringan Komputer — Kelas A") |
| `hari` | enum(`senin`,`selasa`,`rabu`,`kamis`,`jumat`,`sabtu`) | Pola berulang mingguan |
| `jam_mulai` | time | |
| `jam_selesai` | time | |
| `tanggal_mulai_semester` | date | |
| `tanggal_selesai_semester` | date | |
| `kuota` | int | Maksimal 30-40 (divalidasi range di Form Request, bukan hardcode di kolom) |
| `tautan_pengumpulan` | varchar(2048), nullable | Tautan tempat unggah dokumen laporan (PDF/DOCX) yang diisi Dosen; ditampilkan ke mahasiswa di form Kirim Tugas. Nullable di DB agar baris lama valid, namun **wajib diisi** lewat validasi `Store/UpdateKelasLabRequest` |
| `created_at`, `updated_at` | timestamp | |

**Catatan implementasi**:
- Beberapa sesi paralel (Kelas A, B, C) dari mata kuliah yang sama disimpan sebagai **baris terpisah** di tabel ini, semuanya merujuk `mata_kuliah_id` yang sama, masing-masing dengan `kuota` independen — pengelompokan formal kini tersedia lewat relasi ini (mis. untuk menampilkan "semua sesi Praktikum Jaringan Komputer" atau laporan rekap per mata kuliah)
- Relasi `kelas_lab (1) ──── (M) deadline_pertemuan` (3.12a) & `kelas_lab (1) ──── (M) tugas` (3.12) — dipakai untuk fitur tugas per pertemuan & Rekap Tugas.
- Backend **wajib** memvalidasi `dosen_id` yang dimasukkan benar merujuk dosen yang sah, terlepas dari apakah yang membuat entri adalah Dosen itu sendiri (`dibuat_oleh = dosen_id`'s `user_id`) atau Supervisor atas permintaannya
- Constraint bentrok jadwal terhadap `peminjaman_ruangan` dan sesama `kelas_lab` lain divalidasi di Form Request, mengecek ruangan + hari + rentang jam yang overlap, dalam rentang `tanggal_mulai_semester`–`tanggal_selesai_semester`. Pembukaan kelas hanya diizinkan jika `ruangan.status = 'tersedia'`.
- **Jam wajib dalam rentang operasional lab 07.00–17.00 WIB** (`jam_mulai ≥ 07:00`, `jam_selesai ≤ 17:00`).

### 3.8 `kelas_lab_peserta`
Pendaftaran mahasiswa sebagai peserta suatu sesi Kelas Lab/Praktikum. **Pendaftaran butuh persetujuan** Dosen pengampu (atau Supervisor) sebelum mahasiswa resmi menjadi peserta.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kelas_lab_id` | bigint, FK → `kelas_lab.id` | `on delete cascade` |
| `mahasiswa_id` | bigint, FK → `mahasiswa.id` | `on delete cascade` |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`) | Default `menunggu`. Diubah oleh Dosen pengampu/Supervisor lewat menu Persetujuan Pendaftaran |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | Dosen/Supervisor yang memproses, `on delete set null` |
| `created_at`, `updated_at` | timestamp | |

**Constraint & aturan (SRS UC-02a)**:
- Kombinasi `kelas_lab_id` + `mahasiswa_id` **unique** (tak bisa mendaftar dua kali ke sesi yang sama). Baris `ditolak` boleh diajukan ulang — backend mengubah statusnya kembali ke `menunggu` (tanpa baris baru).
- **Kuota memesan slot**: jumlah baris berstatus `menunggu` + `disetujui` (mengecualikan `ditolak`) untuk satu `kelas_lab` **tidak boleh melebihi** `kuota` — divalidasi di backend.
- **Satu sesi per mata kuliah**: mahasiswa tidak boleh mengambil lebih dari satu sesi pada `mata_kuliah` yang sama, namun **boleh** mengambil sesi pada mata kuliah berbeda (selama tidak bentrok jadwal).
- **Tanpa bentrok jadwal**: sesi baru tidak boleh `hari` sama + rentang jam tumpang tindih dengan sesi lain yang sudah diambil mahasiswa tsb.
- `sisa_kuota` yang ditampilkan = `kuota − (menunggu + disetujui)`.
- **Pembatalan**: Mahasiswa hanya dapat membatalkan pendaftaran saat status `menunggu`. Setelah `disetujui`, hanya Dosen pengampu/Supervisor yang dapat mengeluarkan peserta (`DELETE /api/kelas-lab/pendaftaran/{peserta}` — lihat 5.7).

### 3.9 `perangkat`
Data master perangkat lab (PC, Router, Switch, IoT Kit, dll).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `nama_perangkat` | varchar | |
| `nomor_seri` | varchar, unique | |
| `kategori` | varchar, nullable | Mis. "Router", "IoT Kit" |
| `status` | enum(`tersedia`,`dipinjam`,`perbaikan`) | |
| `created_at`, `updated_at` | timestamp | |

### 3.10 `peminjaman_perangkat`
Pengajuan peminjaman perangkat oleh Mahasiswa.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `perangkat_id` | bigint, FK → `perangkat.id` | `on delete cascade` |
| `user_id` | bigint, FK → `users.id` | Selalu Mahasiswa (SRS Bagian 1), `on delete cascade` |
| `tanggal_pinjam` | date | |
| `tanggal_kembali_rencana` | date | |
| `tanggal_kembali_aktual` | date, nullable | Diisi saat pengembalian dikonfirmasi |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`,`dikembalikan`) | |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | `on delete set null` |
| `created_at`, `updated_at` | timestamp | |

### 3.11 `perpanjangan_peminjaman`
Pengajuan perpanjangan waktu pinjam perangkat (SRS UC-03).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `peminjaman_perangkat_id` | bigint, FK → `peminjaman_perangkat.id` | `on delete cascade` |
| `tanggal_kembali_baru` | date | Usulan tanggal kembali yang baru |
| `status` | enum(`menunggu`,`disetujui`,`ditolak`) | |
| `disetujui_oleh` | bigint, FK → `users.id`, nullable | `on delete set null` |
| `created_at`, `updated_at` | timestamp | |

**Aturan (SRS UC-03)**:
- Backend menolak insert baru di tabel ini jika `tanggal_kembali_rencana` pada `peminjaman_perangkat` terkait sudah lewat dari tanggal hari ini.
- Ketika status perpanjangan diperbarui menjadi `disetujui`, backend wajib secara otomatis (lewat DB Transaction) memperbarui kolom `tanggal_kembali_rencana` pada tabel `peminjaman_perangkat` induk menjadi nilai dari `tanggal_kembali_baru`.

### 3.12 `tugas` (menggantikan `presensi`)
Pengumpulan tugas mahasiswa (tautan/URL hasil) untuk sebuah pertemuan pada sesi Kelas Lab yang diikutinya. **Menggantikan modul `presensi` v1.0** (tabel `presensi` di-drop lewat migrasi `..._drop_presensi_table`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kelas_lab_id` | bigint, FK → `kelas_lab.id` | Sesi kelas konteks tugas, `on delete cascade`; ada index |
| `pertemuan` | tinyint unsigned | Pertemuan 1–16 dalam satu semester (default 1) |
| `mahasiswa_id` | bigint, FK → `mahasiswa.id` | Pengirim tugas, `on delete cascade` |
| `judul` | varchar | Judul tugas |
| `tautan` | varchar(2048) | URL hasil tugas (GDrive/GitHub/dll), divalidasi `url` |
| `created_at`, `updated_at` | timestamp | `created_at` dipakai membandingkan tepat/telat vs `deadline_pertemuan.deadline` |

**Aturan (SRS UC-04)**:
- Hanya mahasiswa berstatus peserta **`disetujui`** pada `kelas_lab` tujuan yang boleh mengirim (divalidasi `StoreTugasRequest`).
- **Satu tugas per (`kelas_lab_id`, `pertemuan`, `mahasiswa_id`)** — pengiriman kedua ditolak.
- Deadline **tidak memblokir** pengiriman; keterlambatan ditandai dengan membandingkan `tugas.created_at` (WIB) terhadap `deadline_pertemuan.deadline`.
- Saat tugas dibuat, sistem mengirim notifikasi `pengajuan_masuk` ke **dosen pengampu kelas + semua Supervisor** (transaksi sama, SRS UC-07).

### 3.12a `deadline_pertemuan`
Materi &/atau deadline pengumpulan tugas untuk satu pertemuan (1–16) sebuah Kelas Lab, ditetapkan Dosen pengampu / Supervisor / Admin.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kelas_lab_id` | bigint, FK → `kelas_lab.id` | `on delete cascade` |
| `pertemuan` | tinyint unsigned | 1–16; unique bersama `kelas_lab_id` |
| `materi` | varchar, nullable | Nama materi/silabus pertemuan — boleh berdiri sendiri tanpa deadline |
| `deadline` | datetime, nullable | Tenggat pengumpulan (WIB). **Nullable**: pertemuan tanpa `deadline` **tidak dihitung sebagai tugas** |
| `created_at`, `updated_at` | timestamp | |

**Aturan**:
- Unique `(kelas_lab_id, pertemuan)` — satu record per pertemuan per kelas (upsert via `PUT`).
- Record valid bila **minimal salah satu** dari `materi`/`deadline` terisi; bila keduanya dikosongkan, record dihapus.
- **"Tanpa deadline = tidak ada tugas"**: kolom matriks Rekap Tugas (5.15) hanya mencakup pertemuan yang punya `deadline`.

### 3.13 `sertifikasi`
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

### 3.14 `portofolio`
Hasil riset/proyek/publikasi milik mahasiswa.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id` | Pemilik (Mahasiswa), `on delete cascade` |
| `judul` | varchar | |
| `deskripsi` | text, nullable | |
| `tautan` | varchar, nullable | Link ke repo/dokumen/demo |
| `tanggal` | date, nullable | |
| `created_at`, `updated_at` | timestamp | |

### 3.15 `info_lab`
Konten halaman informasi lab (Beranda, Visi-Misi, Profil Kepala Lab, Roadmap Lab tingkat KK) — dikelola Admin (PRD 2.5).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `tipe` | enum(`beranda`,`visi_misi`,`kepala_lab`,`roadmap_kk`) | Satu baris per tipe konten |
| `judul` | varchar, nullable | |
| `konten` | longtext | Rich text — lihat catatan format di bawah |
| `gambar` | varchar, nullable | |
| `dosen_id` | bigint, FK → `dosen.id`, nullable | Khusus tipe `kepala_lab` — tautan ke entri dosen, `on delete set null` |
| `updated_by` | bigint, FK → `users.id`, nullable | Admin terakhir yang mengubah |
| `created_at`, `updated_at` | timestamp | |

**Catatan format konten**: Kolom `konten` menyimpan **HTML** yang dihasilkan editor visual (TipTap WYSIWYG) di panel Admin (Konten Info Lab). Konten lama yang masih berformat **Markdown** tetap didukung — frontend (`markdown-content.vue`) merender baik HTML maupun Markdown (lewat `marked`).

**Profil Kepala Lab (`kepala_lab`)**: bila baris ini punya `dosen_id`, halaman publik dirender sebagai **kartu identitas terstruktur** dari profil dosen tertaut (nama, jabatan fungsional, NIDN, jenis kelamin, TTL, email, no. telp, Bidang Minat) — `GET /api/info-lab/kepala_lab` meng-eager-load `dosen.user` & `dosen.bidangMinat`. Bila `dosen_id` kosong, halaman jatuh kembali ke konten bebas (`judul`/`gambar`/`konten`). Admin menautkan dosen lewat fitur *Ambil dari Profil Dosen* (men-set `dosen_id` saat disimpan; tidak menambah endpoint baru).

### 3.16 `notifikasi`
Notifikasi in-app (SRS UC-07). Dibuat **otomatis oleh sistem** sebagai efek samping aksi lain (approve/reject/pengajuan baru/tugas masuk) di dalam transaksi yang sama, atau lewat penjadwalan berkala (pengingat). Tidak ada endpoint pembuatan publik.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | bigint, FK → `users.id` | Penerima, `on delete cascade` |
| `judul` | varchar | |
| `pesan` | text | |
| `tipe` | enum(`pengajuan_masuk`,`status_pengajuan`,`pendaftaran`,`pengingat`) | Menentukan ikon/warna di frontend. `pengingat` = tenggat tugas / pengembalian perangkat (terjadwal) |
| `referensi_id` | bigint unsigned, nullable | ID entitas pemicu (peminjaman/perpanjangan/kelas/tugas) untuk navigasi — **tanpa FK** (lintas tabel) |
| `is_read` | boolean, default false | |
| `created_at`, `updated_at` | timestamp | Index komposit `(user_id, is_read)` untuk hitung unread & list milik user |

**Aturan (SRS UC-07)**:
- Insert `notifikasi` dilakukan dalam **DB transaction yang sama** dengan aksi pemicunya (rollback → notifikasi ikut batal).
- `GET /api/auth/me` menyertakan `unread_notifications_count` (COUNT `is_read=false` milik user) untuk badge navbar tanpa request tambahan.
- Notifikasi `pengingat` dibuat oleh command terjadwal (lihat 3.17) dan **idempoten** — tidak menduplikasi pengingat yang sama untuk pasangan pengguna–deadline/peminjaman yang sama.

### 3.17 Penjadwalan (Scheduler) & Pengingat
Dua command terjadwal (didaftarkan di `bootstrap/app.php` via `withSchedule`, dijalankan oleh `schedule:run`/`schedule:work`):

| Command | Jadwal | Fungsi |
|---|---|---|
| `pengingat:deadline` | tiap jam (`hourly`) | `PengingatDeadlineService` — kirim notifikasi `pengingat` ke mahasiswa peserta `disetujui` yang belum mengumpulkan untuk `deadline_pertemuan` yang sudah lewat; idempoten per (user, deadline). Hook lazy juga dipanggil di `NotifikasiController@index` |
| `pengingat:pengembalian` | harian 07.00 (`dailyAt`) | `PengingatPengembalianService` — kirim notifikasi `pengingat` pengembalian perangkat yang jatuh tempo |

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

sertifikasi   → berdiri sendiri, tidak ada relasi ke users (murni katalog)
info_lab      → relasi updated_by ke users; opsional dosen_id (tipe kepala_lab)
notifikasi    → referensi_id lintas tabel (tanpa FK); hanya user_id yang ber-FK
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
| GET | `/api/auth/me` | Ambil data user yang sedang login; response menyertakan field `unread_notifications_count` (integer) untuk keperluan badge navbar |
| POST | `/api/auth/set-password` | Atur password pertama kali (hanya `password` baru + konfirmasi, untuk user yang `password`-nya masih NULL) |
| PATCH | `/api/auth/change-password` | Ubah password yang sudah ada (wajib sertakan password lama) |
| POST | `/api/auth/avatar` | Unggah/ganti foto profil akun sendiri (multipart, field `avatar`: `jpeg/jpg/png/webp`, maks 2 MB). File disimpan di disk publik (`storage/app/public/avatars`, nama file UUID), kolom `avatar` diisi URL absolut. Avatar lama yang berupa file lokal dihapus; avatar Google eksternal dibiarkan |
| PATCH | `/api/auth/profile` | Edit profil akun sendiri (halaman Profil, 3 tab: Akun/Data Pribadi/Data Akademik). Semua role: `name`, `no_telp`, `email_pribadi` (email cadangan, **bukan** login). Dosen: `nidn`, `jabatan_fungsional`, `tempat_lahir`, `tanggal_lahir`, `bidang_minat_ids[]` + **Data Akademik**: `biografi`, `credential`, `publikasi`, `buku`, `roadmap_riset`. Mahasiswa: `prodi` (whitelist `Informatika`). `email`, `role`, `npm`/`angkatan` **immutable** |

**Catatan**:
- Penyimpanan avatar memerlukan disk publik Laravel aktif (`php artisan storage:link`) agar URL `…/storage/avatars/…` dapat diakses frontend.
- **Bidang Minat (master, banyak-banyak)**: dikelola Admin/Supervisor lewat panel **Bidang Minat** (Gate `manage-bidang-minat`), lalu dipilih Dosen di Edit Profil. Dosen mengirim `bidang_minat_ids[]` ke `PATCH /api/auth/profile`; backend `sync` ke pivot `dosen_bidang_minat`. Ditampilkan di kartu Profil Saya & halaman Detail Dosen (lihat skema 3.2a).

### 5.2 User & Role (Admin only)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/users` | List semua user (filter by role) |
| POST | `/api/users` | Pendaftaran/pembuatan user manual (Dosen/Supervisor/Admin) dengan credential awal |
| PATCH | `/api/users/{id}` | Update data/role user |
| DELETE | `/api/users/{id}` | Hapus user |

**Delegasi Asisten Lab (Aslab)** — Admin menetapkan mahasiswa jadi Supervisor (Gate `manage-users`). Sengaja dibatasi hanya transisi mahasiswa↔supervisor (bukan ubah role bebas):

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/aslab` | `{ kandidat: [mahasiswa], aslab: [supervisor dari mahasiswa] }` |
| POST | `/api/aslab/{user}` | Jadikan mahasiswa → Supervisor (profil mahasiswa dipertahankan) |
| DELETE | `/api/aslab/{user}` | Kembalikan Supervisor (dari mahasiswa) → Mahasiswa |

### 5.3 Dosen
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/dosen` | **(publik)** List semua dosen (halaman Daftar Dosen), eager load relasi `user` |
| GET | `/api/dosen/{id}` | **(publik)** Detail profil satu dosen (halaman Biografi/Detail Dosen) |
| PATCH | `/api/dosen/{id}` | Update profil — pemilik (Dosen) atau Admin/Supervisor (via `DosenPolicy`). Field `name`/`no_telp` ditulis ke akun `users`, sisanya ke tabel `dosen` |

### 5.4 Mahasiswa
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/mahasiswa` | List semua mahasiswa — Admin & Supervisor (operasional penuh); Dosen (read-only, untuk kebutuhan rekap tugas mahasiswa) |
| GET | `/api/mahasiswa/{id}` | Detail profil satu mahasiswa (milik sendiri, atau Admin/Dosen pembimbing) |
| PATCH | `/api/mahasiswa/{id}` | Update profil milik sendiri — field `npm` diabaikan/ditolak meski dikirim di body (lihat SDD 3.3) |

### 5.5 Ruangan & Peminjaman Ruangan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/ruangan` | List ruangan + status |
| POST | `/api/ruangan` | Tambah ruangan (Admin/Supervisor) |
| PATCH | `/api/ruangan/{id}` | Update status/data ruangan |
| DELETE | `/api/ruangan/{id}` | Hapus ruangan (Admin/Supervisor) — ditolak jika masih ada `peminjaman_ruangan` atau `kelas_lab` aktif yang merujuk ruangan ini |
| GET | `/api/peminjaman-ruangan` | List pengajuan (milik sendiri, atau semua untuk Admin/Supervisor) |
| GET | `/api/peminjaman-ruangan/kalender` | Data ketersediaan: `kelas_lab` aktif + peminjaman `disetujui` **mulai awal minggu berjalan ke depan** (peminjaman minggu lalu otomatis rontok tiap pergantian minggu) |
| POST | `/api/peminjaman-ruangan` | Ajukan peminjaman (**Mahasiswa saja**) |
| PATCH | `/api/peminjaman-ruangan/{id}/approve` | Setujui (Admin/Supervisor) — validasi ulang bentrok & status ruangan |
| PATCH | `/api/peminjaman-ruangan/{id}/reject` | Tolak (Admin/Supervisor) |
| DELETE | `/api/peminjaman-ruangan/{id}` | Hapus pengajuan dari daftar (Admin/Supervisor) |

### 5.6 Mata Kuliah (Data Master)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/mata-kuliah` | List mata kuliah (semua role — dipakai Dosen saat memilih saat membuka Kelas Lab) |
| POST | `/api/mata-kuliah` | Tambah mata kuliah (Admin/Supervisor) |
| PATCH | `/api/mata-kuliah/{id}` | Update data mata kuliah (Admin/Supervisor) |
| DELETE | `/api/mata-kuliah/{id}` | Hapus mata kuliah (Admin/Supervisor) |

### 5.7 Kelas Lab/Praktikum
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/kelas-lab` | List Kelas Lab/Praktikum (semua role, untuk melihat & mendaftar); dapat difilter per `mata_kuliah_id` untuk melihat semua sesi paralel suatu mata kuliah |
| GET | `/api/kelas-lab/{id}` | Detail satu kelas/sesi, termasuk sisa kuota |
| POST | `/api/kelas-lab` | Buka Kelas Lab/Praktikum baru — **Dosen** (untuk dirinya sendiri) atau **Supervisor** (atas permintaan, wajib isi `dosen_id` terkait). Wajib pilih `mata_kuliah_id` dari data master yang sudah ada. Admin tidak memiliki akses ke endpoint ini |
| PATCH | `/api/kelas-lab/{id}` | Update jadwal/kuota — pemilik (`dosen_id`) atau Supervisor |
| DELETE | `/api/kelas-lab/{id}` | Hapus/batalkan kelas — pemilik (`dosen_id`) atau Supervisor |
| POST | `/api/kelas-lab/{id}/daftar` | Mahasiswa mendaftar — dibuat status `menunggu`. Ditolak jika kuota penuh, sudah terdaftar di sesi tsb, sudah ambil sesi lain di mata kuliah yang sama, atau bentrok jadwal |
| DELETE | `/api/kelas-lab/{id}/daftar` | Mahasiswa membatalkan pendaftaran dirinya sendiri — **hanya saat status `menunggu`** (setelah disetujui harus lewat Dosen/Supervisor) |
| GET | `/api/kelas-lab/{id}/peserta` | List peserta satu sesi + status (pemilik kelas, Supervisor, Admin) |
| GET | `/api/kelas-lab/pendaftaran` | List pendaftaran untuk persetujuan — Dosen (kelas miliknya) / Supervisor (semua); filter opsional `?status=` |
| PATCH | `/api/kelas-lab/pendaftaran/{peserta}/approve` | Setujui pendaftaran — pemilik kelas (Dosen) atau Supervisor |
| PATCH | `/api/kelas-lab/pendaftaran/{peserta}/reject` | Tolak pendaftaran — pemilik kelas (Dosen) atau Supervisor |
| DELETE | `/api/kelas-lab/pendaftaran/{peserta}` | Keluarkan peserta dari kelas (mis. salah daftar) — pemilik kelas (Dosen) atau Supervisor |

### 5.8 Perangkat, Peminjaman & Perpanjangan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/perangkat` | List perangkat + status |
| POST | `/api/perangkat` | Tambah perangkat (Admin/Supervisor) |
| PATCH | `/api/perangkat/{id}` | Update data/status perangkat |
| DELETE | `/api/perangkat/{id}` | Hapus perangkat (Admin/Supervisor) — ditolak jika status bukan `tersedia` atau masih ada `peminjaman_perangkat` aktif |
| GET | `/api/peminjaman-perangkat` | List pengajuan (milik sendiri / semua untuk Admin/Supervisor) |
| POST | `/api/peminjaman-perangkat` | Ajukan peminjaman (Mahasiswa) |
| PATCH | `/api/peminjaman-perangkat/{id}/approve` | Setujui (Admin/Supervisor) |
| PATCH | `/api/peminjaman-perangkat/{id}/reject` | Tolak (Admin/Supervisor) |
| POST | `/api/peminjaman-perangkat/{id}/perpanjangan` | Ajukan perpanjangan (Mahasiswa) |
| PATCH | `/api/perpanjangan/{id}/approve` | Setujui perpanjangan (Admin/Supervisor) — otomatis memperbarui tanggal_kembali_rencana di peminjaman_perangkat induk |
| PATCH | `/api/perpanjangan/{id}/reject` | Tolak perpanjangan (Admin/Supervisor) |

### 5.9 Pengumpulan Tugas & Deadline Pertemuan (menggantikan Presensi)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/tugas` | List tugas — cakupan per-role: Mahasiswa → miliknya; Dosen → kelas yang diampu; Admin/Supervisor → semua |
| POST | `/api/tugas` | Kirim tugas (Mahasiswa, peserta `disetujui`) — body `kelas_lab_id`, `pertemuan` (1–16), `judul`, `tautan` (url). Satu tugas per pertemuan |
| DELETE | `/api/tugas/{tugas}` | Hapus tugas — pemilik (Mahasiswa) atau Admin/Supervisor |
| GET | `/api/kelas-lab/{kelasLab}/deadline` | List materi/deadline pertemuan sebuah kelas (semua role login) |
| PUT | `/api/kelas-lab/{kelasLab}/deadline/{pertemuan}` | Upsert materi &/atau deadline pertemuan — Dosen pengampu/Supervisor/Admin. Bila materi & deadline kosong keduanya → record dihapus |
| DELETE | `/api/kelas-lab/{kelasLab}/deadline/{pertemuan}` | Hapus materi & deadline pertemuan — Dosen pengampu/Supervisor/Admin |

> Endpoint `presensi` v1.0 (`/api/presensi/check-in`, `/check-out`, dst.) **sudah dihapus** seiring penggantian modul.

### 5.10 Sertifikasi (Katalog)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/sertifikasi` | List katalog sertifikasi |
| POST | `/api/sertifikasi` | Tambah entri (Admin/Supervisor) |
| PATCH | `/api/sertifikasi/{id}` | Update entri |
| DELETE | `/api/sertifikasi/{id}` | Hapus entri |

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
| GET | `/api/info-lab/{tipe}` | Ambil konten (beranda/visi_misi/kepala_lab/roadmap_kk) |
| PATCH | `/api/info-lab/{tipe}` | Update konten (Admin) |

### 5.13 Laporan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/report?from=&to=` | Data rekap (Admin/Supervisor); parameter `from` & `to` format `YYYY-MM-DD`, default 30 hari terakhir |
| GET | `/api/report/pdf?from=&to=` | Unduh file PDF rekap (Admin/Supervisor) |

**Struktur response `GET /api/report`** (field `data`):
- `periode` — `{ dari, sampai }` tanggal yang direkap
- `peminjaman_ruangan` — `{ total_pengajuan, total_disetujui, total_ditolak, total_menunggu }`
- `peminjaman_perangkat` — `{ total_pengajuan, total_disetujui, total_ditolak, total_dikembalikan }`
- `tugas` — `{ total_terkumpul, total_mahasiswa_unik, total_kelas }` (peminjaman & tugas dihitung berdasarkan `created_at`)

> Rekap presensi v1.0 pada bagian ini telah **digantikan rekap `tugas`** seiring penggantian modul.

### 5.14 Notifikasi In-App
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/notifikasi` | List semua notifikasi milik user yang login, urut dari terbaru; response menyertakan `unread_count` |
| PATCH | `/api/notifikasi/{id}/read` | Tandai satu notifikasi sebagai sudah dibaca |
| PATCH | `/api/notifikasi/read-all` | Tandai semua notifikasi milik user sebagai sudah dibaca |
| DELETE | `/api/notifikasi/{id}` | Hapus satu notifikasi milik sendiri |

**Aturan akses**: semua role mengakses notifikasi milik sendiri saja. Pembuatan notifikasi dilakukan internal oleh backend (bukan endpoint publik), dipanggil via Service/Observer dalam transaksi DB yang sama dengan aksi pemicunya.

### 5.15 Rekap Tugas Kelas Lab
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/rekap-tugas` | Rekap JSON (ringkasan semua kelas + matriks detail per kelas) — Admin/Supervisor/Dosen |
| GET | `/api/rekap-tugas/pdf` | Unduh PDF rekap (landscape) — Admin/Supervisor/Dosen |
| GET | `/api/rekap-tugas/excel` | Unduh Excel `.xlsx` berformat (sheet Ringkasan + satu sheet per kelas) — Admin/Supervisor/Dosen |

**Aturan akses**: Gate `view-rekap-tugas` (Admin/Supervisor/Dosen). **Dosen di-scope otomatis** hanya ke kelas yang `dosen_id`-nya miliknya; Admin/Supervisor melihat semua kelas. Data dihitung on-request (selalu mencerminkan tugas terbaru — tanpa snapshot tersimpan).

**Struktur response `GET /api/rekap-tugas`** (field `data`):
- `generated_at` — waktu rekap dibuat (WIB)
- `ringkasan[]` — satu baris per kelas: `{ kelas_lab_id, mata_kuliah, nama_sesi, dosen, hari, jam, peserta_disetujui, total_tugas, pertemuan_bertugas, pertemuan_berjalan, tunggakan, perlu_perhatian, status ('perhatian'|'berjalan'|'beres'), deadline_terdekat }`
- `detail[]` — per kelas: `{ kelas_lab_id, mata_kuliah, nama_sesi, dosen, hari, jam, pertemuan[], peserta[] }`
  - `pertemuan[]` — kolom matriks (hanya pertemuan yang punya deadline): `{ pertemuan, materi, deadline }`
  - `peserta[]` — baris matriks (peserta status `disetujui`): `{ npm, nama, prodi, sel{ <pertemuan>: { status ('tepat'|'telat'|'belum'), judul, tautan, dikumpulkan } }, total_kumpul, telat }`
  - Sel `tepat`/`telat` ditentukan dari `tugas.created_at` vs `deadline_pertemuan.deadline`.

> Implementasi: `RekapTugasService` (agregasi, dipakai bersama endpoint `/kelas-lab/rekap-tugas` untuk badge kepatuhan), `RekapTugasExcelWriter` (phpspreadsheet), Blade `reports/rekap-tugas.blade.php` (dompdf).
> **Rencana lanjutan (Tahap 2)**: sinkronisasi otomatis ke Google Sheets (service account) — ditunda hingga kredensial GCP tersedia.

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
