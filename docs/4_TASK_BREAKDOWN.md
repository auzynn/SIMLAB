# 4. Task Breakdown

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Versi Dokumen**: 1.0
**Dokumen Acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`

> Dokumen ini adalah **rencana kerja/backlog** yang dieksekusi semua AI Agent (Hermes, Roo Code, Kilo Code, dll). Setiap task mencantumkan rujukan ke dokumen sumber (PRD/SRS/SDD) agar AI Agent membaca konteks yang tepat sebelum mengerjakan — lihat `.clinerules/agent.md` Bagian 9 (Workflow Kerja Standar).
>
> **Cara update status**: ubah `[ ]` menjadi `[x]` setelah task selesai **dan** test relevan lulus. AI Agent dilarang menandai selesai sebelum keduanya terpenuhi (lihat `agent.md` Bagian 6).

---

## Catatan Progres (per 2026-06-28)

**Backend (`src/backend`)**:
- Sanctum token auth fungsional: `POST /api/auth/login` (cek NULL password, pesan eksplisit sesuai SRS UC-01), `GET /api/auth/me`, `POST /api/auth/logout` — sudah sesuai SDD 5.1.
- Migration `users` lengkap sesuai SDD 3.1 (kolom `google_id`, `avatar`, `role` enum, `password` nullable).
- Migration `dosen` (SDD 3.2) & `mahasiswa` (SDD 3.3) sudah ada: `user_id` unique, `mahasiswa.npm` unique, `angkatan` char(4), `dosen_pembimbing_id` FK `nullOnDelete`.
- Model `User`, `Dosen`, `Mahasiswa` + relasi Eloquent lengkap (`User::dosen()/mahasiswa()`, `Dosen::mahasiswaBimbingan()`, `Mahasiswa::dosenPembimbing()`).
- `laravel/socialite ^5.28` terpasang, `config/services.php` punya blok `google` (client id/secret/redirect) & `frontend.url`.
- Google OAuth: `GET /api/auth/google/redirect` & `/callback` jalan — validasi domain (`@student.unsil.ac.id`→mahasiswa, `@unsil.ac.id`→dosen), find-or-create `users`, terbitkan Sanctum token, redirect ke frontend. **Auto-create profil `dosen`/`mahasiswa` + ekstraksi NPM/angkatan sudah AKTIF** (`createRoleProfile()` dipanggil saat registrasi pertama; `ensureRoleProfile()` backfill profil untuk akun lama yang belum punya — keduanya idempotent via `firstOrCreate`).
- **Profil Akun (semua role)**: `POST /api/auth/avatar` (unggah foto, validasi `image|mimes:jpeg,jpg,png,webp|max:2048`, simpan disk publik `avatars/<uuid>`, hapus avatar lokal lama) & `PATCH /api/auth/profile` (edit `name`/`no_telp`; dosen +`nidn`; mahasiswa +`prodi`; `email`/`role`/`npm`/`angkatan` immutable). Kolom `no_telp` ditambahkan ke `users` (migration `add_no_telp_to_users`). Diuji `AvatarTest` (3 test). _**Bidang Minat** (master banyak-banyak `dosen ↔ bidang_minat`) aktif: panel admin (Gate `manage-bidang-minat`), dipilih dosen via `bidang_minat_ids[]` di Edit Profil, tampil di Profil & Detail Dosen. Penamaan konsisten `bidang_minat` di semua lapisan — lihat `3_SDD.md` 3.2a._
- `DatabaseSeeder` membuat akun Admin & Supervisor sesuai SDD Bagian 2.
- `laravel/sanctum` terpasang, `config/cors.php` dikonfigurasi (`supports_credentials: true`, origin dari env `FRONTEND_URL`).
- **Kelola User (Admin)**: `UserController` CRUD (`/api/users`) + Form Request (`StoreUserRequest`/`UpdateUserRequest`) + Gate `manage-users`; create role `dosen` otomatis membuat profil `dosen`; `destroy` menolak hapus akun sendiri. Diuji `UserManagementTest` (5 test lulus).
- **Info Lab (FASE 2)**: migration + model `InfoLab`, seeder 4 tipe (`InfoLabSeeder` di `DatabaseSeeder`), endpoint `GET` (publik) & `PATCH` (Admin via Gate `manage-info-lab`) dengan constraint enum `tipe`. Diuji `InfoLabTest`. _Seeder kini berisi konten nyata (bukan placeholder): `kepala_lab` (foto `frontend/public/nur-widiyasono.jpg` + tabel profil markdown), `visi_misi` (Visi + 8 poin Misi). Konten tetap dapat disunting Admin lewat panel._
- **Dosen (FASE 2)**: tabel `dosen` diperluas (migration `add_profile_fields_to_dosen`: jenis_kelamin, jabatan_fungsional, tempat_lahir, tanggal_lahir, biografi) agar Detail Dosen sepadan situs lama. `DosenController` (index/show **publik**, update via `DosenPolicy` pemilik/Admin/Supervisor) + `UpdateDosenRequest`. `DosenSeeder` menyalin profil dari situs lama (1 dosen nyata: Ir. Nur Widiyasono). Diuji `DosenTest` (8 test).
- **Belum ada**: Gate/Policy modul lain, seluruh modul FASE 3–9.

**Frontend (`src/frontend/app-labriset`)**:
- Vue 3 + Vite, Vue Router 4, Pinia, Axios — semua terpasang. Struktur folder lengkap (`components/`, `views/`, `stores/`, `services/`, `router/`, `composables/`).
- `services/api.js`: Axios instance + interceptor Bearer token otomatis dari localStorage, base URL dari `VITE_API_BASE_URL`.
- `stores/auth.js`: login, loginWithToken (menerima token jadi dari callback Google), logout, fetchUser — token disimpan di localStorage, state `user`/`isAuthenticated`.
- `router/index.js`: navigation guard RBAC (`requiresAuth`/`roles`), restore sesi saat refresh halaman, redirect sudah-login ke beranda; route `/auth/callback` terdaftar.
- `views/login-page.vue`: form login email+password terhubung ke `authStore.login()`; tombol "Login dengan UNSIL Mail" redirect ke `/api/auth/google/redirect`, plus penanganan & tampilan pesan error OAuth dari query string.
- `views/auth-callback.vue`: penerima redirect Google OAuth — ambil token dari query, simpan via `authStore.loginWithToken()`, muat data user, lalu arahkan ke tujuan semula (atau balik ke login dengan pesan error bila gagal).
- `views/profil-page.vue` (route `/profil`, `requiresAuth`): kartu identitas (avatar Google/inisial, nama, email, peran; NPM/angkatan/prodi untuk mahasiswa, NIDN/bidang minat untuk dosen) + **form Edit Profil** (nama, no_telp; dosen +NIDN; mahasiswa +prodi — via `authService.updateProfile()`) + **ganti foto profil** (unggah file via `authService.updateAvatar()`) + form password kondisional — "Atur Password Login" bila `has_password=false`, "Ubah Password" (wajib password lama) bila sudah ada. Terhubung ke `authService.setPassword()`/`changePassword()`, lalu `fetchUser()` untuk segarkan state. Link "Profil Saya" muncul di header saat login.
- Backend pendukung: `me()` kini eager-load relasi `dosen.bidangMinat`/`mahasiswa`; model `User` mengekspos flag `has_password` agar frontend memilih form yang tepat, dan `no_telp` masuk `fillable`.
- Halaman info lab **tersambung API** lewat `composables/use-info-lab.js` + `components/markdown-content.vue` (render Markdown, dep `marked`): Beranda, Visi-Misi, Profil Kepala Lab, Roadmap Lab membaca `GET /api/info-lab/{tipe}`. _`markdown-content.vue` menata tabel profil (kepala lab) & daftar: penanda daftar berupa panah kanan dua warna (kuning atas, biru `--bs-navy` bawah) via `li::before` + `background-clip:text`, sejajar judul — menyamai tampilan halaman statis lama._
- **Dosen tersambung API**: `views/list-dosen.vue` (Daftar Dosen) & `views/detail-dosen.vue` (Biografi/Detail) kini dinamis via `services/dosen.js` (`GET /api/dosen`, `GET /api/dosen/{id}`); route `/detaildosen/:id`; tabel bio (3 kolom rapi) + biografi + **Bidang Minat** (gabungan nama relasi) dirender dari data, `tanggal_lahir` diformat ke teks Indonesia. `sidemenu-dosen.vue`: tautan Biografi mengikuti id aktif, sub-halaman (Credential/Publikasi/Buku/Roadmap) membawa konteks `?dosen=<id>`. **Roadmap Penelitian Dosen** (`roadmap-dosen.vue`) kini dinamis dari `dosen.roadmap_riset` — dibedakan dari **Roadmap Laboratorium** (`roadmap-lab.vue`, `info_lab.roadmap_kk`).
- **Panel Admin**: hub `/admin` (`admin-page.vue`) + `components/sidemenu-admin.vue`; **Kelola User** (`admin-users.vue`) & **Konten Info Lab** (`admin-info-lab.vue`) fungsional; link "Panel Admin" di header untuk role admin.
- **Belum ada**: modul admin lain (Data Master, Persetujuan Peminjaman, Sertifikasi, Presensi, Laporan), seluruh modul FASE 3–9.

---

## FASE 0: Fondasi Proyek

Task persiapan sebelum modul fitur apapun bisa dikerjakan.

- [x] **T0.1** — Inisialisasi project Laravel 13.16 di `src/backend` (`composer create-project laravel/laravel`), set PHP 8.5.7 di `composer.json`
- [x] **T0.2** — Inisialisasi project Vue 3 + Vite di `src/frontend` (`npm create vite@latest -- --template vue`)
- [x] **T0.3** — Konfigurasi koneksi MySQL di `src/backend/.env`
- [x] **T0.4** — Install & konfigurasi Laravel Sanctum untuk SPA authentication (SDD Bagian 1)
- [x] **T0.5** — Konfigurasi CORS (`config/cors.php`) agar backend menerima request dari origin frontend, `supports_credentials` aktif (SDD Bagian 1)
- [x] **T0.6** — Install & konfigurasi Laravel Socialite untuk Google OAuth (SDD Bagian 2)
- [x] **T0.7** — Install Vue Router & Pinia di frontend; setup struktur folder `components/`, `views/`, `stores/`, `services/`, `router/` (`agent.md` Bagian 5)
- [x] **T0.8** — Setup Axios instance di `src/frontend/src/services` dengan base URL dari `.env` (`VITE_API_BASE_URL`)
- [x] **T0.9** — Install Laravel Pint (backend) & Prettier/ESLint (frontend), verifikasi `format on save` di `.vscode/settings.json` berjalan
- [x] **T0.10** — Setup PHPUnit/Pest config dasar di `src/backend/tests`

---

## FASE 1: Autentikasi & Manajemen User

Fondasi yang harus selesai sebelum modul lain bisa diuji end-to-end (hampir semua modul butuh user yang sudah login).

### Backend
- [x] **T1.1** — Migration tabel `users` sesuai SDD 3.1 (kolom `google_id`, `avatar`, `role` enum, `password` nullable)
- [x] **T1.2** — Migration tabel `dosen` sesuai SDD 3.2 (relasi `user_id` wajib unique)
- [x] **T1.3** — Migration tabel `mahasiswa` sesuai SDD 3.3 (kolom `npm`, `angkatan`, `dosen_pembimbing_id` FK -> dosen.id — lihat aturan auto-extract & bimbingan)
- [x] **T1.4** — Model `User`, `Dosen`, `Mahasiswa` + relasi Eloquent (`User::dosen()`, `User::mahasiswa()`, `Mahasiswa::dosenPembimbing()`, dst.)
- [x] **T1.5** — Endpoint `GET /api/auth/google/redirect` & `GET /api/auth/google/callback` — implementasi alur SDD Bagian 2 lengkap: validasi domain email, auto-create `users` + `dosen`/`mahasiswa`, ekstraksi NPM & angkatan (format `"20" . dua_digit_awal`). _Catatan: `createRoleProfile()` kini **aktif** (dipanggil saat registrasi pertama) + `ensureRoleProfile()` (backfill akun lama), keduanya idempotent via `firstOrCreate`. Tabel `dosen`/`mahasiswa` sudah ada (T1.2/T1.3). Test otomatis (T1.18–T1.20) belum dibuat._
- [x] **T1.6** — Endpoint `POST /api/auth/login` (login manual) — tolak jika `password` NULL, dengan pesan sesuai SRS UC-01 skenario 1b
- [x] **T1.7** — Endpoint `POST /api/auth/set-password` & `PATCH /api/auth/change-password` (SRS UC-01b). _set-password hanya untuk akun ber-password NULL (tanpa password lama); change-password wajib `current_password` cocok. Validasi `min:8` + `confirmed`; flag `has_password` diekspos via model `User` dan `me()` eager-load profil._
- [x] **T1.7a** — Migration `add_no_telp_to_users` (kolom `no_telp` varchar(32) nullable) + endpoint `PATCH /api/auth/profile` (Edit Profil akun sendiri, SDD 5.1): field `name`/`no_telp` (semua role), `nidn` (dosen), `prodi` (mahasiswa, whitelist `Informatika`); `email`/`role`/`npm`/`angkatan` **immutable** (ditolak di backend). _Field `bidang_minat_ids[]` (dosen) di-`sync` ke pivot Bidang Minat (master aktif) — lihat SDD 3.2a._
- [x] **T1.7b** — Endpoint `POST /api/auth/avatar` (unggah/ganti foto akun sendiri, SDD 5.1): validasi `image|mimes:jpeg,jpg,png,webp|max:2048`, simpan disk publik `avatars/<uuid>`, kolom `avatar` diisi URL absolut, avatar lokal lama dihapus (avatar Google eksternal dibiarkan). Diuji `AvatarTest`.
- [x] **T1.8** — Endpoint `POST /api/auth/logout` & `GET /api/auth/me`
- [x] **T1.9** — Seeder `UserSeeder` untuk membuat akun Admin & Supervisor manual (SDD Bagian 2, catatan implementasi)
- [ ] **T1.10** — Policy dasar untuk role-based access control mengacu matriks RBAC SRS Bagian 1. _Catatan: fondasi via Gate sudah ada & diuji — `manage-users` & `manage-info-lab` (Admin only) di `AppServiceProvider`. Gate/Policy modul lain (ruangan/perangkat/dosen/dst.) menyusul saat fasenya dikerjakan._
- [x] **T1.11** — Endpoint `GET /api/users`, `POST /api/users`, `PATCH /api/users/{id}`, `DELETE /api/users/{id}` (Admin only). _`UserController` (apiResource only index/store/update/destroy), otorisasi Gate `manage-users`; create user role `dosen` otomatis membuat profil `dosen` (invarian SDD 3.2); `destroy` menolak hapus akun sendiri. Diuji di `UserManagementTest`._
- [x] **T1.12** — Form Request validasi untuk seluruh endpoint di atas (`StoreUserRequest`, `UpdateUserRequest`)

### Frontend
- [x] **T1.13** — Halaman Login: tombol "Login dengan Google" + form "Login dengan Email & Password" (form fungsional, terhubung ke `authStore.login()`; tombol Google redirect ke `/api/auth/google/redirect`, callback ditangani `views/auth-callback.vue`)
- [x] **T1.14** — Halaman Profil (`views/profil-page.vue`, route `/profil`): kartu identitas + **form Edit Profil** (nama, no_telp; dosen +NIDN; mahasiswa +prodi) + **ganti foto profil** (unggah file) + form "Atur Password" / "Ubah Password" kondisional sesuai flag `has_password`; link "Profil Saya" di header saat login. Terhubung `authService.updateProfile()`/`updateAvatar()`/`setPassword()`/`changePassword()`
- [x] **T1.15** — Pinia store `auth` — menyimpan token (localStorage), data user, role; dipakai global untuk proteksi route
- [x] **T1.16** — Vue Router navigation guard — `beforeEach` dengan meta `requiresAuth`/`roles`, restore sesi saat refresh, redirect pasca-login. _Catatan: redirect ke dashboard per role menunggu halaman dashboard masing-masing role dibuat._
- [x] **T1.17** — Halaman Kelola User (Admin only) — list, edit role, hapus user. _`views/admin-users.vue` (route `/admin/users`, guard `roles: ['admin']`): tabel user + filter role + form tambah/edit + hapus (tombol hapus disembunyikan untuk akun sendiri). Plus halaman hub Panel Admin `views/admin-page.vue` (`/admin`) & `components/sidemenu-admin.vue`; link "Panel Admin" di header untuk role admin._

### Testing
- [ ] **T1.18** — Test: domain email non-UNSIL ditolak saat login Google
- [ ] **T1.19** — Test: login Google pertama kali membuat `users` + entri `dosen`/`mahasiswa` otomatis sesuai role
- [ ] **T1.20** — Test: ekstraksi NPM dan angkatan dari email mahasiswa menghasilkan nilai yang benar
- [ ] **T1.21** — Test: login manual ditolak jika `password` masih NULL
- [ ] **T1.22** — Test: field `npm` dan `angkatan` tidak bisa diubah lewat endpoint update profil mahasiswa
- [x] **T1.23** — Test: unggah avatar (`AvatarTest`) — user dapat unggah gambar, file non-gambar ditolak (422), endpoint butuh login (401)

---

## FASE 2: Halaman Informasi Lab

Modul tampilan informasi publik (PRD 2.5, SDD 3.15).

### Backend
- [x] **T2.1** — Migration tabel `info_lab` (SDD 3.15)
- [x] **T2.2** — Model `InfoLab`
- [x] **T2.3** — Endpoint `GET /api/info-lab/{tipe}` & `PATCH /api/info-lab/{tipe}` (Admin only untuk update). _`InfoLabController` (`show` publik, `update` via Gate `manage-info-lab`, upsert by `tipe`); constraint enum `tipe` di route; diuji `InfoLabTest`._
- [x] **T2.4** — Seeder data awal untuk tipe `beranda`, `visi_misi`, `kepala_lab`, `roadmap_kk` (`InfoLabSeeder`, dipanggil di `DatabaseSeeder`). _`kepala_lab` & `visi_misi` berisi konten nyata (foto + tabel profil; Visi + 8 Misi) sebagai markdown; `beranda` & `roadmap_kk` masih placeholder satu kalimat._
- [x] **T2.5** — Endpoint `GET /api/dosen` & `GET /api/dosen/{id}` (publik) dengan eager load relasi `user` (SDD 3.2 catatan penting). _`DosenController` index/show + route publik._
- [x] **T2.6** — Endpoint `PATCH /api/dosen/{id}` — update oleh pemilik (Dosen) atau Admin/Supervisor via `DosenPolicy`. _`UpdateDosenRequest`; `name`/`no_telp` ditulis ke `users`, sisanya ke `dosen`. Tabel `dosen` diperluas (jenis_kelamin, jabatan_fungsional, tempat_lahir, tanggal_lahir, biografi) agar Detail Dosen sepadan situs lama — lihat SDD 3.2._

### Frontend
- [x] **T2.7** — Halaman Beranda (tersambung `GET /api/info-lab/beranda`). _Konten dinamis (judul/gambar/markdown) di bawah jumbotron._
- [x] **T2.8** — Halaman Visi & Misi (tersambung `GET /api/info-lab/visi_misi`)
- [x] **T2.9** — Halaman Profil Kepala Lab (tersambung `GET /api/info-lab/kepala_lab`). _Foto (`info.gambar`) + judul (nama) + tabel profil markdown; data nyata di-seed, foto di `frontend/public/`._
- [x] **T2.10** — Halaman Daftar Dosen (list dari `GET /api/dosen`) + halaman Detail Profil Dosen (`GET /api/dosen/{id}`). _`list-dosen.vue` & `detail-dosen.vue` kini dinamis via `services/dosen.js`; route `/detaildosen/:id`; tabel bio + biografi dirender dari data (tanggal_lahir diformat Indonesia). `sidemenu-dosen.vue` Biografi mengikuti id aktif._
- [x] **T2.11** — Halaman Roadmap Laboratorium (tersambung `GET /api/info-lab/roadmap_kk`)
- [x] **T2.12** — Panel kelola konten info lab (Admin only). _`views/admin-info-lab.vue`: tab 4 tipe + form judul/gambar/konten, terhubung `infoLabService`. Shared: `composables/use-info-lab.js` + `components/markdown-content.vue` (dep `marked`)._

### Testing
- [x] **T2.13** — Test: hanya Admin yang bisa update `info_lab` dan data dosen milik orang lain. _`info_lab` diuji `InfoLabTest`; bagian dosen diuji `DosenTest` (8 test): baca publik, pemilik & Admin/Supervisor bisa update, dosen lain/mahasiswa 403, guest 401._

---

## FASE 3: Peminjaman Ruangan Lab, Mata Kuliah & Kelas Lab/Praktikum

(PRD 3.3, 3.3a — SRS UC-02, UC-02a — SDD 3.4, 3.5, 3.6, 3.7, 3.8)

> Fase ini dikerjakan sebagai satu kesatuan karena `kelas_lab` dan `peminjaman_ruangan` saling bergantung dalam validasi bentrok jadwal — migration dan logika validasinya harus ada bersamaan sebelum salah satu bisa diuji secara penuh.

### Backend — Mata Kuliah (Data Master)
- [ ] **T3.1** — Migration tabel `mata_kuliah` (SDD 3.6)
- [ ] **T3.2** — Model `MataKuliah`
- [ ] **T3.3** — Endpoint CRUD `/api/mata-kuliah` (Admin/Supervisor); `GET /api/mata-kuliah` bisa diakses semua role (dipakai Dosen saat memilih saat membuka Kelas Lab — SRS F-DS-07)

### Backend — Ruangan & Peminjaman Ruangan
- [ ] **T3.4** — Migration tabel `ruangan` (SDD 3.4)
- [ ] **T3.5** — Migration tabel `peminjaman_ruangan` (SDD 3.5)
- [ ] **T3.6** — Model `Ruangan`, `PeminjamanRuangan` + relasi
- [ ] **T3.7** — Endpoint CRUD `/api/ruangan` (Admin/Supervisor)
- [ ] **T3.8** — Endpoint `GET /api/peminjaman-ruangan/kalender` — data ketersediaan gabungan: peminjaman disetujui + jadwal `kelas_lab` aktif, untuk tampilan kalender frontend
- [ ] **T3.9** — Endpoint `POST /api/peminjaman-ruangan` — Form Request **wajib** validasi: status ruangan adalah 'tersedia', dan validasi bentrok terhadap dua sumber sekaligus: (1) `peminjaman_ruangan` berstatus `disetujui`, dan (2) `kelas_lab` aktif pada ruangan + tanggal + rentang jam yang sama (SRS UC-02 aturan validasi kunci)
- [ ] **T3.10** — Endpoint `PATCH /api/peminjaman-ruangan/{id}/approve` & `/reject` — saat approve, backend **wajib** menjalankan ulang validasi bentrok (kondisi bisa berubah antara saat pengaju submit dan saat Supervisor approve) serta memastikan status ruangan masih 'tersedia'
- [ ] **T3.11** — Endpoint `GET /api/peminjaman-ruangan` — filter milik sendiri vs semua (sesuai role)

### Backend — Kelas Lab/Praktikum
- [ ] **T3.12** — Migration tabel `kelas_lab` (SDD 3.7)
- [ ] **T3.13** — Migration tabel `kelas_lab_peserta` (SDD 3.8)
- [ ] **T3.14** — Model `KelasLab`, `KelasLabPeserta` + relasi (`belongsTo MataKuliah`, `belongsTo Dosen`, `hasMany KelasLabPeserta`)
- [ ] **T3.15** — Endpoint `GET /api/kelas-lab` — list semua sesi, support filter `?mata_kuliah_id=` untuk menampilkan semua sesi paralel suatu mata kuliah (SDD 5.7)
- [ ] **T3.16** — Endpoint `GET /api/kelas-lab/{id}` — detail satu sesi, termasuk sisa kuota (`kuota - COUNT(kelas_lab_peserta)`)
- [ ] **T3.17** — Endpoint `POST /api/kelas-lab` — **Dosen** (untuk dirinya sendiri) atau **Supervisor** (wajib sertakan `dosen_id` valid). Admin **dilarang** — implementasi via Policy, bukan cuma kondisi `if`. Form Request **wajib**: validasi `mata_kuliah_id` ada di data master, `kuota` dalam range 1–40, tidak ada bentrok jadwal ruangan (SRS UC-02a aturan validasi kunci)
- [ ] **T3.18** — Endpoint `PATCH /api/kelas-lab/{id}` & `DELETE /api/kelas-lab/{id}` — hanya pemilik (`dosen_id`) atau Supervisor
- [ ] **T3.19** — Endpoint `POST /api/kelas-lab/{id}/daftar` (Mahasiswa mendaftar) — Form Request **wajib** validasi: (1) kuota belum penuh, (2) mahasiswa belum terdaftar di sesi yang sama (SRS UC-02a)
- [ ] **T3.20** — Endpoint `DELETE /api/kelas-lab/{id}/daftar` (Mahasiswa batalkan pendaftaran)
- [ ] **T3.21** — Endpoint `GET /api/kelas-lab/{id}/peserta` — hanya pemilik kelas, Supervisor, Admin

### Frontend — Mata Kuliah
- [ ] **T3.22** — Panel kelola data mata kuliah (Admin/Supervisor): list, tambah, edit, hapus

### Frontend — Ruangan & Peminjaman Ruangan
- [ ] **T3.23** — Halaman Kalender Ketersediaan Ruangan — tampilan kalender yang menggabungkan slot `peminjaman_ruangan` disetujui + slot `kelas_lab` aktif (dibedakan secara visual, mis. warna berbeda)
- [ ] **T3.24** — Form Pengajuan Peminjaman Ruangan (Mahasiswa/Dosen) — slot yang sudah terisi `kelas_lab` tidak bisa dipilih
- [ ] **T3.25** — Halaman Daftar Pengajuan + tombol Approve/Reject (Admin/Supervisor)
- [ ] **T3.26** — Panel kelola data ruangan (Admin/Supervisor)
- [ ] **T3.27** — Halaman "Peminjaman Saya" — status pengajuan milik mahasiswa/dosen

### Frontend — Kelas Lab/Praktikum
- [ ] **T3.28** — Halaman Kelas Lab/Praktikum — list semua mata kuliah + sesi paralel yang tersedia, termasuk sisa kuota tiap sesi
- [ ] **T3.29** — Tombol "Daftar" / "Batalkan Pendaftaran" per sesi (Mahasiswa)
- [ ] **T3.30** — Form buka Kelas Lab baru (Dosen/Supervisor): pilih mata kuliah dari dropdown, isi ruangan, hari, jam, tanggal semester, kuota, nama sesi (mis. "Kelas A")
- [ ] **T3.31** — Halaman kelola Kelas Lab milik Dosen (edit jadwal, lihat peserta, hapus)

### Testing
- [ ] **T3.32** — Test: pengajuan `peminjaman_ruangan` pada slot yang sudah ada `kelas_lab` aktif → ditolak (SRS UC-02 skenario alternatif)
- [ ] **T3.33** — Test: pengajuan `peminjaman_ruangan` pada slot kosong (di luar `kelas_lab`) → diterima
- [ ] **T3.34** — Test: pembukaan `kelas_lab` baru yang bentrok dengan `kelas_lab` atau `peminjaman_ruangan` lain yang sudah disetujui → ditolak (SRS UC-02a)
- [ ] **T3.35** — Test: pendaftaran peserta melebihi kuota → ditolak sistem
- [ ] **T3.36** — Test: mahasiswa yang sudah terdaftar di sesi yang sama tidak bisa mendaftar dua kali
- [ ] **T3.37** — Test: Admin tidak bisa membuka Kelas Lab (endpoint mengembalikan 403)
- [ ] **T3.38** — Test: Mahasiswa/Dosen hanya bisa melihat pengajuan peminjaman miliknya sendiri

---

## FASE 4: Inventaris & Peminjaman Perangkat

(PRD 3.4, SRS UC-03, SDD 3.9, 3.10, 3.11)

### Backend
- [ ] **T4.1** — Migration tabel `perangkat` (SDD 3.9)
- [ ] **T4.2** — Migration tabel `peminjaman_perangkat` (SDD 3.10)
- [ ] **T4.3** — Migration tabel `perpanjangan_peminjaman` (SDD 3.11)
- [ ] **T4.4** — Model `Perangkat`, `PeminjamanPerangkat`, `PerpanjanganPeminjaman` + relasi
- [ ] **T4.5** — Endpoint CRUD `/api/perangkat` (Admin/Supervisor)
- [ ] **T4.6** — Endpoint `POST /api/peminjaman-perangkat` (Mahasiswa saja — SRS Bagian 1)
- [ ] **T4.7** — Endpoint `PATCH /api/peminjaman-perangkat/{id}/approve` & `/reject`
- [ ] **T4.8** — Endpoint `POST /api/peminjaman-perangkat/{id}/perpanjangan` — **wajib** validasi tanggal kembali rencana belum lewat (SRS UC-03 aturan validasi kunci)
- [ ] **T4.9** — Endpoint `PATCH /api/perpanjangan/{id}/approve` & `/reject` — saat approve, backend wajib memperbarui `tanggal_kembali_rencana` pada `peminjaman_perangkat` induk secara otomatis

### Frontend
- [ ] **T4.10** — Halaman Daftar Perangkat (status Tersedia/Dipinjam/Perbaikan)
- [ ] **T4.11** — Form Pengajuan Peminjaman Perangkat (Mahasiswa)
- [ ] **T4.12** — Halaman "Peminjaman Saya" — termasuk tombol Ajukan Perpanjangan
- [ ] **T4.13** — Halaman Approve/Reject Peminjaman & Perpanjangan (Admin/Supervisor)
- [ ] **T4.14** — Panel kelola data perangkat (Admin/Supervisor)

### Testing
- [ ] **T4.15** — Test: pengajuan perpanjangan ditolak jika diajukan setelah `tanggal_kembali_rencana` lewat
- [ ] **T4.16** — Test: hanya Mahasiswa yang bisa mengajukan peminjaman perangkat (Dosen ditolak)

---

## FASE 5: Presensi Laboratorium

(PRD 3.5, SRS UC-04, SDD 3.12)

### Backend
- [ ] **T5.1** — Migration tabel `presensi` (SDD 3.12)
- [ ] **T5.2** — Model `Presensi` + relasi
- [ ] **T5.3** — Endpoint `POST /api/presensi/check-in` — **wajib** validasi tidak ada sesi `check_out IS NULL` aktif milik user yang sama (SRS UC-04 aturan validasi kunci)
- [ ] **T5.4** — Endpoint `PATCH /api/presensi/{id}/check-out` — set timestamp WIB
- [ ] **T5.5** — Endpoint `GET /api/presensi` — filter milik sendiri / mahasiswa bimbingan (Dosen) / rekap (Admin-Supervisor)
- [ ] **T5.6** — Endpoint `PATCH /api/presensi/{id}` & `DELETE /api/presensi/{id}` (Dosen, untuk mahasiswa bimbingan — set `dicatat_oleh`)

### Frontend
- [ ] **T5.7** — Tombol/Halaman Check-in (pilih keperluan riset) & Check-out
- [ ] **T5.8** — Halaman Riwayat Presensi (Mahasiswa: milik sendiri; Dosen: mahasiswa bimbingan; Admin/Supervisor: rekap)

### Testing
- [ ] **T5.9** — Test: check-in kedua ditolak selama sesi sebelumnya belum check-out
- [ ] **T5.10** — Test: timestamp presensi tersimpan sesuai waktu lokal WIB

---

## FASE 6: Katalog Sertifikasi (Informasional)

(PRD 3.6, SRS UC-05, SDD 3.13)

### Backend
- [ ] **T6.1** — Migration tabel `sertifikasi` (SDD 3.13 — murni katalog, tanpa relasi ke `users`)
- [ ] **T6.2** — Model `Sertifikasi`
- [ ] **T6.3** — Endpoint CRUD `/api/sertifikasi` (Create/Update/Delete: Admin/Supervisor; Read: semua role)

### Frontend
- [ ] **T6.4** — Halaman Katalog Sertifikasi (list + detail, dengan tautan eksternal ke penyelenggara)
- [ ] **T6.5** — Panel kelola katalog sertifikasi (Admin/Supervisor)

### Testing
- [ ] **T6.6** — Test: Mahasiswa hanya bisa Read, tidak bisa Create/Update/Delete katalog sertifikasi

---

## FASE 7: Portofolio Mahasiswa

(PRD 3.7, SDD 3.14)

### Backend
- [ ] **T7.1** — Migration tabel `portofolio` (SDD 3.14)
- [ ] **T7.2** — Model `Portofolio` + relasi ke `User`
- [ ] **T7.3** — Endpoint CRUD `/api/portofolio` — Create/Update/Delete hanya pemilik (Mahasiswa); Read semua role

### Frontend
- [ ] **T7.4** — Halaman Portofolio Pribadi (Mahasiswa — kelola milik sendiri)
- [ ] **T7.5** — Halaman Lihat Portofolio (publik untuk semua role yang login)

### Testing
- [ ] **T7.6** — Test: Mahasiswa tidak bisa edit/hapus portofolio milik mahasiswa lain

---

## FASE 8: Laporan (Report)

(PRD 3.9, SRS UC-06)

### Backend
- [ ] **T8.1** — Endpoint `GET /api/report?from=&to=` — agregasi data peminjaman, presensi, aktivitas lab
- [ ] **T8.2** — Endpoint `GET /api/report/pdf?from=&to=` — generate PDF (gunakan package PDF generator Laravel, mis. `barryvdh/laravel-dompdf` — **konfirmasi ke user sebelum install dependency baru**, sesuai `agent.md` Bagian 3)

### Frontend
- [ ] **T8.3** — Halaman Report — filter rentang tanggal, tampilan rekap, tombol Download PDF

### Testing
- [ ] **T8.4** — Test: hanya Admin/Supervisor yang bisa mengakses endpoint report

---

## FASE 9: Notifikasi In-App

(PRD 3.10, SRS UC-07, SDD 3.16, 5.14)

> Fase ini **tidak berdiri sendiri** — notifikasi adalah efek samping dari aksi di Fase 3, 4, dan 5. Migration dan Model dikerjakan di sini, tapi integrasi insert notifikasi ke transaksi masing-masing modul dikerjakan bersamaan saat fase tersebut dieksekusi (atau sebagai pass kedua setelah fase terkait selesai).

### Backend
- [ ] **T9.1** — Migration tabel `notifikasi` (SDD 3.16): kolom `user_id`, `judul`, `pesan`, `tipe` (enum), `referensi_id`, `is_read`; composite index `(user_id, is_read)`
- [ ] **T9.2** — Model `Notifikasi` + relasi `belongsTo User`
- [ ] **T9.3** — `NotifikasiService` — class reusable dengan method `kirim(userId, judul, pesan, tipe, referensiId)` yang melakukan insert dalam transaksi yang sudah berjalan; dipanggil dari dalam transaksi modul lain
- [ ] **T9.4** — Integrasi di modul Peminjaman Ruangan: panggil `NotifikasiService::kirim()` di dalam transaksi approve/reject `peminjaman_ruangan` — kirim ke pengaju; dan di dalam transaksi `POST /api/peminjaman-ruangan` — kirim ke semua Supervisor & Admin (tipe: `pengajuan_masuk`)
- [ ] **T9.5** — Integrasi di modul Peminjaman Perangkat: panggil `NotifikasiService::kirim()` di dalam transaksi approve/reject `peminjaman_perangkat` — kirim ke pengaju; dan saat pengajuan baru masuk — kirim ke semua Supervisor & Admin
- [ ] **T9.6** — Integrasi di modul Perpanjangan: panggil `NotifikasiService::kirim()` di dalam transaksi approve/reject `perpanjangan_peminjaman` — kirim ke pengaju; dan saat pengajuan baru masuk — kirim ke semua Supervisor & Admin
- [ ] **T9.7** — Integrasi di modul Kelas Lab: panggil `NotifikasiService::kirim()` di dalam transaksi `POST /api/kelas-lab/{id}/daftar` (konfirmasi pendaftaran berhasil — kirim ke Mahasiswa yang mendaftar)
- [ ] **T9.8** — Endpoint `GET /api/notifikasi` — list notifikasi milik sendiri, urut terbaru, response sertakan `unread_count` (SDD 5.14)
- [ ] **T9.9** — Endpoint `PATCH /api/notifikasi/{id}/read` — tandai satu notifikasi sebagai sudah dibaca (validasi: hanya milik sendiri)
- [ ] **T9.10** — Endpoint `PATCH /api/notifikasi/read-all` — tandai semua notifikasi milik sendiri sebagai sudah dibaca
- [ ] **T9.11** — Endpoint `DELETE /api/notifikasi/{id}` — hapus satu notifikasi milik sendiri
- [ ] **T9.12** — Update `GET /api/auth/me`: tambahkan field `unread_notifications_count` (COUNT `notifikasi` milik user dengan `is_read = 0`) ke response — dipakai badge navbar tanpa request tambahan (SRS UC-07 aturan validasi kunci, SDD 5.1)

### Frontend
- [ ] **T9.13** — Komponen `NotificationBell` di navbar: ikon lonceng + badge angka merah jika `unread_notifications_count > 0`; nilai badge diambil dari response `GET /api/auth/me` saat pertama load
- [ ] **T9.14** — Dropdown/panel notifikasi: muncul saat lonceng diklik, list notifikasi dari `GET /api/notifikasi`; notifikasi belum dibaca ditandai secara visual (mis. background berbeda)
- [ ] **T9.15** — Tombol "Tandai Sudah Dibaca" per item + "Tandai Semua" + tombol hapus per item
- [ ] **T9.16** — Pinia store `notifikasi` — menyimpan list & `unread_count`; diupdate setelah aksi tandai baca/hapus

### Testing
- [ ] **T9.17** — Test: approve `peminjaman_ruangan` membuat entri `notifikasi` untuk pengaju dan tidak untuk user lain
- [ ] **T9.18** — Test: pengajuan baru `peminjaman_ruangan` membuat notifikasi untuk semua Supervisor & Admin (bukan untuk pengaju itu sendiri)
- [ ] **T9.19** — Test: `GET /api/auth/me` mengembalikan `unread_notifications_count` yang akurat
- [ ] **T9.20** — Test: `PATCH /api/notifikasi/{id}/read` ditolak (403) jika notifikasi bukan milik sendiri
- [ ] **T9.21** — Test: jika transaksi approve rollback, insert notifikasi ikut rollback (tidak ada notifikasi orphan)

---

## Catatan Pengerjaan untuk AI Agent

1. **Urutan fase bersifat dependency, bukan kaku** — Fase 0 dan Fase 1 harus selesai duluan karena hampir semua modul butuh user & auth. Fase 2–9 secara teknis bisa dikerjakan paralel/diloncat sesuai prioritas, tapi disarankan urut karena beberapa relasi data saling bergantung (mis. Fase 4 & 5 sama-sama butuh data `users`/`mahasiswa` dari Fase 1).
2. **Setiap task backend yang menyentuh data sensitif (approve/reject, kelola user) wajib dicek ulang ke matriks RBAC di `2_SRS.md` Bagian 1** sebelum dianggap selesai.
3. Checklist test di tiap fase adalah **minimum**, bukan daftar lengkap — AI Agent boleh menambah test lain yang relevan selama tidak mengurangi yang sudah ada di sini.
