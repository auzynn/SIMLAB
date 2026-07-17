# Ringkasan Fitur — Final

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Dokumen Acuan**: `1_PRD_FINAL.md`, `2_SRS_FINAL.md`, `3_SDD_FINAL.md`

> Dokumen final ini merangkum **seluruh fitur/modul aplikasi yang telah selesai dibangun**, disusun per domain dan per peran pengguna. Ini adalah gambaran "apa yang bisa dilakukan aplikasi", bukan urutan pengerjaan.

---

## 1. Daftar Modul (per Domain)

### 1.1 Autentikasi & Akun
- Login via **Google OAuth UNSIL** (pembuatan akun otomatis + penentuan role dari domain email)
- Login manual (email + password) sebagai alternatif setelah password diatur
- Atur password pertama kali, ubah password, dan reset password (khusus akun Google)
- Kelola **Profil Saya** (3 tab: Akun / Data Pribadi / Data Akademik): edit data diri, unggah foto profil
- 4 peran: Admin, Supervisor, Dosen, Mahasiswa dengan hak akses berbeda (RBAC)
- **Delegasi Asisten Lab**: Admin menetapkan Mahasiswa ↔ Supervisor

### 1.2 Informasi & Profil Lab
- Halaman **Beranda** (dashboard adaptif per role)
- **Profil Kepala Laboratorium** (kartu identitas terstruktur dari profil dosen tertaut)
- **Visi & Misi Laboratorium**
- **Daftar Dosen** + halaman **Detail Dosen** (biografi, bidang minat, credential, publikasi, buku, roadmap riset)
- **Roadmap Laboratorium** (tingkat KK JKF)
- Editor konten (WYSIWYG) untuk Admin mengelola konten informasi lab
- Master **Bidang Minat** (dikelola Admin/Supervisor, dipilih Dosen)

### 1.3 Data Master
- **Ruangan** (nama, kapasitas — jumlah peminjaman paralel yang diizinkan pada jam sama, status: tersedia/dipakai/perbaikan)
- **Mata Kuliah** (kode, nama, SKS)
- **Perangkat** (nama, nomor seri, kategori, status: tersedia/dipinjam/perbaikan)
- **Katalog Sertifikasi** (informasi sertifikasi eksternal; Dosen juga boleh menambah entri & mengelola miliknya sendiri via `created_by`)
- Semua CRUD dikelola Admin/Supervisor; read terbuka untuk role login

### 1.4 Peminjaman Ruangan
- Kalender ketersediaan ruangan (kelas mingguan + peminjaman disetujui, dikelompokkan Minggu ini / Mendatang)
- Form pengajuan Mahasiswa (mode Satu hari / Beberapa hari; jam operasional 07.00–17.00 WIB)
- **Validasi bentrok jadwal dua-arah** (peminjaman ↔ jadwal Kelas Lab), **berbasis kapasitas ruangan** (beberapa peminjaman paralel diizinkan hingga kapasitas penuh; Kelas Lab memblok penuh)
- Alur persetujuan (Approve/Reject) oleh Supervisor/Admin dengan re-validasi bentrok dalam transaksi ber-lock; slot penuh saat approve → status otomatis **kadaluarsa** + notifikasi ke pengaju
- Halaman "Peminjaman Saya" dan panel Persetujuan dengan filter kolom

### 1.5 Kelas Lab / Praktikum
- Pembukaan kelas oleh Dosen (atau Supervisor atas nama Dosen), multi-sesi paralel
- Validasi bentrok jadwal ruangan + jam operasional
- Pendaftaran Mahasiswa dengan **persetujuan** Dosen/Supervisor, kuota memesan slot
- Aturan: satu sesi per mata kuliah, tanpa bentrok jadwal peserta
- Halaman Kelola Kelas (dikelompokkan per mata kuliah), Persetujuan Pendaftaran, Daftar Peserta
- Halaman **Detail Kelas Lab** & **Detail Pertemuan**

### 1.6 Peminjaman & Perpanjangan Perangkat
- Katalog perangkat dengan badge status
- Pengajuan pinjam (Mahasiswa), approve/reject, konfirmasi pengembalian
- Pengajuan **perpanjangan** (ditolak bila tanggal kembali sudah lewat; approve otomatis memperbarui tanggal kembali induk)
- Peminjaman & persetujuan perangkat menyatu (tab) dengan halaman peminjaman ruangan

### 1.7 Pengumpulan Tugas
- Penetapan **Materi & Deadline per pertemuan** (1–16) oleh Dosen pengampu/Supervisor/Admin
- Materi boleh berdiri sendiri (silabus); "tanpa deadline = tidak ada tugas"
- Mahasiswa mengirim **tautan hasil tugas** per pertemuan (satu tugas per pertemuan)
- Penanda **tepat / terlambat** otomatis (WIB); pengiriman terlambat tetap diterima
- Notifikasi "tugas baru masuk" ke Dosen pengampu + Supervisor

### 1.8 Rekap Tugas
- **Ringkasan kepatuhan** per kelas (status: perhatian / berjalan / beres)
- **Matriks detail** per kelas (peserta × pertemuan: tepat/telat/belum)
- Unduh **PDF** (landscape) dan **Excel .xlsx** berformat
- Data dihitung on-request (selalu terbaru); Dosen di-scope ke kelasnya

### 1.9 Portofolio Mahasiswa
- Mahasiswa mengelola portofolio riset/proyek/publikasi milik sendiri
- Tab "Portofolio Saya" & "Jelajah Semua" (read terbuka untuk role login)

### 1.10 Laporan (Report)
- Rekap aktivitas lab per rentang tanggal (peminjaman ruangan, perangkat, tugas)
- Unduh **PDF**; akses Admin/Supervisor

### 1.11 Notifikasi In-App
- Lonceng notifikasi + badge belum-dibaca di navbar
- Notifikasi otomatis: status pengajuan, pengajuan baru ke approver, pendaftaran kelas, tugas masuk
- **Pengingat terjadwal**: tenggat tugas terlewati (per jam) & pengembalian perangkat (harian)
- Tandai satu/semua dibaca, hapus per item; tidak hilang otomatis

---

## 2. Fitur per Peran (Ringkas)

### Mahasiswa
Login, kelola profil, lihat jadwal lab, ajukan peminjaman ruangan, daftar Kelas Lab, ajukan peminjaman & perpanjangan perangkat, kumpulkan tugas per pertemuan, kelola portofolio, lihat katalog sertifikasi, terima notifikasi.

### Dosen
Login, kelola profil & roadmap riset, buka & kelola Kelas Lab miliknya, tetapkan materi & deadline pertemuan, setujui/tolak pendaftaran peserta, lihat tugas masuk, unduh Rekap Tugas kelasnya, tambah entri katalog sertifikasi (kelola miliknya sendiri), lihat jadwal lab (read-only), terima notifikasi.

### Supervisor (Asisten Lab)
Login, approve/reject peminjaman ruangan & perangkat + perpanjangan, kelola data master (ruangan/perangkat/mata kuliah/sertifikasi), buka Kelas Lab atas nama Dosen, setujui pendaftaran, unduh Laporan & Rekap Tugas semua kelas, terima notifikasi.

### Admin (Kepala Lab)
Seluruh kewenangan Supervisor + kelola akun user & role, delegasi Asisten Lab, kelola konten informasi/profil lab. Berwenang penuh atas Kelas Lab (buka/ubah/hapus semua kelas dengan menunjuk dosen pengampu + approve/reject pendaftaran).

---

## 3. Cakupan Teknis Final

- **Backend**: Laravel 13 (PHP 8.5), REST API, Laravel Sanctum, Laravel Socialite (Google OAuth)
- **Frontend**: Vue 3 (Composition API) + Vite, Vue Router, Pinia, Axios — SPA
- **Basis Data**: MySQL — 18 tabel utama + 1 tabel pivot (users, dosen, mahasiswa, bidang_minat + pivot dosen_bidang_minat, ruangan, mata_kuliah, peminjaman_ruangan, kelas_lab, kelas_lab_peserta, perangkat, peminjaman_perangkat, perpanjangan_peminjaman, tugas, deadline_pertemuan, sertifikasi, portofolio, info_lab, notifikasi)
- **Otorisasi**: Laravel Gate/Policy per modul, mengacu matriks RBAC
- **Laporan**: PDF (dompdf) & Excel (PhpSpreadsheet)
- **Penjadwalan**: Laravel Scheduler untuk notifikasi pengingat
- **Kualitas**: rangkaian test otomatis (Feature test) untuk seluruh modul + validasi build frontend

> Rincian teknis lengkap: skema tabel di `3_SDD_FINAL.md` Bagian 3, daftar endpoint di Bagian 5.
