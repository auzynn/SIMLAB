# 4. Task Breakdown

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Versi Dokumen**: 1.0
**Dokumen Acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`

> Dokumen ini adalah **rencana kerja/backlog** yang dieksekusi semua AI Agent (Hermes, Roo Code, Kilo Code, dll). Setiap task mencantumkan rujukan ke dokumen sumber (PRD/SRS/SDD) agar AI Agent membaca konteks yang tepat sebelum mengerjakan — lihat `.clinerules/agent.md` Bagian 9 (Workflow Kerja Standar).
>
> **Cara update status**: ubah `[ ]` menjadi `[x]` setelah task selesai **dan** test relevan lulus. AI Agent dilarang menandai selesai sebelum keduanya terpenuhi (lihat `agent.md` Bagian 6).

---

## Catatan Progres (per 2026-07-17)

> **Sinkronisasi dokumen ↔ implementasi (v0.8.10)**: verifikasi terakhir **backend 207/207 test lulus (494 assertion)**. Penyelarasan yang dibukukan ke `2_SRS.md`/`3_SDD.md` (v1.2) & `docs/final/*`: (1) `sertifikasi.created_by` (FK → users, nullable) + `SertifikasiPolicy` — Dosen boleh Create dan kelola entri miliknya (menggantikan catatan lama FASE 6 "tanpa relasi ke users" & Gate `manage-master-data`); (2) peminjaman ruangan **berbasis kapasitas** (`ruangan.kapasitas` = jumlah peminjaman paralel; Kelas Lab memblok penuh) + status **`kadaluarsa`** saat approve menemukan slot penuh (lock `lockForUpdate` dalam transaksi); (3) `notifikasi.referensi_id` dipertegas **sengaja tanpa FK** (by design, lintas tabel); (4) endpoint yang terlewat didokumentasikan: `POST /api/auth/reset-password`, `POST /api/info-lab/upload`, `PATCH /api/peminjaman-perangkat/{id}/kembalikan`, `DELETE /api/peminjaman-perangkat/{id}`, `GET /api/kelas-lab/rekap-tugas`, plus catatan `KelasLabPolicy@view` (Mahasiswa hanya bisa buka detail kelas setelah `disetujui`).

## Catatan Progres (per 2026-07-09)

> **Ringkasan penyelarasan dokumen (2026-07-09)**: Seluruh FASE 0–9 tuntas. **Modul Presensi (Fase 5) telah digantikan modul Pengumpulan Tugas** — lihat Fase 5 di bawah. Modul tambahan di luar backlog awal yang kini aktif & teruji: **Rekap Tugas** (PDF/Excel), **Deadline & Materi per Pertemuan**, **Notifikasi Pengingat terjadwal** (`pengingat:deadline` per jam + `pengingat:pengembalian` harian), **Delegasi Aslab**. Verifikasi terakhir: **backend 185/185 test lulus**, `vite build` hijau (per `docs/STATUS Sesi - Tugas Pertemuan & Deadline.md`). Dokumen `1_PRD.md`, `2_SRS.md`, `3_SDD.md` sudah diselaraskan ke v1.1. **Belum ada commit** (working tree).

## Catatan Progres (per 2026-07-05)

**Backend (`src/backend`)**:
- Sanctum token auth fungsional: `POST /api/auth/login` (cek NULL password, pesan eksplisit sesuai SRS UC-01), `GET /api/auth/me`, `POST /api/auth/logout` — sudah sesuai SDD 5.1.
- Migration `users` lengkap sesuai SDD 3.1 (kolom `google_id`, `avatar`, `role` enum, `password` nullable).
- Migration `dosen` (SDD 3.2) & `mahasiswa` (SDD 3.3) sudah ada: `user_id` unique, `mahasiswa.npm` unique, `angkatan` char(4), `dosen_pembimbing_id` FK `nullOnDelete`.
- Model `User`, `Dosen`, `Mahasiswa` + relasi Eloquent lengkap (`User::dosen()/mahasiswa()`, `Dosen::mahasiswaBimbingan()`, `Mahasiswa::dosenPembimbing()`).
- `laravel/socialite ^5.28` terpasang, `config/services.php` punya blok `google` (client id/secret/redirect) & `frontend.url`.
- Google OAuth: `GET /api/auth/google/redirect` & `/callback` jalan — validasi domain (`@student.unsil.ac.id`→mahasiswa, `@unsil.ac.id`→dosen), find-or-create `users`, terbitkan Sanctum token, redirect ke frontend. **Auto-create profil `dosen`/`mahasiswa` + ekstraksi NPM/angkatan sudah AKTIF** (`createRoleProfile()` dipanggil saat registrasi pertama; `ensureRoleProfile()` backfill profil untuk akun lama yang belum punya — keduanya idempotent via `firstOrCreate`).
- **Profil Akun (semua role)**: `POST /api/auth/avatar` (unggah foto, validasi `image|mimes:jpeg,jpg,png,webp|max:2048`, simpan disk publik `avatars/<uuid>`, hapus avatar lokal lama) & `PATCH /api/auth/profile` (edit `name`/`no_telp`; dosen +`nidn`/`jabatan_fungsional`/`tempat_lahir`/`tanggal_lahir`; mahasiswa +`prodi`; `email`/`role`/`npm`/`angkatan` immutable). Kolom `no_telp` ditambahkan ke `users` (migration `add_no_telp_to_users`). Diuji `AvatarTest` (3 test). _**Bidang Minat** (master banyak-banyak `dosen ↔ bidang_minat`) aktif: panel admin (Gate `manage-bidang-minat`), dipilih dosen via `bidang_minat_ids[]` di Edit Profil, tampil di Profil & Detail Dosen. Penamaan konsisten `bidang_minat` di semua lapisan — lihat `3_SDD.md` 3.2a._
- `DatabaseSeeder` membuat akun Admin & Supervisor sesuai SDD Bagian 2.
- `laravel/sanctum` terpasang, `config/cors.php` dikonfigurasi (`supports_credentials: true`, origin dari env `FRONTEND_URL`).
- **Kelola User (Admin)**: `UserController` CRUD (`/api/users`) + Form Request (`StoreUserRequest`/`UpdateUserRequest`) + Gate `manage-users`; create role `dosen` otomatis membuat profil `dosen`; `destroy` menolak hapus akun sendiri. Diuji `UserManagementTest` (5 test lulus).
- **Info Lab (FASE 2)**: migration + model `InfoLab`, seeder 4 tipe (`InfoLabSeeder` di `DatabaseSeeder`), endpoint `GET` (publik) & `PATCH` (Admin via Gate `manage-info-lab`) dengan constraint enum `tipe`. Diuji `InfoLabTest`. _Seeder kini berisi konten nyata (bukan placeholder): `kepala_lab` (foto `frontend/public/nur-widiyasono.jpg` + tabel profil markdown), `visi_misi` (Visi + 8 poin Misi). Konten tetap dapat disunting Admin lewat panel._
- **Dosen (FASE 2)**: tabel `dosen` diperluas (migration `add_profile_fields_to_dosen`: jenis_kelamin, jabatan_fungsional, tempat_lahir, tanggal_lahir, biografi) agar Detail Dosen sepadan situs lama. `DosenController` (index/show **publik**, update via `DosenPolicy` pemilik/Admin/Supervisor) + `UpdateDosenRequest`. `DosenSeeder` menyalin profil dari situs lama (1 dosen nyata: Ir. Nur Widiyasono). _Edit Profil dosen kini juga mencakup `jabatan_fungsional`, `tempat_lahir`, `tanggal_lahir` (selain `nidn` & Bidang Minat) lewat `PATCH /api/auth/profile`._ Diuji `DosenTest` (10 test).
- **Data Master (FASE 3)**: tabel & model `ruangan` (SDD 3.4) dan `mata_kuliah` (SDD 3.6); Gate `manage-master-data` (Admin/Supervisor) di `AppServiceProvider`. `RuanganController` & `MataKuliahController` (apiResource index/store/update/destroy) — read terbuka untuk semua role login, CUD via Gate. Route `apiResource('ruangan')` & `apiResource('mata-kuliah')` (param `mataKuliah`). Seeder `RuanganSeeder` (3 ruangan KK JKF) & `MataKuliahSeeder` (4 praktikum JKF) di `DatabaseSeeder`. Diuji `RuanganTest` (8) & `MataKuliahTest` (7). _Tolak-hapus saat masih dirujuk peminjaman/kelas aktif ditandai `ponytail` — menyusul bersama T3.5/T3.12._
- **Peminjaman Ruangan & Kelas Lab (FASE 3)**: tabel/model `peminjaman_ruangan`, `kelas_lab`, `kelas_lab_peserta` (relasi lengkap ke `ruangan`/`mata_kuliah`/`dosen`/`mahasiswa`/`users`). `JadwalRuanganService` memusatkan deteksi bentrok dua arah (peminjaman titik-waktu ↔ kelas berulang mingguan; overlap jam string `H:i:s`, hari dari tanggal via ISO day, rentang semester). `PeminjamanRuanganController` (index filter-per-role, kalender gabungan, store, approve/reject dgn re-validasi, **destroy** Admin/Supervisor) + `StorePeminjamanRuanganRequest` (ajukan **Mahasiswa saja**; approve/reject via Gate `approve-peminjaman-ruangan`). `KelasLabController` (index/show + `sisa_kuota`, store/update/destroy via `KelasLabPolicy` — Admin dilarang buka kelas; **direvisi per RBAC v1.2: Admin kini berwenang penuh**) + `Store/UpdateKelasLabRequest`. Seeder `KelasLabSeeder`. Diuji `PeminjamanRuanganTest` & `KelasLabTest`.
- **Penyempurnaan FASE 3 (per 2026-06-29)**:
  - **Jam operasional 07.00–17.00 WIB** divalidasi di `StorePeminjamanRuanganRequest`, `Store/UpdateKelasLabRequest` (`after_or_equal:07:00` / `before_or_equal:17:00`).
  - **Kalender** kini menampilkan peminjaman disetujui **dari awal minggu berjalan ke depan** (peminjaman minggu lalu otomatis rontok tiap pergantian minggu — tanpa cron).
  - **Persetujuan pendaftaran Kelas Lab**: migration `add_status_to_kelas_lab_peserta` (`status` enum menunggu/disetujui/ditolak + `disetujui_oleh`). `daftar` membuat status `menunggu`; kuota & aturan menghitung menunggu+disetujui. Endpoint baru `GET /api/kelas-lab/pendaftaran`, `PATCH …/pendaftaran/{peserta}/approve|reject` (Dosen pemilik / Supervisor). Aturan pendaftaran: **1 sesi per mata kuliah** + **tanpa bentrok jadwal** (boleh ambil >1 mata kuliah). Baris `ditolak` boleh diajukan ulang.
  - Suite backend **72 test lulus**.
- **Inventaris & Peminjaman Perangkat (FASE 4, per 2026-07-05)**: migrasi + model `perangkat` (SDD 3.9), `peminjaman_perangkat` (SDD 3.10), `perpanjangan_peminjaman` (SDD 3.11) dengan relasi lengkap. Gate `approve-peminjaman-perangkat` (Admin/Supervisor); CRUD perangkat via `manage-master-data`. `PerangkatController` (apiResource — read terbuka, CUD via Gate; **destroy** ditolak bila status ≠ `tersedia` atau ada peminjaman aktif). `PeminjamanPerangkatController` (index filter-per-role; store **Mahasiswa saja** via `StorePeminjamanPerangkatRequest`; approve/reject; **kembalikan**; **destroy** = batalkan pengajuan sendiri saat `menunggu` / hapus oleh Admin/Supervisor; ajukanPerpanjangan). `PerpanjanganController` (approve/reject). **Aturan kunci UC-03**: approve peminjaman menandai perangkat `dipinjam` (DB transaction, re-validasi tersedia); kembalikan → `dikembalikan` + `tanggal_kembali_aktual` + perangkat `tersedia`; perpanjangan ditolak bila `tanggal_kembali_rencana` sudah lewat; approve perpanjangan otomatis memperbarui `tanggal_kembali_rencana` induk. Seeder `PerangkatSeeder` (10 perangkat contoh, idempotent) di `DatabaseSeeder`. Diuji `PerangkatTest` (7), `PeminjamanPerangkatTest` (14, termasuk batal/hapus), `PerpanjanganTest` (5). Suite backend **104 test lulus**.
- **Presensi Laboratorium (FASE 5, per 2026-07-05)**: migrasi + model `presensi` (SDD 3.12) + migrasi `add_konteks_to_presensi_table` (`kelas_lab_id` & `peminjaman_ruangan_id`, FK nullable `nullOnDelete`, saling eksklusif). **Presensi berbasis konteks jadwal** (maintenance Fase 5) — bukan check-in/out teks bebas: sebuah entri terikat **Kelas Lab terjadwal hari ini** (peserta `disetujui`, hari cocok + dalam rentang semester) atau **peminjaman ruangan disetujui hari ini**; `check_in`/`check_out` & `keperluan` diturunkan dari jadwal konteks, bukan waktu tombol ditekan. `PresensiController`: `index` (cakupan per-role — Mahasiswa miliknya / Dosen mahasiswa bimbingan via `dosen.mahasiswaBimbingan` / Admin-Supervisor semua), `konteks` (`GET /api/presensi/konteks` — daftar konteks layak hari ini + tanda `sudah_presensi`), `store` (`POST /api/presensi` — cek presensi; validasi sumber & konteks di `StorePresensiRequest`), `update`/`destroy` (koreksi/hapus: Dosen untuk bimbingannya, Admin/Supervisor untuk entri mana pun; set `dicatat_oleh`). **Aturan kunci UC-04**: presensi ganda untuk konteks yang sama ditolak (422); tanpa konteks jadwal aktif tidak bisa presensi. Timestamp waktu lokal WIB (`Asia/Jakarta`; app tz `UTC`). Tanpa Gate baru — otorisasi via `authorize()` Form Request + helper scoping controller. Diuji `PresensiTest` (15 — cek presensi kelas/peminjaman, tolak bukan-hari-ini/belum-disetujui/duplikat, konteks + `sudah_presensi`, scoping mahasiswa/dosen/supervisor, koreksi dosen+`dicatat_oleh`, hapus). Suite backend **119 test lulus**.
- **Katalog Sertifikasi (FASE 6, per 2026-07-05)**: migrasi + model `sertifikasi` (SDD 3.13 — katalog berdiri sendiri, tanpa relasi ke `users`; **direvisi per RBAC v1.2/2026-07-10: kini ada kolom `created_by` FK → users + `SertifikasiPolicy`, Dosen boleh Create & kelola miliknya**). `SertifikasiController` apiResource: `index` terbuka semua role login, `store`/`update`/`destroy` via Gate `manage-master-data` (Admin/Supervisor, dipakai bersama katalog master lain; **kini via `SertifikasiPolicy`**). Modul murni informasional (tidak menangani pendaftaran). Seeder `SertifikasiSeeder` (MTCNA, CCNA, CEH, Oracle SQL, idempotent) di `DatabaseSeeder`. Diuji `SertifikasiTest` (6).
- **Portofolio Mahasiswa (FASE 7, per 2026-07-05)**: migrasi + model `portofolio` (SDD 3.14, `belongsTo User`, `User::portofolio()` hasMany). `PortofolioController` apiResource: `index` terbuka semua role login (filter `?user_id=`), `store` via `StorePortofolioRequest` (Mahasiswa saja, `user_id` dari user login), `update` via `UpdatePortofolioRequest` (pemilik saja), `destroy` cek kepemilikan (403 bila bukan pemilik). Diuji `PortofolioTest` (7). Suite backend **132 test lulus**.
- **Enhancement di luar backlog fase (per 2026-07-06)** — fitur nyata yang sudah jalan tetapi tidak berasal dari daftar task FASE 0–9:
  - **Delegasi Asisten Lab (Aslab)**: `AslabController` — Admin menetapkan/mengembalikan mahasiswa ↔ Supervisor (hanya transisi `mahasiswa↔supervisor`, bukan ubah role bebas), otorisasi Gate `manage-users`. Endpoint `GET /api/aslab`, `POST /api/aslab/{user}` (promote), `DELETE /api/aslab/{user}` (demote); profil `mahasiswa` dipertahankan agar bisa dikembalikan. Frontend `views/admin-aslab.vue`. Diuji `AslabTest`.
  - **Reset password jalur "lupa password"**: `POST /api/auth/reset-password` (`AuthController::resetPassword`) — atur ulang password **tanpa** password lama, **khusus akun tertaut Google UNSIL** (`google_id` tidak null); akun non-Google ditolak.
  - **Unggah lampiran konten Info Lab**: `POST /api/info-lab/upload` (`InfoLabController::uploadLampiran`, Gate `manage-info-lab`) — opsi "File" pada editor konten; validasi `mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,zip|max:5120`.
- **Laporan/Report (FASE 8, per 2026-07-06)**: `ReportController` (`index` JSON + `pdf` unduh). Gate baru `view-report` (Admin/Supervisor) di `AppServiceProvider`; `ReportRequest` menggabungkan otorisasi (Gate) + validasi `from`/`to` (`date`, `to after_or_equal:from`). Rekap `periode` + `peminjaman_ruangan` (per status) + `peminjaman_perangkat` (per status) + `presensi` (`total_sesi`, `total_mahasiswa_unik`, `rata_rata_durasi_menit`) sesuai 3_SDD.md 5.13; default 30 hari terakhir. PDF via **`barryvdh/laravel-dompdf ^3.1`** (dependency baru, dikonfirmasi user) render Blade `resources/views/reports/lab.blade.php` (satu-satunya Blade — dokumen PDF, bukan halaman SPA). Route `GET /api/report`, `GET /api/report/pdf`. Diuji `ReportTest` (7 — akses Admin/Supervisor, tolak Dosen/Mahasiswa/guest, agregasi status, unduh PDF `content-type application/pdf`).
- **Notifikasi In-App (FASE 9, per 2026-07-06)**: migrasi + model `notifikasi` (SDD 3.16 — `user_id`, `judul`, `pesan`, `tipe` enum `pengajuan_masuk`/`status_pengajuan`/`pendaftaran`, `referensi_id` nullable, `is_read`; index `(user_id, is_read)`). `NotifikasiService` (`kirim()` + `kirimKeApprover()`) dipanggil **di dalam transaksi DB aksi pemicu** (rollback → notifikasi ikut batal). Terintegrasi ke: approve/reject & pengajuan baru **peminjaman ruangan** (`PeminjamanRuanganController`), **peminjaman perangkat** & **perpanjangan** (`PeminjamanPerangkatController`, `PerpanjanganController`), dan **pendaftaran Kelas Lab** (`KelasLabController::daftar`). `NotifikasiController` (`index` milik sendiri + `unread_count`, `read`, `readAll`, `destroy`; 403 bila bukan milik). `GET /api/auth/me` kini menyertakan `unread_notifications_count`. `User::notifikasi()` hasMany. Tanpa endpoint pembuatan publik. Diuji `NotifikasiTest` (8 — pengajuan baru→approver bukan pengaju, approve→pengaju saja, `me` unread count, scoping index, read 403 bukan milik, read-all, hapus, rollback atomik). Suite backend **147 test lulus**.
- **Belum ada**: —. Seluruh FASE 0–9 selesai (kecuali T1.10 Policy RBAC terpusat yang *sebagian* — otorisasi via Gate per-modul).

**Frontend (`src/frontend/app-labriset`)**:
- Vue 3 + Vite, Vue Router 4, Pinia, Axios — semua terpasang. Struktur folder lengkap (`components/`, `views/`, `stores/`, `services/`, `router/`, `composables/`).
- `services/api.js`: Axios instance + interceptor Bearer token otomatis dari localStorage, base URL dari `VITE_API_BASE_URL`.
- `stores/auth.js`: login, loginWithToken (menerima token jadi dari callback Google), logout, fetchUser — token disimpan di localStorage, state `user`/`isAuthenticated`.
- `router/index.js`: navigation guard RBAC (`requiresAuth`/`roles`), restore sesi saat refresh halaman, redirect sudah-login ke beranda; route `/auth/callback` terdaftar.
- `views/login-page.vue`: form login email+password terhubung ke `authStore.login()`; tombol "Login dengan UNSIL Mail" redirect ke `/api/auth/google/redirect`, plus penanganan & tampilan pesan error OAuth dari query string.
- `views/auth-callback.vue`: penerima redirect Google OAuth — ambil token dari query, simpan via `authStore.loginWithToken()`, muat data user, lalu arahkan ke tujuan semula (atau balik ke login dengan pesan error bila gagal).
- `views/profil-page.vue` (route `/profil`, `requiresAuth`): kartu identitas (avatar Google/inisial, nama, email, peran; NPM/angkatan/prodi untuk mahasiswa, NIDN/bidang minat untuk dosen) + **form Edit Profil** (nama, no_telp; dosen +NIDN/jabatan fungsional/tempat & tanggal lahir + **Bidang Minat** multi-select via `components/multi-select-dropdown.vue`; mahasiswa +prodi — via `authService.updateProfile()`) + **ganti foto profil** (unggah file via `authService.updateAvatar()`) + form password kondisional — "Atur Password Login" bila `has_password=false`, "Ubah Password" (wajib password lama) bila sudah ada. Terhubung ke `authService.setPassword()`/`changePassword()`, lalu `fetchUser()` untuk segarkan state. Link "Profil Saya" muncul di header saat login.
- Backend pendukung: `me()` kini eager-load relasi `dosen.bidangMinat`/`mahasiswa`; model `User` mengekspos flag `has_password` agar frontend memilih form yang tepat, dan `no_telp` masuk `fillable`.
- Halaman info lab **tersambung API** lewat `composables/use-info-lab.js` + `components/markdown-content.vue` (render konten HTML dari editor TipTap maupun Markdown legacy, dep `marked`): Beranda, Visi-Misi, Profil Kepala Lab, Roadmap Lab membaca `GET /api/info-lab/{tipe}`. _`markdown-content.vue` menata tabel profil (kepala lab) & daftar: penanda daftar berupa panah kanan dua warna (kuning atas, biru `--bs-navy` bawah) via `li::before` + `background-clip:text`, sejajar judul — menyamai tampilan halaman statis lama._
- **Dosen tersambung API**: `views/list-dosen.vue` (Daftar Dosen) & `views/detail-dosen.vue` (Biografi/Detail) kini dinamis via `services/dosen.js` (`GET /api/dosen`, `GET /api/dosen/{id}`); route `/detaildosen/:id`; tabel bio (3 kolom rapi) + biografi + **Bidang Minat** (gabungan nama relasi) dirender dari data, `tanggal_lahir` diformat ke teks Indonesia. `sidemenu-dosen.vue`: tautan Biografi mengikuti id aktif, sub-halaman (Credential/Publikasi/Buku/Roadmap) membawa konteks `?dosen=<id>`. **Roadmap Penelitian Dosen** (`roadmap-dosen.vue`) kini dinamis dari `dosen.roadmap_riset` — dibedakan dari **Roadmap Laboratorium** (`roadmap-lab.vue`, `info_lab.roadmap_kk`). _Semua sub-halaman dosen (Biografi/Credential/Penelitian/Buku/Roadmap) memakai komponen seragam `components/dosen-identity-card.vue` (kartu identitas) + composable cache `composables/use-dosen.js` agar berpindah menu tak memuat ulang data dosen yang sama._
- **Panel Admin**: hub `/admin` (`admin-page.vue`) + `components/sidemenu-admin.vue`; **Kelola User** (`admin-users.vue`), **Konten Info Lab** (`admin-info-lab.vue` — editor **TipTap** WYSIWYG via `components/rich-text-editor.vue`, plus fitur "Ambil dari Profil Dosen" untuk tab Kepala Lab) fungsional; paginasi lokal (`components/pagination-bar.vue` + `composables/use-pagination.js`); link "Panel Admin" di header untuk role admin.
- **Data Master (FASE 3)**: `views/admin-data-master.vue` (route `/admin/data-master`, guard `roles: ['admin','supervisor']`) — satu halaman dengan **tab Ruangan · Mata Kuliah · Bidang Minat** (Bidang Minat dipindah ke sini dari panel terpisah; `admin-bidang-minat.vue` dihapus, route `/admin/bidang-minat` dihapus), masing-masing CRUD penuh + paginasi lokal, via `services/ruangan.js`/`mata-kuliah.js`/`bidang-minat.js`. Status ruangan badge berwarna. Link "Data Master" di `sidemenu-admin.vue` & kartu hub `admin-page.vue`.
- **Peminjaman Ruangan (FASE 3)**: `jadwal-lab.vue` (kartu **Informasi Jadwal Lab** = kelas mingguan + peminjaman disetujui dikelompokkan **Minggu ini / Mendatang** dgn pembatas & catatan auto-refresh mingguan; **form pengajuan Mahasiswa** mode Satu hari / Beberapa hari + jam 07–17; kolom kanan jadi kartu **Persetujuan Peminjaman Ruangan** utk Admin/Supervisor), `peminjaman-saya.vue` (status pengajuan sendiri), `persetujuan-peminjaman.vue` (tabel + **filter per kolom** Pengaju Nama/NPM dll + Approve/Reject/Hapus). Service `peminjaman-ruangan.js`.
- **Kelas Lab (FASE 3)**: `kelas-lab.vue` (landing — Mahasiswa lihat "Kelas Lab Saya" + status, tombol ke katalog; Dosen/Supervisor lihat katalog + tombol Kelola & Persetujuan), `katalog-kelas-lab.vue` (pilih & daftar sesi), `persetujuan-kelas-lab.vue` (Dosen/Supervisor terima/tolak pendaftaran + filter kolom + tab Menunggu/Semua), `kelola-kelas-lab.vue` (buka/edit/hapus kelas + filter kolom, Nama Sesi dropdown A–F), `peserta-kelas-lab.vue` (halaman peserta terpisah: kolom NPM·Nama·Prodi). Service `kelas-lab.js` (+ pendaftaran/approve/reject). Nav header "Kelas Lab"; aksi dipindah ke tombol dalam halaman (bukan dropdown profil). Util `namaHari()` di `utils/format.js`.
- **Inventaris & Peminjaman Perangkat (FASE 4)**: `views/perangkat.vue` (katalog perangkat + badge status; Mahasiswa tombol "Ajukan Pinjam"). Peminjaman & persetujuan perangkat **disatukan sebagai tab** ke halaman yang sudah ada agar UX konsisten: `views/peminjaman-saya.vue` (tab **Ruangan/Perangkat** — form pengajuan + riwayat + **Ajukan Perpanjangan** inline + **Batalkan** saat menunggu) dan `views/persetujuan-peminjaman.vue` (tab **Ruangan/Perangkat** — approve/reject + **Konfirmasi Kembali** + approve/reject perpanjangan), dibuka via query `?tab=perangkat`. Path lama `/peminjaman-perangkat` & `/persetujuan-perangkat` di-**redirect** ke tab tersebut. Kelola data perangkat sebagai **tab "Perangkat"** di `admin-data-master.vue`. Service `perangkat.js` & `peminjaman-perangkat.js`. Route `/perangkat` (semua login); nav header menu "Perangkat". Util `statusPerangkatLabel()` + status `dikembalikan` di `utils/format.js`. `vite build` hijau.
- **Presensi Laboratorium (FASE 5)**: `views/presensi.vue` (route `/presensi`, semua role login — halaman **adaptif per-role**). Mahasiswa: **Cek Presensi berbasis konteks** — memilih Kelas Lab terjadwal / peminjaman ruangan disetujui hari ini lalu klik **Cek Presensi** (tombol nonaktif bila sudah presensi), plus **Riwayat Presensi Saya** (kolom Kegiatan · Tanggal · Waktu · Ruangan). Dosen/Admin/Supervisor: **Rekap** kehadiran (Dosen → mahasiswa bimbingan; Admin/Supervisor → semua) + statistik (total sesi & mahasiswa unik) + hapus entri. Entri diakses via **kartu "Presensi" di paling atas kolom aksi hub `views/jadwal-lab.vue`** (di atas Formulir Peminjaman Ruangan). Service `presensi.js` (`list`, `konteks`, `cek`, `update`, `remove`). Util `formatWaktu`/`formatRentangWaktu`/`durasiPresensi` di `utils/format.js`. `vite build` hijau.
- **Katalog Sertifikasi (FASE 6)**: `views/sertifikasi.vue` (route `/sertifikasi`, semua login) — grid kartu (nama, penyelenggara, jadwal, persyaratan) + tombol "Info Pendaftaran ↗" ke tautan eksternal. Panel admin `views/admin-sertifikasi.vue` (route `/admin/sertifikasi`, guard `['admin','supervisor']`) CRUD + paginasi. Service `sertifikasi.js`. Nav header "Sertifikasi"; link aktif di `sidemenu-admin.vue` & kartu hub `admin-page.vue`. `vite build` hijau.
- **Portofolio Mahasiswa (FASE 7)**: `views/portofolio.vue` (route `/portofolio`, semua login) — tab **Portofolio Saya** (Mahasiswa: form tambah/edit + hapus milik sendiri) & **Jelajah Semua** (grid kartu semua portofolio + nama pemilik; default untuk non-Mahasiswa). Service `portofolio.js`. Nav header "Portofolio". `vite build` hijau.
- **Laporan/Report (FASE 8)**: `views/report.vue` (route `/report`, guard `['admin','supervisor']`) — filter rentang tanggal (`from`/`to`, default 30 hari), 3 kartu rekap (Peminjaman Ruangan/Perangkat/**Tugas**) + tombol **Unduh PDF** (fetch blob via `reportService.pdf` agar Bearer token terkirim). Service `report.js`. Entri: nav header **"Laporan"** untuk Admin/Supervisor + kartu hub `admin-page.vue` + link `sidemenu-admin.vue`. _Rekap Tugas rinci (PDF/Excel, per pertemuan) tersedia terpisah — lihat Fase 5 T5.8._ `vite build` hijau.
- **Notifikasi In-App (FASE 9)**: `components/notification-bell.vue` (ikon lonceng + badge merah `unread_count` + panel dropdown: daftar notifikasi, tandai satu/semua dibaca, hapus per item; unread ditandai visual). Store `stores/notifikasi.js` (Pinia — `items`, `unreadCount`, `seed`/`fetch`/`markRead`/`markAllRead`/`remove`/`reset`); badge **diseed dari `unread_notifications_count` pada `me()`** (tanpa request tambahan), daftar dimuat saat panel dibuka. Service `notifikasi.js`. Lonceng dipasang di `header-component.vue` (saat login) + reset saat logout. `vite build` hijau.
- **Belum ada**: —. Seluruh modul FASE 0–9 selesai.

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
- [x] **T1.5** — Endpoint `GET /api/auth/google/redirect` & `GET /api/auth/google/callback` — implementasi alur SDD Bagian 2 lengkap: validasi domain email, auto-create `users` + `dosen`/`mahasiswa`, ekstraksi NPM & angkatan (format `"20" . dua_digit_awal`). _Catatan: `createRoleProfile()` kini **aktif** (dipanggil saat registrasi pertama) + `ensureRoleProfile()` (backfill akun lama), keduanya idempotent via `firstOrCreate`. Tabel `dosen`/`mahasiswa` sudah ada (T1.2/T1.3). Test otomatis T1.18–T1.20 ada di `GoogleAuthTest` (Socialite di-mock)._
- [x] **T1.6** — Endpoint `POST /api/auth/login` (login manual) — tolak jika `password` NULL, dengan pesan sesuai SRS UC-01 skenario 1b
- [x] **T1.7** — Endpoint `POST /api/auth/set-password` & `PATCH /api/auth/change-password` (SRS UC-01b). _set-password hanya untuk akun ber-password NULL (tanpa password lama); change-password wajib `current_password` cocok. Validasi `min:8` + `confirmed`; flag `has_password` diekspos via model `User` dan `me()` eager-load profil._
- [x] **T1.7a** — Migration `add_no_telp_to_users` (kolom `no_telp` varchar(32) nullable) + endpoint `PATCH /api/auth/profile` (Edit Profil akun sendiri, SDD 5.1): field `name`/`no_telp` (semua role), `nidn`/`jabatan_fungsional`/`tempat_lahir`/`tanggal_lahir` (dosen), `prodi` (mahasiswa, whitelist `Informatika`); `email`/`role`/`npm`/`angkatan` **immutable** (ditolak di backend). _Field `bidang_minat_ids[]` (dosen) di-`sync` ke pivot Bidang Minat (master aktif) — lihat SDD 3.2a._
- [x] **T1.7b** — Endpoint `POST /api/auth/avatar` (unggah/ganti foto akun sendiri, SDD 5.1): validasi `image|mimes:jpeg,jpg,png,webp|max:2048`, simpan disk publik `avatars/<uuid>`, kolom `avatar` diisi URL absolut, avatar lokal lama dihapus (avatar Google eksternal dibiarkan). Diuji `AvatarTest`.
- [x] **T1.8** — Endpoint `POST /api/auth/logout` & `GET /api/auth/me`
- [x] **T1.9** — Seeder `UserSeeder` untuk membuat akun Admin & Supervisor manual (SDD Bagian 2, catatan implementasi)
- [ ] **T1.10** — Policy dasar untuk role-based access control mengacu matriks RBAC SRS Bagian 1. _Catatan: fondasi via Gate sudah ada & diuji — `manage-users` & `manage-info-lab` (Admin only) di `AppServiceProvider`. Gate/Policy modul lain (ruangan/perangkat/dosen/dst.) menyusul saat fasenya dikerjakan._
- [x] **T1.11** — Endpoint `GET /api/users`, `POST /api/users`, `PATCH /api/users/{id}`, `DELETE /api/users/{id}` (Admin only). _`UserController` (apiResource only index/store/update/destroy), otorisasi Gate `manage-users`; create user role `dosen` otomatis membuat profil `dosen` (invarian SDD 3.2); `destroy` menolak hapus akun sendiri. Diuji di `UserManagementTest`._
- [x] **T1.12** — Form Request validasi untuk seluruh endpoint di atas (`StoreUserRequest`, `UpdateUserRequest`)

### Frontend
- [x] **T1.13** — Halaman Login: tombol "Login dengan Google" + form "Login dengan Email & Password" (form fungsional, terhubung ke `authStore.login()`; tombol Google redirect ke `/api/auth/google/redirect`, callback ditangani `views/auth-callback.vue`)
- [x] **T1.14** — Halaman Profil (`views/profil-page.vue`, route `/profil`): kartu identitas + **form Edit Profil** (nama, no_telp; dosen +NIDN/jabatan fungsional/tempat & tanggal lahir + Bidang Minat; mahasiswa +prodi) + **ganti foto profil** (unggah file) + form "Atur Password" / "Ubah Password" kondisional sesuai flag `has_password`; link "Profil Saya" di header saat login. Terhubung `authService.updateProfile()`/`updateAvatar()`/`setPassword()`/`changePassword()`
- [x] **T1.15** — Pinia store `auth` — menyimpan token (localStorage), data user, role; dipakai global untuk proteksi route
- [x] **T1.16** — Vue Router navigation guard — `beforeEach` dengan meta `requiresAuth`/`roles`, restore sesi saat refresh, redirect pasca-login. _Catatan: redirect ke dashboard per role menunggu halaman dashboard masing-masing role dibuat._
- [x] **T1.17** — Halaman Kelola User (Admin only) — list, edit role, hapus user. _`views/admin-users.vue` (route `/admin/users`, guard `roles: ['admin']`): tabel user + filter role + form tambah/edit + hapus (tombol hapus disembunyikan untuk akun sendiri). Plus halaman hub Panel Admin `views/admin-page.vue` (`/admin`) & `components/sidemenu-admin.vue`; link "Panel Admin" di header untuk role admin._

### Testing
- [x] **T1.18** — Test: domain email non-UNSIL ditolak saat login Google. _Diuji `GoogleAuthTest` — Socialite di-mock, email `@gmail.com` ditolak: redirect `/login?error=invalid_domain` & tidak ada `users` dibuat._
- [x] **T1.19** — Test: login Google pertama kali membuat `users` + entri `dosen`/`mahasiswa` otomatis sesuai role. _Diuji `GoogleAuthTest` (2 kasus: `@student`→mahasiswa, `@unsil`→dosen; profil terkait ikut terbuat, profil lawan 0)._
- [x] **T1.20** — Test: ekstraksi NPM dan angkatan dari email mahasiswa menghasilkan nilai yang benar. _Diuji `GoogleAuthTest`: `197006028@student…`→`npm=197006028`, `angkatan=2019` (concat `"20"."19"`)._
- [x] **T1.21** — Test: login manual ditolak jika `password` masih NULL. _Diuji `LoginTest`: `POST /api/auth/login` → 422 + pesan eksplisit SRS UC-01 (2b)._
- [x] **T1.22** — Test: field `npm` dan `angkatan` tidak bisa diubah lewat endpoint update profil mahasiswa. _Diuji `ProfileTest`: `PATCH /api/auth/profile` kirim `npm`/`angkatan` → diabaikan, hanya `prodi` berubah._
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
- [x] **T2.12** — Panel kelola konten info lab (Admin only). _`views/admin-info-lab.vue`: tab 4 tipe + form judul/gambar/konten dengan editor **TipTap** WYSIWYG (`components/rich-text-editor.vue`, konten disimpan sebagai HTML; konten Markdown lama tetap dirender), plus fitur **Ambil dari Profil Dosen** untuk menyusun konten Kepala Lab dari data dosen terpilih; terhubung `infoLabService`. Shared: `composables/use-info-lab.js` + `components/markdown-content.vue` (render HTML/Markdown, dep `marked`)._

### Testing
- [x] **T2.13** — Test: hanya Admin yang bisa update `info_lab` dan data dosen milik orang lain. _`info_lab` diuji `InfoLabTest`; bagian dosen diuji `DosenTest` (10 test): baca publik, relasi Bidang Minat, pemilik & Admin/Supervisor bisa update, edit jabatan & TTL lewat profil, dosen lain/mahasiswa 403, guest 401._

---

## FASE 3: Peminjaman Ruangan Lab, Mata Kuliah & Kelas Lab/Praktikum

(PRD 3.3, 3.3a — SRS UC-02, UC-02a — SDD 3.4, 3.5, 3.6, 3.7, 3.8)

> Fase ini dikerjakan sebagai satu kesatuan karena `kelas_lab` dan `peminjaman_ruangan` saling bergantung dalam validasi bentrok jadwal — migration dan logika validasinya harus ada bersamaan sebelum salah satu bisa diuji secara penuh.

### Backend — Mata Kuliah (Data Master)
- [x] **T3.1** — Migration tabel `mata_kuliah` (SDD 3.6). _`kode_mk` nullable unique, `nama_mk`, `sks` nullable._
- [x] **T3.2** — Model `MataKuliah` (`$table = 'mata_kuliah'`, fillable `kode_mk`/`nama_mk`/`sks`)
- [x] **T3.3** — Endpoint CRUD `/api/mata-kuliah` (Admin/Supervisor via Gate `manage-master-data`); `GET` bisa diakses semua role login (dipakai Dosen saat memilih saat membuka Kelas Lab). _`MataKuliahController` apiResource (index/store/update/destroy), validasi inline (`kode_mk` unique, `nama_mk` wajib). Diuji `MataKuliahTest` (7 test)._

### Backend — Ruangan & Peminjaman Ruangan
- [x] **T3.4** — Migration tabel `ruangan` (SDD 3.4). _`status` enum(`tersedia`,`dipakai`,`perbaikan`) default `tersedia`._
- [x] **T3.5** — Migration tabel `peminjaman_ruangan` (SDD 3.5). _FK `ruangan_id`/`user_id` cascade, `disetujui_oleh` nullOnDelete; `status` enum default `menunggu`._
- [x] **T3.6** — Model `Ruangan`, `PeminjamanRuangan` + relasi. _`Ruangan::peminjaman()`/`kelasLab()`; `PeminjamanRuangan` belongsTo `ruangan`/`user` (pengaju)/`penyetuju` (`disetujui_oleh`); cast `tanggal` date._
- [x] **T3.7** — Endpoint CRUD `/api/ruangan` (Admin/Supervisor via Gate `manage-master-data`; `GET` terbuka untuk semua role login). _`RuanganController` apiResource (index/store/update/destroy), validasi inline (status enum). Tolak-hapus saat ada peminjaman/kelas aktif ditandai `ponytail` (menunggu T3.5/T3.12). Diuji `RuanganTest` (8 test)._
- [x] **T3.8** — Endpoint `GET /api/peminjaman-ruangan/kalender` — data ketersediaan gabungan: peminjaman `disetujui` + jadwal `kelas_lab` aktif (masing-masing dengan info ruangan), untuk tampilan kalender frontend.
- [x] **T3.9** — Endpoint `POST /api/peminjaman-ruangan` — `StorePeminjamanRuanganRequest` (authorize: **Mahasiswa saja**; Dosen tidak meminjam ruangan — SRS UC-02) validasi status ruangan `tersedia` + bentrok terhadap dua sumber via `JadwalRuanganService::peminjamanBentrok()`: (1) `peminjaman_ruangan` `disetujui`, (2) `kelas_lab` aktif pada ruangan + hari(dari tanggal) + rentang jam, dalam rentang semester. Disimpan status `menunggu`.
- [x] **T3.10** — Endpoint `PATCH /api/peminjaman-ruangan/{id}/approve` & `/reject` (Gate `approve-peminjaman-ruangan`, Admin/Supervisor) — saat approve, validasi bentrok dijalankan ulang (`peminjamanBentrok`, abaikan diri sendiri) + status ruangan masih `tersedia`; set `disetujui_oleh`. Transaksi DB.
- [x] **T3.11** — Endpoint `GET /api/peminjaman-ruangan` — Admin/Supervisor lihat semua, Dosen/Mahasiswa hanya `user_id` sendiri; eager load `ruangan`/`user`/`penyetuju`. Diuji `PeminjamanRuanganTest`.

### Backend — Kelas Lab/Praktikum
- [x] **T3.12** — Migration tabel `kelas_lab` (SDD 3.7). _FK `mata_kuliah_id`/`dosen_id`/`ruangan_id`/`dibuat_oleh` cascade; `hari` enum senin–sabtu._
- [x] **T3.13** — Migration tabel `kelas_lab_peserta` (SDD 3.8). _FK cascade; unique `(kelas_lab_id, mahasiswa_id)`._
- [x] **T3.14** — Model `KelasLab`, `KelasLabPeserta` + relasi (`belongsTo MataKuliah`/`Dosen`/`Ruangan`/`pembuat`, `hasMany peserta`); accessor `sisa_kuota` (`kuota − peserta_count`).
- [x] **T3.15** — Endpoint `GET /api/kelas-lab` — list semua sesi, filter `?mata_kuliah_id=`; eager load `mataKuliah`/`dosen.user`/`ruangan` + `withCount('peserta')` + `sisa_kuota`.
- [x] **T3.16** — Endpoint `GET /api/kelas-lab/{id}` — detail satu sesi termasuk sisa kuota.
- [x] **T3.17** — Endpoint `POST /api/kelas-lab` — `KelasLabPolicy::create` (Dosen/Supervisor; **Admin 403**). `StoreKelasLabRequest`: Dosen `dosen_id` di-set dari profilnya, Supervisor wajib kirim `dosen_id` valid; `mata_kuliah_id`/`ruangan_id` exists, `kuota` 1–40, ruangan `tersedia`, tidak bentrok (`JadwalRuanganService::kelasBentrok`). `dibuat_oleh` = user login. Transaksi DB.
- [x] **T3.18** — Endpoint `PATCH /api/kelas-lab/{id}` & `DELETE /api/kelas-lab/{id}` — `KelasLabPolicy::update`/`delete` (pemilik `dosen_id` atau Supervisor). `UpdateKelasLabRequest` re-validasi bentrok abaikan diri sendiri.
- [x] **T3.19** — Endpoint `POST /api/kelas-lab/{id}/daftar` (Gate `daftar-kelas-lab`, Mahasiswa) — insert `kelas_lab_peserta` status **`menunggu`** (butuh persetujuan). Validasi: kuota (menunggu+disetujui) belum penuh, belum terdaftar di sesi tsb, **1 sesi/mata kuliah**, **tidak bentrok jadwal** kelas mahasiswa lain. Baris `ditolak` dapat diajukan ulang. _Penyempurnaan per 2026-06-29 — lihat Catatan Progres._
- [x] **T3.20** — Endpoint `DELETE /api/kelas-lab/{id}/daftar` — Mahasiswa batalkan pendaftaran dirinya sendiri.
- [x] **T3.21** — Endpoint `GET /api/kelas-lab/{id}/peserta` — pemilik kelas, Supervisor, atau Admin (via `KelasLabPolicy::viewPeserta`); eager load `mahasiswa.user` + status.
- [x] **T3.21a** — Persetujuan pendaftaran (penyempurnaan): `GET /api/kelas-lab/pendaftaran` (Dosen kelasnya / Supervisor) + `PATCH …/pendaftaran/{peserta}/approve|reject`. Migration `add_status_to_kelas_lab_peserta` (`status`+`disetujui_oleh`). Diuji `KelasLabTest`.

### Frontend — Mata Kuliah
- [x] **T3.22** — Panel kelola data mata kuliah (Admin/Supervisor): list, tambah, edit, hapus. _Diwujudkan sebagai tab "Mata Kuliah" di halaman gabungan `views/admin-data-master.vue` (route `/admin/data-master`, guard `roles: ['admin','supervisor']`) via `services/mata-kuliah.js`; paginasi lokal._

### Frontend — Ruangan & Peminjaman Ruangan
- [x] **T3.23** — Halaman Kalender Ketersediaan Ruangan. _`views/jadwal-lab.vue` (route `/jadwallab`): kalender mingguan dari `GET /api/peminjaman-ruangan/kalender`, slot peminjaman disetujui & `kelas_lab` dibedakan warna; filter ruangan + navigasi minggu._
- [x] **T3.24** — Form Pengajuan Peminjaman Ruangan (**Mahasiswa saja**). _Tertanam di `jadwal-lab.vue` sebagai kartu kanan (form hanya tampil untuk Mahasiswa): pilih ruangan/tanggal/jam/keperluan (textarea auto-resize) → `POST /api/peminjaman-ruangan`; error bentrok/ruangan tak tersedia ditampilkan dari backend. Layout 2 kolom: kartu Ketersediaan (kiri, lebih lebar) + kartu Form (kanan)._
- [x] **T3.25** — Halaman Daftar Pengajuan + Approve/Reject (Admin/Supervisor). _`views/persetujuan-peminjaman.vue` (route `/persetujuan-peminjaman`): tabel pengajuan + filter status + tombol Setujui/Tolak (`PATCH …/approve|reject`)._
- [x] **T3.26** — Panel kelola data ruangan (Admin/Supervisor). _Diwujudkan sebagai tab "Ruangan" di halaman gabungan `views/admin-data-master.vue` (route `/admin/data-master`) via `services/ruangan.js`; status ditampilkan sebagai badge berwarna, paginasi lokal._
- [x] **T3.27** — Halaman "Peminjaman Saya". _`views/peminjaman-saya.vue` (route `/peminjaman-saya`, **Mahasiswa saja**): daftar pengajuan milik sendiri + badge status._

### Frontend — Kelas Lab/Praktikum
- [x] **T3.28** — Halaman Kelas Lab/Praktikum. _`views/kelas-lab.vue` (route `/kelaslab`): sesi dikelompokkan per mata kuliah, tampil jadwal + sisa kuota tiap sesi._
- [x] **T3.29** — Tombol "Daftar" / "Batalkan Pendaftaran" per sesi (Mahasiswa). _Di `kelas-lab.vue`: tombol kondisional sesuai status pendaftaran & sisa kuota (`POST`/`DELETE …/daftar`)._
- [x] **T3.30** — Form buka Kelas Lab baru (Dosen/Supervisor). _`views/kelola-kelas-lab.vue` (route `/kelaslab/kelola`): pilih mata kuliah/ruangan, hari, jam, tanggal semester, kuota, nama sesi; Supervisor wajib pilih dosen._
- [x] **T3.31** — Halaman kelola Kelas Lab milik Dosen. _`kelola-kelas-lab.vue`: list kelas milik sendiri (Supervisor: yang dibuatnya), edit/hapus, lihat peserta._

### Testing
- [x] **T3.32** — Test: pengajuan `peminjaman_ruangan` pada slot yang sudah ada `kelas_lab` aktif → ditolak. _`PeminjamanRuanganTest`._
- [x] **T3.33** — Test: pengajuan `peminjaman_ruangan` pada slot kosong (di luar `kelas_lab`) → diterima. _`PeminjamanRuanganTest`._
- [x] **T3.34** — Test: pembukaan `kelas_lab` baru yang bentrok dengan `kelas_lab` atau `peminjaman_ruangan` disetujui → ditolak. _`KelasLabTest`._
- [x] **T3.35** — Test: pendaftaran peserta melebihi kuota → ditolak. _`KelasLabTest`._
- [x] **T3.36** — Test: mahasiswa yang sudah terdaftar di sesi yang sama tidak bisa mendaftar dua kali. _`KelasLabTest`._
- [x] **T3.37** — Test: Admin tidak bisa membuka Kelas Lab (403). _`KelasLabTest`._ _(Usang — dibalik oleh revisi RBAC v1.2: Admin kini boleh membuka kelas dengan menunjuk dosen pengampu; test disesuaikan.)_
- [x] **T3.38** — Test: Mahasiswa/Dosen hanya bisa melihat pengajuan peminjaman miliknya sendiri. _`PeminjamanRuanganTest`._

---

## FASE 4: Inventaris & Peminjaman Perangkat

(PRD 3.4, SRS UC-03, SDD 3.9, 3.10, 3.11)

### Backend
- [x] **T4.1** — Migration tabel `perangkat` (SDD 3.9)
- [x] **T4.2** — Migration tabel `peminjaman_perangkat` (SDD 3.10)
- [x] **T4.3** — Migration tabel `perpanjangan_peminjaman` (SDD 3.11)
- [x] **T4.4** — Model `Perangkat`, `PeminjamanPerangkat`, `PerpanjanganPeminjaman` + relasi
- [x] **T4.5** — Endpoint CRUD `/api/perangkat` (Admin/Supervisor)
- [x] **T4.6** — Endpoint `POST /api/peminjaman-perangkat` (Mahasiswa saja — SRS Bagian 1)
- [x] **T4.7** — Endpoint `PATCH /api/peminjaman-perangkat/{id}/approve` & `/reject`
- [x] **T4.8** — Endpoint `POST /api/peminjaman-perangkat/{id}/perpanjangan` — **wajib** validasi tanggal kembali rencana belum lewat (SRS UC-03 aturan validasi kunci)
- [x] **T4.9** — Endpoint `PATCH /api/perpanjangan/{id}/approve` & `/reject` — saat approve, backend wajib memperbarui `tanggal_kembali_rencana` pada `peminjaman_perangkat` induk secara otomatis
- [x] **T4.9a** — (tambahan) Endpoint `PATCH /api/peminjaman-perangkat/{id}/kembalikan` — konfirmasi pengembalian: status `dikembalikan`, isi `tanggal_kembali_aktual`, perangkat kembali `tersedia`. Approve peminjaman menandai perangkat `dipinjam` otomatis (DB transaction).
- [x] **T4.9b** — (tambahan) Endpoint `DELETE /api/peminjaman-perangkat/{id}` — pemilik (Mahasiswa) membatalkan pengajuan sendiri selama masih `menunggu`; Admin/Supervisor menghapus kapan saja. Seeder `PerangkatSeeder` di `DatabaseSeeder`.

### Frontend
- [x] **T4.10** — Halaman Daftar Perangkat (status Tersedia/Dipinjam/Perbaikan) — `views/perangkat.vue`
- [x] **T4.11** — Form Pengajuan Peminjaman Perangkat (Mahasiswa) — tab **Perangkat** di `views/peminjaman-saya.vue` (`?tab=perangkat`)
- [x] **T4.12** — Halaman "Peminjaman Saya" — termasuk tombol Ajukan Perpanjangan & Batalkan (tab Perangkat)
- [x] **T4.13** — Halaman Approve/Reject Peminjaman & Perpanjangan (Admin/Supervisor) — tab **Perangkat** di `views/persetujuan-peminjaman.vue` (+ tombol Konfirmasi Kembali)
- [x] **T4.14** — Panel kelola data perangkat (Admin/Supervisor) — tab "Perangkat" di `admin-data-master.vue`

### Testing
- [x] **T4.15** — Test: pengajuan perpanjangan ditolak jika diajukan setelah `tanggal_kembali_rencana` lewat
- [x] **T4.16** — Test: hanya Mahasiswa yang bisa mengajukan peminjaman perangkat (Dosen ditolak)

---

## FASE 5: Pengumpulan Tugas Kelas Lab (menggantikan Presensi)

(PRD 3.5, SRS UC-04, SDD 3.12, 3.12a)

> **⚠️ Perubahan lingkup (per 2026-07-06 s/d 2026-07-08)**: Modul **Presensi** (check-in/check-out) yang semula direncanakan di fase ini **dihapus total** dan digantikan modul **Pengumpulan Tugas** atas permintaan lab. Riwayat evolusi: presensi bebas → presensi berbasis konteks jadwal → **Pengumpulan Tugas** (final). Tabel `presensi` di-drop lewat migrasi `..._drop_presensi_table`. Task T5.1–T5.10 lama (presensi) **tidak lagi berlaku** dan digantikan T5.1–T5.14 di bawah. Detail lihat `docs/STATUS Update Fase 5.md` & `docs/STATUS Sesi - Tugas Pertemuan & Deadline.md`.

### Backend — Pengumpulan Tugas
- [x] **T5.1** — Migration `drop_presensi_table` + `create_tugas_table` (SDD 3.12): `kelas_lab_id` FK cascade, `mahasiswa_id` FK cascade, `judul`, `tautan` (2048), index `kelas_lab_id`
- [x] **T5.2** — Migration `add_pertemuan_to_tugas_table` (`pertemuan` tinyint 1–16, default 1) + model `Tugas` (fillable + cast `pertemuan` int; relasi `kelasLab`/`mahasiswa`)
- [x] **T5.3** — Endpoint `GET /api/tugas` — scoping per-role (Mahasiswa → miliknya; Dosen → kelas yang diampu; Admin/Supervisor → semua)
- [x] **T5.4** — Endpoint `POST /api/tugas` (`StoreTugasRequest`, Mahasiswa peserta `disetujui`): validasi `pertemuan` 1–16, `tautan` url, **satu tugas per (kelas, pertemuan, mahasiswa)**; kirim notifikasi `pengajuan_masuk` ke dosen pengampu + Supervisor (transaksi sama)
- [x] **T5.5** — Endpoint `DELETE /api/tugas/{tugas}` — pemilik (Mahasiswa) atau Admin/Supervisor

### Backend — Deadline & Materi per Pertemuan
- [x] **T5.6** — Migration `create_deadline_pertemuan_table` + `add_materi_...` (SDD 3.12a): unique `(kelas_lab_id, pertemuan)`, `materi` nullable, `deadline` nullable; model `DeadlinePertemuan` + relasi `KelasLab::deadlinePertemuan()`
- [x] **T5.7** — `DeadlinePertemuanController`: `index` (semua role login), `upsert` (`PUT` — materi &/atau deadline; keduanya kosong → hapus record), `destroy`. Otorisasi Dosen pengampu/Supervisor/Admin. Migration `add_tautan_pengumpulan_to_kelas_lab` + validasi wajib di `Store/UpdateKelasLabRequest`

### Backend — Rekap Tugas & Pengingat
- [x] **T5.8** — `RekapTugasController` + `RekapTugasService` + `RekapTugasExcelWriter`: `GET /api/rekap-tugas` (JSON), `/pdf` (dompdf landscape), `/excel` (phpspreadsheet). Gate `view-rekap-tugas` (Admin/Supervisor/Dosen); Dosen di-scope ke kelasnya. Endpoint badge `GET /api/kelas-lab/rekap-tugas` (status perhatian/berjalan/beres)
- [x] **T5.9** — `PengingatDeadlineService` + command `pengingat:deadline` (`hourly`) — notifikasi `pengingat` ke mahasiswa yang belum kumpul untuk deadline lewat; idempoten. Enum `notifikasi.tipe` +`pengingat` (migrasi `add_pengingat_to_notifikasi_tipe`)
- [x] **T5.10** — `PengingatPengembalianService` + command `pengingat:pengembalian` (`dailyAt 07:00`) — notifikasi pengingat pengembalian perangkat jatuh tempo. Scheduler didaftarkan di `bootstrap/app.php`

### Frontend
- [x] **T5.11** — `views/tugas.vue` (Kirim Tugas: dropdown Pertemuan 1–16, hint deadline merah bila lewat, materi pertemuan terpilih, format nama file `NamaTugas_NPM_Nama`; reviewer: Tugas Masuk). Tombol Kirim/Tugas Masuk di `kelas-lab.vue`
- [x] **T5.12** — `views/detail-kelas-lab.vue` (accordion 16 pertemuan: mhs status kirim + materi + deadline; reviewer form Materi+Deadline) & `views/detail-pertemuan.vue` (reviewer: tabel Sudah/Belum + badge terlambat)
- [x] **T5.13** — `views/report.vue` rekap **Tugas** (menggantikan rekap Presensi) + halaman Rekap Tugas (unduh PDF/Excel). Kartu Beranda adaptif (mhs: Informasi Tugas; reviewer: Informasi Pemberian Tugas + progres A/B + badge kepatuhan)
- [x] **T5.14** — Util `formatDeadline`/`toDatetimeLocal`/`sudahLewatDeadline`/`dikirimTerlambat` di `utils/format.js`; service `tugas.js` + `kelas-lab.js` (`deadlineList`/`setDeadline`/`removeDeadline`/`rekapTugas`)

### Testing
- [x] **T5.15** — `TugasTest` (kirim oleh peserta disetujui; tolak non-peserta/menunggu/URL invalid/non-mahasiswa; duplikat pertemuan; scoping; hapus pemilik/supervisor; notifikasi ke dosen & supervisor)
- [x] **T5.16** — `DeadlinePertemuanTest` (materi tanpa deadline / bersamaan / kosong→hapus), `RekapTugasTest` (status perhatian/berjalan/beres, progres A-B, materi tak terhitung, scope Dosen), `PengingatDeadlineTest`, `PengingatPengembalianTest`

---

## FASE 6: Katalog Sertifikasi (Informasional)

(PRD 3.6, SRS UC-05, SDD 3.13)

### Backend
- [x] **T6.1** — Migration tabel `sertifikasi` (SDD 3.13 — murni katalog, tanpa relasi ke `users`) _(direvisi per RBAC v1.2: ditambah kolom `created_by` FK → users, migration `add_created_by_to_sertifikasi_table`)_
- [x] **T6.2** — Model `Sertifikasi`
- [x] **T6.3** — Endpoint CRUD `/api/sertifikasi` (Create/Update/Delete: Admin/Supervisor; Read: semua role). _`SertifikasiController` apiResource (index/store/update/destroy); CUD via Gate `manage-master-data` (dipakai bersama katalog master lain), read terbuka semua role login. Route `apiResource('sertifikasi')`. Seeder `SertifikasiSeeder` (4 sertifikasi: MTCNA, CCNA, CEH, Oracle SQL) di `DatabaseSeeder`._

### Frontend
- [x] **T6.4** — Halaman Katalog Sertifikasi (list + detail, dengan tautan eksternal ke penyelenggara). _`views/sertifikasi.vue` (route `/sertifikasi`, semua login): grid kartu (nama, penyelenggara, jadwal, persyaratan) + tombol "Info Pendaftaran ↗" ke tautan eksternal. Service `sertifikasi.js`. Nav header "Sertifikasi"._
- [x] **T6.5** — Panel kelola katalog sertifikasi (Admin/Supervisor). _`views/admin-sertifikasi.vue` (route `/admin/sertifikasi`, guard `['admin','supervisor']`): CRUD penuh + paginasi lokal. Link "Katalog Sertifikasi" aktif di `sidemenu-admin.vue` & kartu hub `admin-page.vue`._

### Testing
- [x] **T6.6** — Test: Mahasiswa hanya bisa Read, tidak bisa Create/Update/Delete katalog sertifikasi. _Diuji `SertifikasiTest` (6 test): read mahasiswa/guest, CUD admin & supervisor, mahasiswa CUD → 403, validasi field wajib._

---

## FASE 7: Portofolio Mahasiswa

(PRD 3.7, SDD 3.14)

### Backend
- [x] **T7.1** — Migration tabel `portofolio` (SDD 3.14). _`user_id` FK `cascadeOnDelete`, `judul`, `deskripsi` text nullable, `tautan` nullable, `tanggal` date nullable._
- [x] **T7.2** — Model `Portofolio` + relasi ke `User`. _`belongsTo User`, cast `tanggal` date; `User::portofolio()` hasMany._
- [x] **T7.3** — Endpoint CRUD `/api/portofolio` — Create/Update/Delete hanya pemilik (Mahasiswa); Read semua role. _`PortofolioController` apiResource; index eager-load `user:id,name,role` + filter `?user_id=`; store via `StorePortofolioRequest` (authorize Mahasiswa, `user_id` di-set dari user login); update via `UpdatePortofolioRequest` (authorize pemilik); destroy cek kepemilikan (403 bila bukan pemilik)._

### Frontend
- [x] **T7.4** — Halaman Portofolio Pribadi (Mahasiswa — kelola milik sendiri). _`views/portofolio.vue` (route `/portofolio`) tab **Portofolio Saya** (Mahasiswa): form tambah/edit (judul, deskripsi, tautan, tanggal) + hapus, grid kartu. Service `portofolio.js`._
- [x] **T7.5** — Halaman Lihat Portofolio (publik untuk semua role yang login). _Tab **Jelajah Semua** di `portofolio.vue`: grid kartu semua portofolio + nama pemilik; default tab untuk non-Mahasiswa. Nav header "Portofolio"._

### Testing
- [x] **T7.6** — Test: Mahasiswa tidak bisa edit/hapus portofolio milik mahasiswa lain. _Diuji `PortofolioTest` (7 test): read semua role/guest, mahasiswa tambah miliknya (`user_id` dari login), dosen tambah → 403, validasi judul, pemilik ubah/hapus, mahasiswa lain ubah/hapus → 403._

---

## FASE 8: Laporan (Report)

(PRD 3.9, SRS UC-06)

### Backend
- [x] **T8.1** — Endpoint `GET /api/report?from=&to=` — agregasi data peminjaman & pengumpulan tugas. _`ReportController::index`; `ReportRequest` (Gate `view-report` + validasi `from`/`to`); rekap periode + peminjaman ruangan/perangkat per status + **tugas** (`total_terkumpul`, `total_mahasiswa_unik`, `total_kelas`) sesuai 3_SDD.md 5.13; default 30 hari terakhir. **Catatan**: rekap presensi awal telah diganti rekap tugas seiring penggantian modul Fase 5._
- [x] **T8.2** — Endpoint `GET /api/report/pdf?from=&to=` — generate PDF. _Dependency **`barryvdh/laravel-dompdf ^3.1`** dikonfirmasi user. `ReportController::pdf` render Blade `resources/views/reports/lab.blade.php` → unduh PDF._

### Frontend
- [x] **T8.3** — Halaman Report — filter rentang tanggal, tampilan rekap, tombol Download PDF. _`views/report.vue` (route `/report`, guard `['admin','supervisor']`) via `services/report.js`; PDF diunduh sebagai blob (Bearer token). Nav header "Laporan" + kartu hub + link sidemenu admin._

### Testing
- [x] **T8.4** — Test: hanya Admin/Supervisor yang bisa mengakses endpoint report. _Diuji `ReportTest` (7): Admin/Supervisor 200; Dosen/Mahasiswa 403; guest 401; agregasi status peminjaman; unduh PDF `content-type application/pdf`._

### Rekap Tugas Kelas Lab (maintenance Fase 8, per 2026-07-09) — SRS UC-06a, 3_SDD.md 5.15
- [x] **T8.5** — `RekapTugasService::build()` — ringkasan kepatuhan per kelas + matriks detail per kelas (peserta × pertemuan bertugas: `tepat`/`telat`/`belum`, dari `tugas.created_at` vs `deadline`). Logika kepatuhan `KelasLabController::rekapTugas` **dipindah ke service** (endpoint lama delegasi, bentuk JSON dijaga → badge Kelas Lab/home tetap jalan). Dosen di-scope ke kelas sendiri.
- [x] **T8.6** — Gate `view-rekap-tugas` (Admin/Supervisor/Dosen) + `RekapTugasRequest`. Endpoint `GET /api/rekap-tugas` (JSON), `/api/rekap-tugas/pdf` (dompdf, Blade `reports/rekap-tugas.blade.php`, landscape), `/api/rekap-tugas/excel`.
- [x] **T8.7** — Export Excel `.xlsx` berformat via **`phpoffice/phpspreadsheet ^5.8`** (dependency baru; prasyarat `ext-zip` diaktifkan di php.ini). `RekapTugasExcelWriter`: sheet **Ringkasan** (warna status, freeze header) + **satu sheet per kelas** (matriks P-n, freeze kolom kiri).
- [x] **T8.8** — Frontend `views/rekap-tugas.vue` (route `/rekap-tugas`, guard `['admin','supervisor','dosen']`) — tabel ringkasan (badge status) + matriks per kelas (accordion) + tombol **Unduh PDF/Excel** (blob). Service `services/rekap-tugas.js`. Entri: nav header **"Rekap Tugas"** + link `sidemenu-admin.vue` + tombol di `kelas-lab.vue`.
- [x] **T8.9** — Test `RekapTugasTest` (+7 = 15 total): struktur JSON ringkasan+detail; matriks `tepat`/`telat`/`belum`; scoping Dosen; Mahasiswa 403; guest 401; unduh PDF & Excel.
- [ ] **T8.10 (Tahap 2, ditunda)** — Sinkronisasi otomatis ke Google Sheets (service account, auto-update saat tugas masuk). Menunggu kredensial Google Cloud.

---

## FASE 9: Notifikasi In-App

(PRD 3.10, SRS UC-07, SDD 3.16, 5.14)

> Fase ini **tidak berdiri sendiri** — notifikasi adalah efek samping dari aksi di Fase 3, 4, dan 5. Migration dan Model dikerjakan di sini, tapi integrasi insert notifikasi ke transaksi masing-masing modul dikerjakan bersamaan saat fase tersebut dieksekusi (atau sebagai pass kedua setelah fase terkait selesai).

### Backend
- [x] **T9.1** — Migration tabel `notifikasi` (SDD 3.16): kolom `user_id`, `judul`, `pesan`, `tipe` (enum `pengajuan_masuk`/`status_pengajuan`/`pendaftaran`), `referensi_id`, `is_read`; composite index `(user_id, is_read)`
- [x] **T9.2** — Model `Notifikasi` + relasi `belongsTo User`. _`$table='notifikasi'`, cast `is_read` boolean; `User::notifikasi()` hasMany._
- [x] **T9.3** — `NotifikasiService` — `kirim(userId, judul, pesan, tipe, referensiId)` + helper `kirimKeApprover()` (semua Admin/Supervisor); insert Eloquent ikut transaksi yang sedang berjalan (rollback → batal).
- [x] **T9.4** — Integrasi Peminjaman Ruangan: `store` (transaksi baru) → `kirimKeApprover` tipe `pengajuan_masuk`; `approve`/`reject` (dalam transaksi) → `kirim` ke pengaju tipe `status_pengajuan`.
- [x] **T9.5** — Integrasi Peminjaman Perangkat: `store` → approver; `approve`/`reject` → pengaju (dalam transaksi masing-masing).
- [x] **T9.6** — Integrasi Perpanjangan: `ajukanPerpanjangan` → approver; `PerpanjanganController::approve`/`reject` → pengaju (pemilik peminjaman induk), dalam transaksi.
- [x] **T9.7** — Integrasi Kelas Lab: `KelasLabController::daftar` (dalam transaksi kuota) → `kirim` ke mahasiswa pendaftar tipe `pendaftaran`.
- [x] **T9.8** — Endpoint `GET /api/notifikasi` — list milik sendiri, terbaru dulu, + `unread_count`. _`NotifikasiController::index`._
- [x] **T9.9** — Endpoint `PATCH /api/notifikasi/{id}/read` — tandai dibaca; 403 bila bukan milik sendiri.
- [x] **T9.10** — Endpoint `PATCH /api/notifikasi/read-all` — tandai semua milik sendiri dibaca (didefinisikan sebelum route ber-{id}).
- [x] **T9.11** — Endpoint `DELETE /api/notifikasi/{id}` — hapus milik sendiri; 403 bila bukan milik.
- [x] **T9.12** — Update `GET /api/auth/me`: field `unread_notifications_count` (COUNT `is_read=0` milik user) untuk badge navbar tanpa request tambahan.

### Frontend
- [x] **T9.13** — Komponen `NotificationBell` di navbar: ikon lonceng + badge merah bila `unreadCount > 0`; badge diseed dari `unread_notifications_count` pada `me()`. _`components/notification-bell.vue`, dipasang di `header-component.vue` saat login._
- [x] **T9.14** — Dropdown/panel notifikasi: muncul saat lonceng diklik (memuat `GET /api/notifikasi`); notifikasi belum dibaca ditandai visual (background biru). Klik-di-luar menutup panel (overlay).
- [x] **T9.15** — Tombol "Tandai semua dibaca" + klik item (tandai satu dibaca) + tombol hapus per item.
- [x] **T9.16** — Pinia store `notifikasi` — `items` & `unreadCount`; diperbarui setelah tandai baca/hapus; `reset()` saat logout. _`stores/notifikasi.js`._

### Testing
- [x] **T9.17** — Test: approve `peminjaman_ruangan` membuat `notifikasi` untuk pengaju saja. _`NotifikasiTest`._
- [x] **T9.18** — Test: pengajuan baru `peminjaman_ruangan` membuat notifikasi untuk semua Supervisor & Admin (bukan pengaju). _`NotifikasiTest`._
- [x] **T9.19** — Test: `GET /api/auth/me` mengembalikan `unread_notifications_count` akurat. _`NotifikasiTest`._
- [x] **T9.20** — Test: `PATCH /api/notifikasi/{id}/read` ditolak (403) bila bukan milik sendiri. _`NotifikasiTest`._
- [x] **T9.21** — Test: transaksi pemicu rollback → insert notifikasi ikut rollback (tanpa orphan). _`NotifikasiTest` (DB::transaction + throw → `assertDatabaseCount('notifikasi', 0)`)._

---

## Catatan Pengerjaan untuk AI Agent

1. **Urutan fase bersifat dependency, bukan kaku** — Fase 0 dan Fase 1 harus selesai duluan karena hampir semua modul butuh user & auth. Fase 2–9 secara teknis bisa dikerjakan paralel/diloncat sesuai prioritas, tapi disarankan urut karena beberapa relasi data saling bergantung (mis. Fase 4 & 5 sama-sama butuh data `users`/`mahasiswa` dari Fase 1).
2. **Setiap task backend yang menyentuh data sensitif (approve/reject, kelola user) wajib dicek ulang ke matriks RBAC di `2_SRS.md` Bagian 1** sebelum dianggap selesai.
3. Checklist test di tiap fase adalah **minimum**, bukan daftar lengkap — AI Agent boleh menambah test lain yang relevan selama tidak mengurangi yang sudah ada di sini.
