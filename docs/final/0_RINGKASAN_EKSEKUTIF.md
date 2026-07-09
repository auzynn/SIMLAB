# Ringkasan Eksekutif — SIM Lab. Riset

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Unit**: Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) — Program Studi Informatika
**Status**: Aplikasi selesai dibangun dan siap digunakan

> Dokumen ini adalah **ringkasan tingkat tinggi** dari aplikasi final untuk pembaca umum (non-teknis). Rincian lengkap ada pada dokumen pendamping di folder ini: `1_PRD_FINAL.md` (produk & alur), `2_SRS_FINAL.md` (hak akses & aturan), `3_SDD_FINAL.md` (arsitektur & basis data), dan `4_RINGKASAN_FITUR.md` (daftar fitur).

---

## 1. Apa Itu SIM Lab. Riset

SIM Lab. Riset adalah sistem informasi berbasis web yang **mendigitalisasi seluruh administrasi Laboratorium Riset KK JKF** ke dalam satu platform terpusat. Sebelumnya, proses peminjaman ruangan, peminjaman perangkat, penjadwalan kelas praktikum, dan pemantauan tugas dilakukan manual sehingga sulit dilacak dan rawan bentrok jadwal.

Dengan sistem ini, seluruh proses menjadi **transparan, terlacak, dan tervalidasi otomatis**, dengan akses yang disesuaikan untuk setiap peran pengguna.

---

## 2. Empat Peran Pengguna

| Peran | Ringkasan Tanggung Jawab |
|---|---|
| **Mahasiswa** | Meminjam ruangan & perangkat, mendaftar Kelas Lab, mengumpulkan tugas, mengunggah portofolio, melihat katalog sertifikasi |
| **Dosen** | Membuka & mengelola Kelas Lab miliknya, menetapkan materi & tenggat tugas per pertemuan, menyetujui pendaftaran peserta, mengunduh rekap kepatuhan tugas kelasnya |
| **Supervisor (Asisten Lab)** | Menyetujui/menolak peminjaman, mengelola perangkat & katalog sertifikasi, membuka Kelas Lab atas nama Dosen, mengunduh laporan & rekap semua kelas |
| **Admin (Kepala Lab)** | Seluruh kewenangan Supervisor, ditambah kelola akun pengguna, delegasi Asisten Lab, dan kelola konten informasi/profil lab |

> Akun dibuat otomatis lewat **Login Google institusi UNSIL** (`@unsil.ac.id` untuk Dosen, `@student.unsil.ac.id` untuk Mahasiswa). Akun Admin & Supervisor disiapkan oleh sistem. Login manual (email + password) tersedia sebagai alternatif setelah pengguna mengatur password.

---

## 3. Modul Utama Aplikasi

1. **Autentikasi & Profil** — Login Google UNSIL / manual, kelola profil pribadi (foto, data diri, password), 4 peran dengan hak akses berbeda.
2. **Informasi Lab** — Beranda, Profil Kepala Lab, Visi & Misi, Daftar & Detail Dosen, Roadmap Laboratorium.
3. **Peminjaman Ruangan** — Pengajuan oleh Mahasiswa dengan validasi bentrok jadwal otomatis, kalender ketersediaan, alur persetujuan.
4. **Kelas Lab / Praktikum** — Pembukaan kelas oleh Dosen (multi-sesi paralel), pendaftaran peserta dengan persetujuan, kuota, dan validasi jadwal.
5. **Peminjaman & Perpanjangan Perangkat** — Katalog inventaris, pengajuan pinjam, perpanjangan, dan pengembalian.
6. **Pengumpulan Tugas** — Mahasiswa mengirim tautan hasil tugas per pertemuan (1–16); Dosen menetapkan materi & tenggat; penanda tepat/terlambat otomatis.
7. **Rekap Tugas** — Ringkasan kepatuhan per kelas + matriks detail per pertemuan, dapat diunduh sebagai **PDF & Excel**.
8. **Katalog Sertifikasi** — Informasi sertifikasi/pelatihan eksternal (Mikrotik, Cisco, Oracle, dll) sebagai referensi mahasiswa.
9. **Portofolio Mahasiswa** — Wadah hasil riset/proyek/publikasi mahasiswa.
10. **Laporan** — Rekap aktivitas lab per rentang tanggal, dapat diunduh sebagai **PDF** (Admin/Supervisor).
11. **Notifikasi In-App** — Lonceng notifikasi otomatis untuk perubahan status pengajuan, tugas masuk, dan pengingat tenggat.

---

## 4. Nilai Utama Produk

- **Tanpa bentrok jadwal** — validasi otomatis dua-arah antara peminjaman ruangan dan jadwal Kelas Lab.
- **Alur persetujuan yang jelas** — setiap pengajuan (ruangan, perangkat, pendaftaran kelas) melewati peninjauan yang berwenang.
- **Kepatuhan tugas terpantau** — status pengumpulan per pertemuan terlihat rinci dan dapat direkap ke PDF/Excel.
- **Hak akses ketat** — setiap peran hanya mengakses fitur & data yang menjadi haknya, ditegakkan di level backend.
- **Notifikasi tepat waktu** — pengguna langsung tahu ketika status pengajuannya berubah atau ada tenggat yang terlewati.

---

## 5. Ringkasan Teknis

| Aspek | Teknologi |
|---|---|
| **Frontend** | Vue 3 (Composition API) + Vite — Single Page Application |
| **Backend** | Laravel 13 REST API (PHP 8.5) |
| **Basis Data** | MySQL |
| **Autentikasi** | Laravel Sanctum (token) + Google OAuth (Laravel Socialite) |
| **Laporan** | PDF (dompdf) & Excel (PhpSpreadsheet) |

Arsitektur memisahkan frontend dan backend sepenuhnya; keduanya berkomunikasi lewat API JSON dengan token Bearer. Rincian arsitektur, skema basis data, dan daftar endpoint tersedia di `3_SDD_FINAL.md`.
