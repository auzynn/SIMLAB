# 1. Product Requirement Document (PRD)

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Unit Terkait**: Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) — Prodi Informatika
**Versi Dokumen**: 1.0

> Dokumen ini adalah **sumber kebenaran utama** untuk visi produk, siapa penggunanya, dan bagaimana alur penggunaannya. Semua AI Agent (Hermes, Roo Code, Kilo Code, Copilot, dll) membaca dokumen ini lebih dulu sebelum mengerjakan task apa pun — lihat `.clinerules/agent.md`.

---

## 1. Latar Belakang & Tujuan Produk

Selama ini proses administrasi di Laboratorium Riset KK JKF — peminjaman ruangan, peminjaman perangkat, dan presensi — masih dilakukan secara manual. Hal ini menyebabkan proses sulit dilacak, rawan bentrok jadwal, dan tidak ada satu sumber data terpusat yang bisa diakses semua pihak terkait. Selain itu, informasi sertifikasi/pelatihan eksternal yang relevan bagi mahasiswa juga belum tersedia dalam satu tempat yang mudah diakses.

**Tujuan produk ini** adalah membangun sistem informasi terpusat berbasis web yang:
- Mendigitalisasi seluruh proses administrasi lab menjadi alur kerja yang transparan dan terlacak
- Memberi setiap peran (mahasiswa, dosen, supervisor, admin) akses sesuai kebutuhan dan tanggung jawabnya
- Mengurangi bentrok jadwal peminjaman ruangan/perangkat lewat validasi sistem
- Menyediakan rekap data (presensi, peminjaman) yang bisa diunduh sebagai laporan
- Menyediakan informasi sertifikasi/pelatihan eksternal dalam satu tempat terpusat sebagai referensi mahasiswa

---

## 2. User Persona & Hak Akses

Sistem memiliki 4 peran pengguna dengan hak akses berbeda:

### 2.1 Mahasiswa (User)
- **Siapa**: Mahasiswa yang aktif melakukan riset/praktikum di lab
- **Bisa melakukan**: registrasi akun, login, kelola profil pribadi, presensi (check-in/check-out), melihat jadwal ketersediaan lab, mengajukan peminjaman ruangan, mengajukan peminjaman perangkat (+ pengajuan perpanjangan), **mendaftar sesi Kelas Lab/Praktikum**, melihat informasi katalog sertifikasi eksternal, mengunggah portofolio riset
- **Hak akses data**: Create, Read, Update (tidak bisa Delete data milik pengguna lain)

### 2.2 Dosen
- **Siapa**: Dosen pembimbing/peneliti yang terafiliasi dengan lab
- **Bisa melakukan**: login, kelola profil & portofolio/roadmap riset pribadi, kelola presensi (untuk mahasiswa bimbingannya), melihat & mengelola jadwal peminjaman lab
- **Hak akses data**: Create, Read, Update, Delete (CRUD) untuk data yang menjadi tanggung jawabnya

### 2.3 Supervisor (Asisten Lab / Aslab)
- **Siapa**: Asisten laboratorium yang mengelola operasional harian lab
- **Bisa melakukan**: login, menyetujui/menolak pengajuan peminjaman ruangan & perangkat, mengelola jadwal peminjaman lab, **membuka Kelas Lab/Praktikum atas permintaan Dosen**, mengelola data perangkat lab, mengelola katalog informasi sertifikasi eksternal, mengunduh laporan (presensi, peminjaman, aktivitas lab) dalam rentang tanggal tertentu
- **Hak akses data**: CRUD penuh untuk modul operasional lab (ruangan, perangkat, jadwal, katalog sertifikasi, report)

### 2.4 Admin (Kepala Lab)
- **Siapa**: Pemegang kendali penuh atas sistem
- **Bisa melakukan**: semua yang bisa dilakukan Supervisor, ditambah kelola data user (semua role), kelola data master sistem secara keseluruhan, kelola konten informasi/profil publik lab (lihat 2.5)
- **Pengecualian**: Admin **tidak dapat** membuka atau mengelola Kelas Lab/Praktikum. Kewenangan ini hanya dimiliki Dosen (untuk kelasnya sendiri) dan Supervisor (atas permintaan Dosen) — agar setiap sesi kelas selalu terhubung ke Dosen pengampu yang bertanggung jawab.
- **Hak akses data**: CRUD penuh ke seluruh modul termasuk manajemen user dan role, kecuali Kelas Lab/Praktikum (lihat pengecualian di atas)

### 2.5 Halaman Informasi Lab (Dapat Diakses Semua Role)
Selain modul transaksional (peminjaman, presensi, sertifikasi), sistem juga menyediakan **halaman informasi/profil lab** yang dapat dilihat oleh semua role setelah login:
- **Beranda**: informasi singkat mengenai Laboratorium Riset Jaringan, Forensika Digital, dan Internet of Things
- **Profil Kepala Laboratorium**: informasi mengenai Kepala Lab
- **Visi & Misi Laboratorium**
- **Daftar Dosen**: daftar dosen di KK JKF beserta halaman detail profil masing-masing
- **Roadmap Laboratorium**: peta jalan riset KK JKF (terhubung dengan roadmap riset pribadi Dosen di 3.7)

Admin bertanggung jawab mengelola (tambah/ubah/hapus) konten daftar dosen, baik dengan mendaftarkan akun dosen secara manual terlebih dahulu melalui manajemen user maupun memperbarui data profilnya; konten lain (visi-misi, roadmap, profil kepala lab) dikelola Admin sebagai bagian dari data master sistem.

> Detail aturan bisnis, validasi, dan batasan akses per endpoint akan dirinci di `2_SRS.md`.

---

## 3. Visi Alur Pengguna (User Flow)

Berikut alur utama yang harus didukung sistem, dikelompokkan per kebutuhan inti. Ini adalah gambaran tingkat tinggi — detail skenario teknis (kondisi error, validasi) ada di `2_SRS.md`.

### 3.1 Autentikasi
1. Pengguna baru membuka halaman login → memilih **Login dengan Google** → masuk dengan akun Google institusi (`@unsil.ac.id` untuk Dosen, `@student.unsil.ac.id` untuk Mahasiswa) → akun otomatis dibuat saat pertama kali login, langsung diarahkan ke dashboard sesuai role
2. Setelah memiliki akun, pengguna dapat mengatur password di halaman Profil agar bisa **login manual** (email + password) sebagai alternatif selain Google, di kesempatan berikutnya
3. Jika memilih login manual sebelum pernah mengatur password, sistem menolak dan mengarahkan pengguna untuk login lewat Google terlebih dahulu
4. Jika kredensial salah saat login manual, sistem menampilkan notifikasi error dan meminta input ulang

### 3.1a Kelola Profil Akun
Berlaku untuk **semua role** terhadap akunnya masing-masing, lewat halaman **Profil Saya**:
1. Pengguna membuka **Profil Saya** → melihat kartu identitas (foto, nama, email, peran; mahasiswa: NPM/angkatan/prodi, dosen: NIDN/bidang riset)
2. Pengguna dapat **mengedit data diri**: nama dan nomor telepon (semua role); Dosen tambahan NIDN; Mahasiswa tambahan prodi. **Email dan peran tidak dapat diubah** sendiri (peran hanya diubah Admin lewat Kelola User); **NPM dan angkatan mahasiswa juga tidak dapat diubah** (diturunkan otomatis saat registrasi)
3. Pengguna dapat **mengganti foto profil** dengan mengunggah gambar (menggantikan foto bawaan dari Google)
4. Pengguna dapat **mengatur/mengubah password** untuk mengaktifkan login manual (lihat 3.1)

### 3.2 Dashboard
- Setelah login, setiap role melihat dashboard yang relevan dengan tanggung jawabnya (mahasiswa: status peminjaman & jadwal; dosen: portofolio & presensi mahasiswa bimbingan; supervisor/admin: ringkasan operasional lab)

### 3.3 Peminjaman Ruangan Lab
1. Mahasiswa membuka menu **Jadwal Lab** → melihat kalender ketersediaan ruangan
2. Mahasiswa memilih ruangan & mengisi form peminjaman (tanggal, waktu, keperluan)
3. Pengajuan masuk ke antrian persetujuan Supervisor/Admin
4. Supervisor/Admin meninjau pengajuan → **Approve** atau **Reject**
5. Status peminjaman terupdate dan terlihat oleh mahasiswa pengaju
6. Mahasiswa menerima **notifikasi in-app** (lihat 3.10) bahwa pengajuannya disetujui atau ditolak

### 3.3a Kelas Lab/Praktikum

**Pembukaan Kelas (Dosen, atau Supervisor atas permintaan Dosen)**:
1. Dosen (atau Supervisor atas permintaan Dosen) membuka menu **Kelas Lab/Praktikum**
2. Mengisi data: mata kuliah (dipilih dari daftar yang sudah ada), ruangan, hari & jam (pola berulang mingguan), tanggal mulai–selesai semester, kuota peserta (maks. 30–40), nama sesi (mis. "Kelas A")
3. Dosen dapat menambahkan beberapa sesi paralel (Kelas A, B, C) dari mata kuliah yang sama, masing-masing dengan kuota independen
4. Sistem memvalidasi tidak ada bentrok jadwal ruangan sebelum menyimpan
5. Jadwal Kelas Lab tersimpan dan secara otomatis "mengisi" slot kalender ruangan untuk seluruh rentang semester

**Pendaftaran Mahasiswa**:
1. Mahasiswa membuka menu **Kelas Lab/Praktikum** → melihat daftar mata kuliah beserta sesi-sesi paralel yang tersedia, termasuk sisa kuota tiap sesi
2. Mahasiswa memilih satu sesi dan mendaftar sebagai peserta
3. Sistem memvalidasi kuota belum penuh sebelum menerima pendaftaran; jika penuh, mahasiswa dapat memilih sesi paralel lain
4. Mahasiswa menerima **notifikasi in-app** (lihat 3.10) konfirmasi pendaftaran berhasil

> **Catatan penting**: Slot yang sudah terisi jadwal Kelas Lab diperlakukan sama seperti peminjaman yang sudah disetujui — tidak tersedia untuk dipilih saat mengajukan peminjaman ruangan biasa (lihat 3.3). Admin **tidak** memiliki kewenangan membuka Kelas Lab (lihat 2.4).

### 3.4 Peminjaman & Perpanjangan Perangkat
1. Mahasiswa membuka menu **Peminjaman Perangkat** → melihat daftar perangkat berstatus "Tersedia"
2. Mahasiswa memilih perangkat yang dibutuhkan → mengajukan peminjaman
3. Jika butuh waktu lebih lama, mahasiswa mengajukan **Perpanjangan** sebelum batas waktu pinjam habis
4. Admin/Supervisor melakukan CRUD data perangkat (termasuk nomor seri dan status: Tersedia/Dipinjam/Perbaikan)
5. Mahasiswa menerima **notifikasi in-app** (lihat 3.10) saat pengajuan peminjaman atau perpanjangan disetujui/ditolak

### 3.5 Presensi Laboratorium
1. Mahasiswa melakukan **Check-in** saat masuk lab, memilih keperluan riset yang dikerjakan
2. Mahasiswa melakukan **Check-out** saat selesai
3. Admin/Dosen dapat merekap data kehadiran untuk laporan utilisasi lab bulanan

### 3.6 Katalog Informasi Sertifikasi & Pelatihan
> **Catatan**: Modul ini bersifat informasional. SIM Lab. Riset tidak menangani proses pendaftaran sertifikasi secara langsung — sistem hanya menyediakan informasi sertifikasi/pelatihan yang diselenggarakan pihak eksternal (mis. Mikrotik, Oracle, Cisco, RedHat). Pendaftaran sesungguhnya dilakukan mahasiswa di luar sistem, langsung ke penyelenggara.

1. Supervisor/Admin menambahkan & memperbarui entri katalog sertifikasi (nama, penyelenggara, jadwal, persyaratan, tautan/cara pendaftaran ke pihak eksternal)
2. Mahasiswa membuka menu **Sertifikasi** untuk melihat daftar sertifikasi yang tersedia sebagai referensi
3. Mahasiswa yang berminat mengikuti instruksi/tautan menuju penyelenggara eksternal untuk mendaftar

### 3.7 Portofolio & Profil Riset
1. Mahasiswa mengunggah hasil riset/proyek/publikasi ke halaman portofolio pribadinya
2. Dosen memperbarui data penelitian, pengabdian, dan roadmap riset KK JKF agar dapat dilihat mahasiswa yang mencari topik tugas akhir/skripsi

### 3.8 Halaman Informasi Lab
1. Pengguna (role apa pun) yang sudah login dapat membuka menu **Beranda**, **Kepala Laboratorium**, **Visi & Misi**, **Daftar Dosen**, atau **Roadmap Laboratorium** dari navigasi utama
2. Pada menu **Daftar Dosen**, pengguna dapat memilih satu dosen untuk melihat halaman detail profilnya
3. Admin membuka panel kelola user/dosen untuk menambah (pendaftaran manual), mengubah, atau menghapus entri dosen yang ditampilkan di Daftar Dosen
4. Admin memperbarui konten Visi-Misi, Roadmap Lab, dan Profil Kepala Lab melalui panel data master

### 3.9 Laporan (Khusus Supervisor)
1. Supervisor membuka menu **Report** → memilih rentang tanggal
2. Sistem menampilkan rekap peminjaman, presensi, dan aktivitas lab pada rentang tersebut
3. Supervisor dapat mengunduh laporan dalam format PDF

### 3.10 Notifikasi In-App
1. Setiap kali ada perubahan status yang relevan (pengajuan disetujui/ditolak, pengajuan baru masuk), sistem secara otomatis menyimpan notifikasi ke database untuk pengguna yang bersangkutan
2. Pengguna melihat ikon **lonceng** di navbar; muncul **angka merah** (badge) jika ada notifikasi yang belum dibaca
3. Pengguna mengklik ikon lonceng → muncul daftar pesan notifikasi, mis. *"Pengajuan peminjaman ruangan kamu pada 20 Juni 2026 telah disetujui"*
4. Notifikasi **tidak hilang otomatis** — tetap tersimpan sampai pengguna menandai sudah dibaca atau menghapusnya
5. Pengguna dapat menandai satu notifikasi sebagai sudah dibaca, atau menandai semua sekaligus, atau menghapus satu per satu

**Pemicu notifikasi**:
- Mahasiswa/Dosen: pengajuan peminjaman ruangan atau perangkat disetujui/ditolak
- Mahasiswa: pengajuan perpanjangan perangkat disetujui/ditolak; berhasil mendaftar ke sesi Kelas Lab
- Supervisor/Admin: ada pengajuan peminjaman baru (ruangan/perangkat/perpanjangan) yang menunggu persetujuan

---

## 4. Kriteria Keberhasilan Produk (Definisi MVP Berhasil)

Produk dianggap berhasil mencapai MVP jika:
1. Pengguna (semua role) dapat melakukan registrasi dan login dengan aman
2. Proses peminjaman ruangan & perangkat berjalan utuh dari pengajuan (mahasiswa) hingga persetujuan (supervisor/admin) **tanpa bentrok jadwal**
3. Data presensi tercatat akurat sesuai waktu lokal (WIB)
4. Supervisor dapat menghasilkan laporan rekap yang dapat diunduh
5. Setiap role hanya dapat mengakses fitur dan data sesuai hak aksesnya (tidak ada kebocoran akses lintas role)
6. Notifikasi in-app terkirim secara akurat dan tepat waktu setiap kali ada perubahan status pengajuan yang relevan bagi pengguna terkait

---

## 5. Lingkup di Luar Dokumen Ini

Dokumen ini sengaja **tidak** membahas:
- Aturan validasi bisnis detail & matriks hak akses per endpoint → lihat `2_SRS.md`
- Skema database, ERD, dan struktur API → lihat `3_SDD.md`
- Breakdown task implementasi → lihat `4_TASK_BREAKDOWN.md`
