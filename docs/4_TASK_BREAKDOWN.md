# 4. Task Breakdown

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Versi Dokumen**: 1.0
**Dokumen Acuan**: `1_PRD.md`, `2_SRS.md`, `3_SDD.md`

> Dokumen ini adalah **rencana kerja/backlog** yang dieksekusi AI Agent (Hermes/Roo Code). Setiap task mencantumkan rujukan ke dokumen sumber (PRD/SRS/SDD) agar AI Agent membaca konteks yang tepat sebelum mengerjakan — lihat `.clinerules/agent.md` Bagian 9 (Workflow Kerja Standar).
>
> **Cara update status**: ubah `[ ]` menjadi `[x]` setelah task selesai **dan** test relevan lulus. AI Agent dilarang menandai selesai sebelum keduanya terpenuhi (lihat `agent.md` Bagian 6).

---

## FASE 0: Fondasi Proyek

Task persiapan sebelum modul fitur apapun bisa dikerjakan.

- [ ] **T0.1** — Inisialisasi project Laravel 13.16 di `src/backend` (`composer create-project laravel/laravel`), set PHP 8.5.7 di `composer.json`
- [ ] **T0.2** — Inisialisasi project Vue 3 + Vite di `src/frontend` (`npm create vite@latest -- --template vue`)
- [ ] **T0.3** — Konfigurasi koneksi MySQL di `src/backend/.env`
- [ ] **T0.4** — Install & konfigurasi Laravel Sanctum untuk SPA authentication (SDD Bagian 1)
- [ ] **T0.5** — Konfigurasi CORS (`config/cors.php`) agar backend menerima request dari origin frontend, `supports_credentials` aktif (SDD Bagian 1)
- [ ] **T0.6** — Install & konfigurasi Laravel Socialite untuk Google OAuth (SDD Bagian 2)
- [ ] **T0.7** — Install Vue Router & Pinia di frontend; setup struktur folder `components/`, `views/`, `stores/`, `services/`, `router/` (`agent.md` Bagian 5)
- [ ] **T0.8** — Setup Axios instance di `src/frontend/src/services` dengan base URL dari `.env` (`VITE_API_BASE_URL`)
- [ ] **T0.9** — Install Laravel Pint (backend) & Prettier/ESLint (frontend), verifikasi `format on save` di `.vscode/settings.json` berjalan
- [ ] **T0.10** — Setup PHPUnit/Pest config dasar di `tests/Backend`

---

## FASE 1: Autentikasi & Manajemen User

Fondasi yang harus selesai sebelum modul lain bisa diuji end-to-end (hampir semua modul butuh user yang sudah login).

### Backend
- [ ] **T1.1** — Migration tabel `users` sesuai SDD 3.1 (kolom `google_id`, `avatar`, `role` enum, `password` nullable)
- [ ] **T1.2** — Migration tabel `dosen` sesuai SDD 3.2 (relasi `user_id` wajib unique)
- [ ] **T1.3** — Migration tabel `mahasiswa` sesuai SDD 3.3 (kolom `nim`, `angkatan` — lihat aturan auto-extract & immutable)
- [ ] **T1.4** — Model `User`, `Dosen`, `Mahasiswa` + relasi Eloquent (`User::dosen()`, `User::mahasiswa()`, dst.)
- [ ] **T1.5** — Endpoint `GET /api/auth/google/redirect` & `GET /api/auth/google/callback` — implementasi alur SDD Bagian 2 lengkap: validasi domain email, auto-create `users` + `dosen`/`mahasiswa`, ekstraksi NIM & angkatan (format `"20" . dua_digit_awal`)
- [ ] **T1.6** — Endpoint `POST /api/auth/login` (login manual) — tolak jika `password` NULL, dengan pesan sesuai SRS UC-01 skenario 1b
- [ ] **T1.7** — Endpoint `POST /api/auth/set-password` & `PATCH /api/auth/change-password` (SRS UC-01b)
- [ ] **T1.8** — Endpoint `POST /api/auth/logout` & `GET /api/auth/me`
- [ ] **T1.9** — Seeder `UserSeeder` untuk membuat akun Admin & Supervisor manual (SDD Bagian 2, catatan implementasi)
- [ ] **T1.10** — Policy dasar untuk role-based access control mengacu matriks RBAC SRS Bagian 1
- [ ] **T1.11** — Endpoint `GET /api/users`, `PATCH /api/users/{id}`, `DELETE /api/users/{id}` (Admin only)
- [ ] **T1.12** — Form Request validasi untuk seluruh endpoint di atas

### Frontend
- [ ] **T1.13** — Halaman Login: tombol "Login dengan Google" + form "Login dengan Email & Password"
- [ ] **T1.14** — Halaman Profil: form "Atur Password" (jika belum ada) / "Ubah Password" (jika sudah ada), kondisional sesuai state user
- [ ] **T1.15** — Pinia store `auth` — menyimpan token, data user, role; dipakai global untuk proteksi route
- [ ] **T1.16** — Vue Router navigation guard — redirect ke dashboard sesuai role, blokir akses halaman yang tidak sesuai hak akses
- [ ] **T1.17** — Halaman Kelola User (Admin only) — list, edit role, hapus user

### Testing
- [ ] **T1.18** — Test: domain email non-UNSIL ditolak saat login Google
- [ ] **T1.19** — Test: login Google pertama kali membuat `users` + entri `dosen`/`mahasiswa` otomatis sesuai role
- [ ] **T1.20** — Test: ekstraksi NIM dan angkatan dari email mahasiswa menghasilkan nilai yang benar
- [ ] **T1.21** — Test: login manual ditolak jika `password` masih NULL
- [ ] **T1.22** — Test: field `nim` dan `angkatan` tidak bisa diubah lewat endpoint update profil mahasiswa

---

## FASE 2: Halaman Informasi Lab

Modul tampilan informasi publik (PRD 2.5, SDD 3.12).

### Backend
- [ ] **T2.1** — Migration tabel `info_lab` (SDD 3.12)
- [ ] **T2.2** — Model `InfoLab`
- [ ] **T2.3** — Endpoint `GET /api/info-lab/{tipe}` & `PATCH /api/info-lab/{tipe}` (Admin only untuk update)
- [ ] **T2.4** — Seeder data awal untuk tipe `beranda`, `visi_misi`, `kepala_lab`, `roadmap_kk`
- [ ] **T2.5** — Endpoint `GET /api/dosen` & `GET /api/dosen/{id}` dengan eager load relasi `user` (SDD 3.2 catatan penting)
- [ ] **T2.6** — Endpoint `PATCH /api/dosen/{id}` — izinkan update oleh pemilik (Dosen) atau Admin/Supervisor

### Frontend
- [ ] **T2.7** — Halaman Beranda
- [ ] **T2.8** — Halaman Visi & Misi
- [ ] **T2.9** — Halaman Profil Kepala Lab
- [ ] **T2.10** — Halaman Daftar Dosen (list) + halaman Detail Profil Dosen
- [ ] **T2.11** — Halaman Roadmap Laboratorium
- [ ] **T2.12** — Panel kelola konten info lab (Admin only)

### Testing
- [ ] **T2.13** — Test: hanya Admin yang bisa update `info_lab` dan data dosen milik orang lain

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
- [ ] **T3.9** — Endpoint `POST /api/peminjaman-ruangan` — Form Request **wajib** validasi bentrok terhadap dua sumber sekaligus: (1) `peminjaman_ruangan` berstatus `disetujui`, dan (2) `kelas_lab` aktif pada ruangan + tanggal + rentang jam yang sama (SRS UC-02 aturan validasi kunci)
- [ ] **T3.10** — Endpoint `PATCH /api/peminjaman-ruangan/{id}/approve` & `/reject` — saat approve, backend **wajib** menjalankan ulang validasi bentrok (kondisi bisa berubah antara saat pengaju submit dan saat Supervisor approve)
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

(PRD 3.4, SRS UC-03, SDD 3.6, 3.7, 3.8)

### Backend
- [ ] **T4.1** — Migration tabel `perangkat` (SDD 3.6)
- [ ] **T4.2** — Migration tabel `peminjaman_perangkat` (SDD 3.7)
- [ ] **T4.3** — Migration tabel `perpanjangan_peminjaman` (SDD 3.8)
- [ ] **T4.4** — Model `Perangkat`, `PeminjamanPerangkat`, `PerpanjanganPeminjaman` + relasi
- [ ] **T4.5** — Endpoint CRUD `/api/perangkat` (Admin/Supervisor)
- [ ] **T4.6** — Endpoint `POST /api/peminjaman-perangkat` (Mahasiswa saja — SRS Bagian 1)
- [ ] **T4.7** — Endpoint `PATCH /api/peminjaman-perangkat/{id}/approve` & `/reject`
- [ ] **T4.8** — Endpoint `POST /api/peminjaman-perangkat/{id}/perpanjangan` — **wajib** validasi tanggal kembali rencana belum lewat (SRS UC-03 aturan validasi kunci)
- [ ] **T4.9** — Endpoint `PATCH /api/perpanjangan/{id}/approve` & `/reject`

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

(PRD 3.5, SRS UC-04, SDD 3.9)

### Backend
- [ ] **T5.1** — Migration tabel `presensi` (SDD 3.9)
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

(PRD 3.6, SRS UC-05, SDD 3.10)

### Backend
- [ ] **T6.1** — Migration tabel `sertifikasi` (SDD 3.10 — murni katalog, tanpa relasi ke `users`)
- [ ] **T6.2** — Model `Sertifikasi`
- [ ] **T6.3** — Endpoint CRUD `/api/sertifikasi` (Create/Update/Delete: Admin/Supervisor; Read: semua role)

### Frontend
- [ ] **T6.4** — Halaman Katalog Sertifikasi (list + detail, dengan tautan eksternal ke penyelenggara)
- [ ] **T6.5** — Panel kelola katalog sertifikasi (Admin/Supervisor)

### Testing
- [ ] **T6.6** — Test: Mahasiswa hanya bisa Read, tidak bisa Create/Update/Delete katalog sertifikasi

---

## FASE 7: Portofolio Mahasiswa

(PRD 3.7, SDD 3.11)

### Backend
- [ ] **T7.1** — Migration tabel `portofolio` (SDD 3.11)
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

## Catatan Pengerjaan untuk AI Agent

1. **Urutan fase bersifat dependency, bukan kaku** — Fase 0 dan Fase 1 harus selesai duluan karena hampir semua modul butuh user & auth. Fase 2–8 secara teknis bisa dikerjakan paralel/diloncat sesuai prioritas, tapi disarankan urut karena beberapa relasi data saling bergantung (mis. Fase 4 & 5 sama-sama butuh data `users`/`mahasiswa` dari Fase 1).
2. **Setiap task backend yang menyentuh data sensitif (approve/reject, kelola user) wajib dicek ulang ke matriks RBAC di `2_SRS.md` Bagian 1** sebelum dianggap selesai.
3. Checklist test di tiap fase adalah **minimum**, bukan daftar lengkap — AI Agent boleh menambah test lain yang relevan selama tidak mengurangi yang sudah ada di sini.
