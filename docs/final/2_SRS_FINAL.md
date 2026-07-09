# Software Requirements Specification (SRS) — Final

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Unit Terkait**: Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) — Prodi Informatika
**Dokumen Acuan**: `1_PRD_FINAL.md`

> Dokumen final ini memaparkan **aturan validasi bisnis dan hak akses (RBAC)** dari aplikasi yang telah selesai. Skema data & API rinci ada di `3_SDD_FINAL.md`.

---

## 1. Matriks Hak Akses (RBAC)

`C` = Create, `R` = Read, `U` = Update, `D` = Delete. Tanda `–` berarti tidak punya akses ke modul tersebut.

| Modul | Admin | Supervisor | Dosen | Mahasiswa |
|---|---|---|---|---|
| Data User & Role | CRUD | – | – | – |
| Delegasi Asisten Lab (Mahasiswa ↔ Supervisor) | C, R, D | – | – | – |
| Data Master (ruangan, perangkat, mata kuliah) | CRUD | CRUD | – | – |
| Jadwal Peminjaman Lab | CRUD | CRUD | R | R |
| Kelas Lab/Praktikum (jadwal semester) | R saja (tidak bisa membuka kelas) | C (atas permintaan Dosen), R, approve/reject pendaftaran | CRUD (milik sendiri) + approve/reject pendaftaran kelasnya | R, daftar sebagai peserta (butuh persetujuan) |
| Pengajuan Peminjaman Ruangan | R (approve/reject) | R (approve/reject) | – | C, R (milik sendiri) |
| Pengajuan Peminjaman Perangkat & Perpanjangan | R (approve/reject) | R (approve/reject) | – | C, R, U (milik sendiri, sebelum disetujui) |
| Pengumpulan Tugas | R (semua), D | R (semua), D | R (kelas milik sendiri), D | C, R, D (milik sendiri) |
| Deadline & Materi Pertemuan Kelas Lab | CRU (semua kelas) | CRU (semua kelas) | CRU (kelas milik sendiri) | R |
| Sertifikasi (katalog informasi) | CRUD | CRUD | – | R |
| Portofolio Mahasiswa | R | R | R | CRUD (milik sendiri) |
| Profil & Roadmap Dosen | R | R | CRUD (milik sendiri) | R |
| Profil Mahasiswa (data diri, kecuali NPM) | R | R | R (mahasiswa bimbingan) | U (milik sendiri) |
| Laporan/Report (unduh) | C, R | C, R | – | – |
| Rekap Tugas Kelas Lab (unduh PDF/Excel) | R (semua kelas) | R (semua kelas) | R (kelas milik sendiri) | – |
| Halaman Informasi Lab (Beranda, Visi-Misi, Daftar Dosen, Roadmap Lab, Profil Kepala Lab) | CRUD (kelola konten) | R | R | R |
| Edit Profil Akun Pribadi | U (milik sendiri) | U (milik sendiri) | U (milik sendiri) | U (milik sendiri) |
| Notifikasi In-App | U (tandai baca), D (milik sendiri) | U (tandai baca), D (milik sendiri) | U (tandai baca), D (milik sendiri) | U (tandai baca), D (milik sendiri) |

**Aturan implementasi**:
- Setiap endpoint API diproteksi middleware `auth:sanctum` kecuali endpoint publik yang eksplisit didefinisikan di `3_SDD_FINAL.md`
- Pengecekan role dilakukan lewat Laravel Policy/Gate, bukan pengecekan manual yang tersebar di Controller
- "Milik sendiri" berarti sistem memvalidasi `user_id`/`dosen_id`/`mahasiswa_id` pemilik data cocok dengan user yang sedang login sebelum mengizinkan Update/Delete
- Hubungan "mahasiswa bimbingan" didefinisikan lewat foreign key `dosen_pembimbing_id` pada entri mahasiswa. (Akses Dosen ke tugas kelas mengikuti kepemilikan kelas `dosen_id`, bukan relasi bimbingan.)
- Pada **Edit Profil Akun Pribadi**, field `email` dan `role` immutable (role hanya diubah Admin lewat Kelola User); untuk Mahasiswa, `npm` dan `angkatan` juga immutable. Validasi penolakan dilakukan di backend, bukan hanya disembunyikan di frontend.

---

## 2. Kebutuhan Fungsional per Role

### 2.1 Admin
| ID | Fungsi | Keterangan |
|---|---|---|
| F-AD-01 | Login via Google OAuth UNSIL | Jalur utama, sekaligus pembuatan akun pertama kali |
| F-AD-02 | Login manual (email/password) | Alternatif, aktif setelah password diatur di Profil (UC-01b) |
| F-AD-03 | Kelola data user | CRUD data user lintas role |
| F-AD-03a | Delegasi Asisten Lab (Aslab) | Menetapkan Mahasiswa menjadi Supervisor & mengembalikannya (hanya transisi Mahasiswa↔Supervisor) |
| F-AD-04 | Kelola data dosen | Tambah, ubah, hapus, lihat detail profil dosen |
| F-AD-05 | Kelola konten informasi lab | Update Beranda, Visi-Misi, Profil Kepala Lab |
| F-AD-06 | Kelola roadmap lab (tingkat KK) | Tambah/ubah konten roadmap riset KK JKF |
| F-AD-07 | Kelola jadwal peminjaman lab | Tambah, ubah, hapus jadwal & ruangan |
| F-AD-08 | Approve/reject peminjaman ruangan & perangkat | Sama seperti Supervisor |
| F-AD-09 | Kelola data perangkat & sertifikasi | CRUD penuh |
| F-AD-10 | Terima & kelola notifikasi in-app | Tandai baca (satu/semua), hapus; notifikasi masuk otomatis saat ada pengajuan baru menunggu persetujuan |
| F-AD-11 | Edit profil akun pribadi | Update nama & no. telepon, unggah foto profil, atur/ubah password |
| F-AD-12 | Unduh laporan & Rekap Tugas | Report (PDF, per rentang tanggal) + Rekap Tugas semua kelas (PDF/Excel) — lihat UC-06, UC-06a |

### 2.2 Supervisor (Asisten Lab)
| ID | Fungsi | Keterangan |
|---|---|---|
| F-SV-01 | Login via Google OAuth UNSIL + login manual | Sama seperti Admin |
| F-SV-02 | Lihat daftar dosen & profil lab | Read-only |
| F-SV-03 | Kelola jadwal peminjaman lab | Tambah, ubah, hapus jadwal & ruangan |
| F-SV-04 | Approve/reject pengajuan peminjaman ruangan | Wajib validasi tidak ada bentrok jadwal sebelum approve |
| F-SV-05 | Approve/reject pengajuan peminjaman & perpanjangan perangkat | — |
| F-SV-06 | Kelola data perangkat | CRUD nomor seri, status (Tersedia/Dipinjam/Perbaikan) |
| F-SV-07 | Kelola katalog informasi sertifikasi | Tambah/ubah/hapus info sertifikasi eksternal |
| F-SV-08 | Unduh laporan & Rekap Tugas | Report (rentang tanggal, PDF) + Rekap Tugas semua kelas (PDF/Excel) — lihat UC-06, UC-06a |
| F-SV-09 | Membuka Kelas Lab/Praktikum atas permintaan Dosen | Membuat jadwal kelas atas nama Dosen terkait; Admin tidak memiliki kewenangan ini |
| F-SV-10 | Terima & kelola notifikasi in-app | Tandai baca (satu/semua), hapus; notifikasi masuk otomatis saat ada pengajuan baru menunggu persetujuan |
| F-SV-11 | Edit profil akun pribadi | Update nama & no. telepon, unggah foto profil, atur/ubah password |

### 2.3 Dosen
| ID | Fungsi | Keterangan |
|---|---|---|
| F-DS-01 | Login via Google OAuth UNSIL + login manual | Sama seperti Admin |
| F-DS-02 | Edit profil pribadi | Update data diri (nama, no. telepon, NIDN, jabatan fungsional, tempat & tanggal lahir, Bidang Minat), unggah foto profil; email & peran tidak dapat diubah sendiri |
| F-DS-03 | Kelola portofolio & roadmap riset pribadi | CRUD konten riset pribadi |
| F-DS-04 | Kelola tugas kelas & rekap kepatuhan | Menetapkan materi & deadline per pertemuan (kelas yang diampu), melihat tugas masuk, mengunduh Rekap Tugas (PDF/Excel) kelasnya — lihat UC-04, UC-06a |
| F-DS-05 | Lihat jadwal ketersediaan lab | Read-only. Dosen **tidak** mengajukan peminjaman ruangan — itu hak Mahasiswa (UC-02) |
| F-DS-06 | Membuka & mengelola Kelas Lab/Praktikum | Tentukan mata kuliah, ruangan, jadwal berulang mingguan selama satu semester, kuota peserta (30-40), multi-sesi paralel; setujui/tolak pendaftaran peserta — lihat UC-02a |
| F-DS-07 | Lihat daftar mata kuliah | Read-only, untuk dipilih saat membuka Kelas Lab |
| F-DS-08 | Terima & kelola notifikasi in-app | Tandai baca (satu/semua), hapus; menerima notifikasi saat ada tugas baru masuk pada kelasnya |

### 2.4 Mahasiswa
| ID | Fungsi | Keterangan |
|---|---|---|
| F-MH-01 | Registrasi akun (otomatis) | Akun **tercipta otomatis** saat login Google pertama kali dengan email `@student.unsil.ac.id` |
| F-MH-02 | Login via Google OAuth UNSIL + login manual | Sama seperti role lain |
| F-MH-03 | Edit profil pribadi | Update data diri (nama, no. telepon, prodi), unggah foto profil; email, peran, NPM & angkatan tidak dapat diubah |
| F-MH-04 | Lihat jadwal ketersediaan lab | Read-only, tampilan kalender |
| F-MH-05 | Ajukan peminjaman ruangan lab | Isi form (tanggal, waktu, keperluan); slot yang sudah dipakai Kelas Lab tidak tersedia |
| F-MH-05a | Mendaftar sesi Kelas Lab/Praktikum | Pilih sesi (mis. Kelas A/B/C) selama kuota belum penuh |
| F-MH-06 | Ajukan peminjaman perangkat | Pilih dari daftar perangkat status "Tersedia" |
| F-MH-07 | Ajukan perpanjangan peminjaman perangkat | Sebelum batas waktu pinjam habis |
| F-MH-08 | Kumpulkan tugas Kelas Lab | Kirim tautan hasil tugas per pertemuan (1–16) untuk kelas yang diikuti (status peserta `disetujui`); satu tugas per pertemuan; ditandai "Terlambat" bila lewat deadline — lihat UC-04 |
| F-MH-09 | Lihat katalog informasi sertifikasi | Melihat daftar sertifikasi eksternal; pendaftaran dilakukan langsung ke penyelenggara |
| F-MH-10 | Kelola portofolio pribadi | CRUD hasil riset/proyek/publikasi milik sendiri |
| F-MH-11 | Terima & kelola notifikasi in-app | Tandai baca (satu/semua), hapus; menerima notifikasi status pengajuan dan konfirmasi pendaftaran Kelas Lab |

---

## 3. Spesifikasi Use Case Kritis

### UC-01: Login
**Aktor**: Admin, Dosen, Supervisor, Mahasiswa

> Akun **hanya bisa dibuat** lewat Login Google UNSIL (`@unsil.ac.id` untuk Dosen, `@student.unsil.ac.id` untuk Mahasiswa; Admin/Supervisor disiapkan sistem). Login manual (email + password) adalah **alternatif** yang baru bisa dipakai setelah user mengatur password sendiri di halaman Profil — lihat UC-01b.

**1a. Login via Google (jalur utama, sekaligus pembuatan akun baru)**

| Skenario Normal | Skenario Alternatif (Domain Email Tidak Valid) |
|---|---|
| 1. Pengguna menekan *Login dengan Google* | 1. Pengguna menekan *Login dengan Google* |
| 2. Memilih akun Google institusi | 2. Memilih akun Google **non-UNSIL** |
| 3. Sistem memvalidasi domain email | 3. Sistem mendeteksi domain tidak sesuai |
| 4. Jika akun baru: sistem membuat `users` + entri `dosen`/`mahasiswa` otomatis; jika sudah ada: lanjut login | 4. Sistem menolak, pesan "Gunakan email institusi UNSIL" |
| 5. Sistem mengarahkan ke dashboard sesuai role | |

**1b. Login Manual (alternatif, hanya untuk akun yang sudah set password)**

| Skenario Normal | Skenario Alternatif |
|---|---|
| 1. Pengguna memilih *Login dengan Email & Password* | 1a. Password salah |
| 2. Memasukkan email & password | 1b. Sistem menampilkan "Email atau password salah" |
| 3. Sistem mengecek email terdaftar **dan** kolom `password` sudah terisi | 2a. Email terdaftar tapi `password` masih NULL |
| 4. Sistem memvalidasi kecocokan password | 2b. Sistem menolak: "Akun ini belum mengaktifkan login manual. Silakan login dengan Google UNSIL, lalu atur password di halaman Profil." |
| 5. Sistem mengarahkan ke dashboard sesuai role | |

**Kebutuhan teknis**: Laravel Socialite untuk Google OAuth, token via Laravel Sanctum untuk SPA, hashing password bcrypt bawaan Laravel.

### UC-01b: Mengatur & Mengubah Password
**Aktor**: semua role (terhadap akun masing-masing)

| Atur Password Pertama Kali | Ubah Password (Sudah Pernah Diatur) |
|---|---|
| 1. User (login via Google) membuka Profil → "Atur Password Login" | 1. User membuka Profil → "Ubah Password" |
| 2. Mengisi password baru + konfirmasi | 2. Mengisi password lama + password baru + konfirmasi |
| 3. Sistem menyimpan password (di-hash) | 3. Sistem memvalidasi password lama cocok |
| | 4. Jika cocok, perbarui password; jika tidak, tolak dengan pesan error |

### UC-02: Pengajuan Peminjaman Ruangan Lab
**Aktor**: Mahasiswa (pengaju), Supervisor/Admin (penyetuju)

> Slot yang sudah terisi jadwal Kelas Lab/Praktikum diperlakukan sama seperti pengajuan berstatus "Disetujui" — ikut dicek dalam validasi bentrok di langkah 3.

| Skenario Normal | Skenario Alternatif (Bentrok/Ruangan Tidak Tersedia) |
|---|---|
| 1. Pengaju membuka kalender ketersediaan ruangan | 1. Pengaju membuka kalender ketersediaan ruangan |
| 2. Memilih ruangan, mengisi tanggal/waktu/keperluan | 2. Memilih ruangan & waktu yang **sudah dipesan/disetujui, terisi jadwal Kelas Lab, atau ruangan tidak 'tersedia'** |
| 3. Sistem memvalidasi tidak ada bentrok (termasuk terhadap jadwal Kelas Lab) & status ruangan 'tersedia' | 3. Sistem mendeteksi bentrok/tidak tersedia → menolak dengan pesan jelas |
| 4. Pengajuan tersimpan status "Menunggu Persetujuan" | 4. Pengaju diarahkan memilih ulang waktu/ruangan lain |
| 5. Supervisor/Admin meninjau → Approve/Reject | |
| 6. Status terupdate, pengaju menerima notifikasi | |

**Aturan validasi kunci**: Sistem mencegah dua pengajuan "Disetujui" pada ruangan & rentang waktu sama, mencegah pengajuan pada slot Kelas Lab, dan mencegah peminjaman pada ruangan yang statusnya bukan "tersedia". Jam peminjaman wajib dalam rentang operasional lab **07.00–17.00 WIB**. Peminjaman ruangan **hanya diajukan Mahasiswa**. Validasi di level backend via Form Request.

> **Pengajuan satu/beberapa hari**: form mendukung mode **Satu hari** atau **Beberapa hari** (pilih ≥2 hari + tanggal per hari, jam sama). Tiap tanggal menghasilkan satu pengajuan terpisah; backend memvalidasi bentrok per tanggal.

### UC-02a: Membuka & Mendaftar Kelas Lab/Praktikum
**Aktor**: Dosen/Supervisor (pembuka kelas), Mahasiswa (pendaftar)

> Mekanisme **terpisah** dari UC-02. Dibuka di awal semester untuk sesi terjadwal tetap (mingguan) hingga akhir semester. **Admin tidak memiliki kewenangan membuka Kelas Lab.**

| Skenario Normal (Pembukaan Kelas) | Skenario Pendaftaran Peserta |
|---|---|
| 1. Dosen (atau Supervisor atas permintaan Dosen) membuka menu Kelas Lab | 1. Mahasiswa membuka menu Kelas Lab |
| 2. Mengisi data: mata kuliah, ruangan, hari & jam (berulang mingguan), tanggal mulai-selesai, kuota (maks. 30-40) | 2. Melihat daftar sesi tersedia (mis. Kelas A 08.00, Kelas B 10.00) + sisa kuota |
| 3. Dapat menambahkan sesi paralel, kuota independen | 3. Memilih satu sesi dan mendaftar → status **menunggu persetujuan** |
| 4. Sistem memvalidasi tidak bentrok dengan jadwal ruangan lain & status ruangan 'tersedia' | 4. Sistem memvalidasi kuota, 1-sesi-per-mata-kuliah, dan tidak bentrok jadwal kelas lain |
| 5. Jadwal tersimpan & langsung "mengisi" kalender ruangan seluruh rentang semester | 5. **Dosen pengampu (atau Supervisor) menyetujui/menolak**; mahasiswa resmi jadi peserta setelah disetujui |

**Aturan validasi kunci**:
- Pendaftaran peserta wajib disetujui Dosen pengampu atau Supervisor (status `menunggu` → `disetujui`/`ditolak`)
- Menolak pendaftaran baru jika kuota penuh (slot dihitung dari `menunggu` + `disetujui`)
- Satu mahasiswa hanya boleh satu sesi per mata kuliah, boleh ambil mata kuliah berbeda selama tidak bentrok jadwal
- Mencegah pembukaan kelas yang bentrok dengan jadwal ruangan terisi atau status ruangan bukan 'tersedia'
- Jam kelas wajib dalam rentang **07.00–17.00 WIB**

### UC-03: Peminjaman & Perpanjangan Perangkat
**Aktor**: Mahasiswa

| Skenario Normal | Skenario Tambahan (Perpanjangan) |
|---|---|
| 1. Membuka daftar perangkat berstatus "Tersedia" | 1. Membuka halaman *Peminjaman Saya* |
| 2. Memilih perangkat yang dibutuhkan | 2. Memilih item yang ingin diperpanjang |
| 3. Sistem mencatat pengajuan; setelah disetujui, status perangkat menjadi "Dipinjam" | 3. Mengajukan perpanjangan sebelum batas waktu pinjam habis |
| | 4. Pengajuan masuk antrian persetujuan Supervisor/Admin |

**Aturan validasi kunci**: Mahasiswa **tidak dapat** mengajukan perpanjangan setelah batas waktu pinjam terlewati (harus mengembalikan dan mengajukan peminjaman baru). Saat perpanjangan disetujui, tanggal kembali rencana peminjaman induk diperbarui otomatis.

### UC-04: Pengumpulan Tugas Kelas Lab
**Aktor**: Mahasiswa (pengumpul), Dosen pengampu/Supervisor/Admin (penetap deadline & peninjau)

**Menetapkan materi/deadline (Dosen pengampu / Supervisor / Admin)**:
1. Reviewer membuka Detail Kelas Lab → memilih salah satu dari **16 pertemuan**
2. Mengisi **Nama Materi** dan/atau **Deadline**. Materi boleh berdiri sendiri (silabus) tanpa deadline; pertemuan tanpa deadline **tidak dihitung sebagai tugas**
3. Bila materi & deadline dikosongkan keduanya, record pertemuan dihapus

**Mengumpulkan tugas (Mahasiswa)**:

| Skenario Normal | Skenario Alternatif |
|---|---|
| 1. Membuka Kelas Lab yang diikuti → *Kirim Tugas* | 1a. Bukan peserta `disetujui` → ditolak |
| 2. Memilih pertemuan (1–16), mengisi judul + tautan | 2a. Sudah pernah mengirim untuk pertemuan yang sama → ditolak (satu tugas per pertemuan) |
| 3. Sistem menyimpan tugas; bila lewat deadline, ditandai **"Terlambat"** (tetap diterima) | 3a. Tautan bukan URL valid → ditolak |
| 4. Dosen pengampu & Supervisor menerima notifikasi "tugas baru masuk" | |

**Aturan validasi kunci**:
- Tugas hanya boleh dikirim untuk Kelas Lab yang diikuti mahasiswa dengan status peserta **`disetujui`**
- **Satu tugas per (kelas, pertemuan, mahasiswa)** — pengiriman kedua ditolak
- **"Tanpa deadline = tidak ada tugas"**: status tepat/telat/belum hanya dihitung untuk pertemuan yang memiliki `deadline`
- Deadline **tidak memblokir** pengiriman terlambat — hanya menandai; timestamp dibandingkan dalam **waktu lokal WIB**
- Penetapan materi/deadline hanya oleh Dosen pengampu kelas (`dosen_id` cocok), Supervisor, atau Admin

### UC-05: Melihat Katalog Informasi Sertifikasi
**Aktor**: Mahasiswa (pengunjung informasi), Admin/Supervisor (pengelola konten)

> Modul ini **murni informasional**. Sistem tidak menangani pendaftaran sertifikasi (tidak ada form registrasi, upload berkas, kuota, atau status seleksi). Hanya menampilkan katalog sertifikasi eksternal (mis. Mikrotik, Oracle, Cisco, RedHat) sebagai referensi. Pendaftaran dilakukan mahasiswa langsung ke penyelenggara, di luar sistem.

| Skenario Normal |
|---|
| 1. Admin/Supervisor menambahkan/mengubah entri sertifikasi (nama, penyelenggara, jadwal, persyaratan, cara/tautan pendaftaran eksternal) |
| 2. Mahasiswa membuka menu Sertifikasi |
| 3. Sistem menampilkan daftar sertifikasi beserta detail informasinya |
| 4. Mahasiswa membaca detail dan, jika berminat, mengikuti instruksi/tautan menuju penyelenggara eksternal |

### UC-06: Mengunduh Laporan
**Aktor**: Supervisor & Admin

1. Membuka menu Laporan
2. Sistem menampilkan rekap (default: 30 hari terakhir)
3. Memilih rentang tanggal kustom
4. Sistem menampilkan hasil rekap sesuai rentang
5. Memilih *Download PDF*
6. Sistem menghasilkan dan mengunduh file laporan

### UC-06a: Rekap Tugas Kelas Lab (per pertemuan)
**Aktor**: Admin, Supervisor, Dosen (Dosen di-scope ke kelas miliknya)

1. Membuka menu **Rekap Tugas**
2. Sistem menampilkan **ringkasan kepatuhan** semua kelas (untuk Dosen: hanya kelasnya) + **matriks detail per kelas** (peserta × pertemuan bertugas: tepat/telat/belum)
3. Data selalu mencerminkan tugas terbaru yang masuk (dihitung on-request)
4. Memilih **Unduh PDF** atau **Unduh Excel (.xlsx)**
5. Sistem menghasilkan dan mengunduh file rekap terkini

**Aturan validasi kunci**: Otorisasi via Gate `view-rekap-tugas` (Admin/Supervisor/Dosen). Dosen hanya dapat merekap kelas yang `dosen_id`-nya miliknya (scoping di backend). Kolom matriks hanya mencakup pertemuan yang memiliki `deadline`.

### UC-07: Notifikasi In-App
**Aktor**: Semua role (penerima); sistem (pembuat — otomatis, bukan aksi pengguna)

> Notifikasi dibuat **otomatis oleh sistem** sebagai efek samping dari aksi lain. Pengguna tidak membuat notifikasi secara langsung.

**Pemicu dan penerima**:
| Pemicu | Penerima |
|---|---|
| Pengajuan peminjaman ruangan disetujui/ditolak | Pengaju (Mahasiswa) |
| Pengajuan peminjaman perangkat disetujui/ditolak | Pengaju (Mahasiswa) |
| Pengajuan perpanjangan perangkat disetujui/ditolak | Pengaju (Mahasiswa) |
| Ada pengajuan peminjaman ruangan baru (status `menunggu`) | Semua Supervisor & Admin |
| Ada pengajuan peminjaman perangkat baru (status `menunggu`) | Semua Supervisor & Admin |
| Ada pengajuan perpanjangan baru (status `menunggu`) | Semua Supervisor & Admin |
| Pendaftaran Kelas Lab berhasil | Mahasiswa yang mendaftar |
| Tugas baru masuk pada sebuah kelas | Dosen pengampu kelas + semua Supervisor |
| Tenggat tugas terlewati & belum dikumpulkan (terjadwal) | Mahasiswa peserta `disetujui` yang belum mengumpulkan |
| Tenggat pengembalian perangkat mendekati/terlewati (terjadwal) | Mahasiswa peminjam perangkat terkait |

> Dua pemicu terakhir bertipe `pengingat` dan dihasilkan oleh **penjadwalan berkala** — bukan aksi langsung pengguna. Bersifat **idempoten** (tidak menduplikasi pengingat yang sama untuk pasangan pengguna–deadline yang sama).

**Alur pengguna**:
| Skenario Normal | Skenario Tandai Baca / Hapus |
|---|---|
| 1. Sistem membuat entri `notifikasi` otomatis saat pemicu terjadi (dalam transaksi yang sama) | 1. Pengguna mengklik ikon lonceng di navbar |
| 2. Badge angka merah bertambah untuk penerima | 2. Memilih notifikasi → status berubah "sudah dibaca", badge berkurang |
| 3. Pengguna klik ikon lonceng → daftar notifikasi tampil | 3. Atau: menekan "Tandai Semua Sudah Dibaca" |
| 4. Notifikasi belum dibaca ditandai secara visual | 4. Menghapus notifikasi satu per satu; tidak hilang otomatis |

**Aturan validasi kunci**:
- Insert `notifikasi` dilakukan dalam **DB transaction yang sama** dengan aksi pemicunya — jika salah satu gagal, keduanya rollback
- Jumlah notifikasi belum dibaca disertakan di response `GET /api/auth/me` sebagai field `unread_notifications_count`
- Tidak ada auto-delete atau TTL — notifikasi hanya hilang jika pengguna eksplisit menghapusnya

---

## 4. Kebutuhan Non-Fungsional (NFR)

### 4.1 Keamanan
- Setiap endpoint API diproteksi middleware `auth:sanctum`, kecuali endpoint publik (login, Google OAuth callback, dan baca konten informasi lab / profil dosen)
- Pengecekan role/hak akses ketat sesuai matriks RBAC di Bagian 1, via Laravel Policy/Gate
- Pencegahan SQL Injection, XSS, dan CSRF menggunakan fitur bawaan Laravel (Eloquent ORM, Form Request validation)
- Password disimpan dengan hashing bawaan Laravel (bcrypt), tidak pernah disimpan/dikirim plain text
- File upload (avatar, lampiran konten) divalidasi tipe dan ukuran sebelum disimpan

### 4.2 Ketersediaan & Performa
- Aplikasi dikembangkan sebagai **Single Page Application (SPA)** terpisah dari backend, untuk transisi cepat dan penghematan bandwidth lewat API JSON
- Query yang menampilkan data dalam jumlah besar menggunakan pagination

### 4.3 Pemeliharaan
- Pemisahan kode bersih antara logika bisnis backend (Controllers, Models, Policies, Form Requests) dan presentasi frontend (Vue Components, Composables)
- Mengikuti standar format kode otomatis: Laravel Pint (backend), Prettier (frontend)
- Setiap perubahan skema database lewat migration

### 4.4 Kualitas Perangkat Lunak
- **Ketersediaan**: dapat diandalkan menangani pengumpulan tugas dan pengajuan peminjaman setiap hari kerja lab
- **Ketepatan**: alur sesuai use case di Bagian 3, termasuk skenario alternatif/error handling
- **Pemulihan data**: backup database berkala; pemulihan dari backup terakhir bila terjadi kerusakan

---

## 5. Lingkup di Luar Dokumen Ini
- Visi produk, persona, dan alur pengguna tingkat tinggi → lihat `1_PRD_FINAL.md`
- Skema database (ERD), struktur API, dan arsitektur sistem → lihat `3_SDD_FINAL.md`
- Daftar fitur/modul final → lihat `4_RINGKASAN_FITUR.md`
