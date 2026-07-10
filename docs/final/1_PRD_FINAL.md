# Product Requirement Document (PRD) — Final

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Unit Terkait**: Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) — Prodi Informatika

> Dokumen final ini memaparkan **visi produk, pengguna, dan alur penggunaan** dari aplikasi yang telah selesai dibangun. Aturan hak akses & validasi rinci ada di `2_SRS_FINAL.md`; skema data & API ada di `3_SDD_FINAL.md`.

---

## 1. Latar Belakang & Tujuan Produk

Administrasi di Laboratorium Riset KK JKF — peminjaman ruangan, peminjaman perangkat, penjadwalan kelas praktikum, dan pengumpulan tugas — memerlukan satu sumber data terpusat yang transparan dan terlacak, serta bebas dari bentrok jadwal. Informasi sertifikasi/pelatihan eksternal yang relevan bagi mahasiswa juga perlu tersedia dalam satu tempat yang mudah diakses.

**SIM Lab. Riset** adalah sistem informasi terpusat berbasis web yang:
- Mendigitalisasi seluruh proses administrasi lab menjadi alur kerja yang transparan dan terlacak
- Memberi setiap peran (Mahasiswa, Dosen, Supervisor, Admin) akses sesuai kebutuhan dan tanggung jawabnya
- Mengurangi bentrok jadwal peminjaman ruangan/perangkat lewat validasi sistem
- Menyediakan rekap data (peminjaman, pengumpulan tugas per pertemuan) yang bisa diunduh sebagai laporan (PDF/Excel)
- Menyediakan informasi sertifikasi/pelatihan eksternal dalam satu tempat terpusat sebagai referensi mahasiswa

---

## 2. User Persona & Hak Akses

Sistem memiliki 4 peran pengguna dengan hak akses berbeda.

### 2.1 Mahasiswa
- **Siapa**: Mahasiswa yang aktif melakukan riset/praktikum di lab
- **Bisa melakukan**: login, kelola profil pribadi, melihat jadwal ketersediaan lab, mengajukan peminjaman ruangan, mengajukan peminjaman perangkat (+ pengajuan perpanjangan), mendaftar sesi Kelas Lab/Praktikum, mengumpulkan tugas (mengirim tautan hasil tugas per pertemuan Kelas Lab yang diikutinya), melihat katalog sertifikasi eksternal, mengunggah portofolio riset
- **Hak akses data**: Create, Read, Update untuk data miliknya (tidak dapat menghapus data milik pengguna lain)

### 2.2 Dosen
- **Siapa**: Dosen pembimbing/peneliti yang terafiliasi dengan lab
- **Bisa melakukan**: login, kelola profil & portofolio/roadmap riset pribadi, melihat jadwal ketersediaan lab (read-only), membuka & mengelola Kelas Lab/Praktikum miliknya sendiri, menetapkan materi & deadline pengumpulan tugas per pertemuan, menyetujui/menolak pendaftaran peserta kelasnya, serta melihat & mengunduh Rekap Tugas (kepatuhan pengumpulan) untuk kelas yang diampunya. Dosen **tidak** mengajukan peminjaman ruangan (itu hak Mahasiswa)
- **Hak akses data**: CRUD untuk data yang menjadi tanggung jawabnya

### 2.3 Supervisor (Asisten Lab / Aslab)
- **Siapa**: Asisten laboratorium yang mengelola operasional harian lab. Ditetapkan oleh Admin dari akun Mahasiswa (lihat 3.11)
- **Bisa melakukan**: login, menyetujui/menolak pengajuan peminjaman ruangan & perangkat, mengelola jadwal peminjaman lab, membuka Kelas Lab/Praktikum atas permintaan Dosen + menyetujui/menolak pendaftaran, mengelola data perangkat lab, mengelola katalog informasi sertifikasi eksternal, mengunduh laporan (peminjaman, pengumpulan tugas, aktivitas lab) dalam rentang tanggal tertentu, serta melihat & mengunduh Rekap Tugas semua kelas
- **Hak akses data**: CRUD penuh untuk modul operasional lab (ruangan, perangkat, jadwal, katalog sertifikasi, laporan)

### 2.4 Admin (Kepala Lab)
- **Siapa**: Pemegang kendali penuh atas sistem
- **Bisa melakukan**: semua yang bisa dilakukan Supervisor, ditambah kelola data user (semua role), delegasi Asisten Lab (menetapkan/mengembalikan Mahasiswa ↔ Supervisor), kelola data master sistem, dan kelola konten informasi/profil publik lab (lihat 2.5)
- **Kelas Lab/Praktikum**: Admin memiliki **hak akses penuh** (buka/ubah/hapus semua kelas + approve/reject pendaftaran). Karena Admin bukan Dosen, saat membuka kelas ia **wajib menunjuk Dosen pengampu** — sehingga setiap sesi tetap terhubung ke Dosen yang bertanggung jawab (mekanisme sama seperti Supervisor)
- **Hak akses data**: CRUD penuh ke seluruh modul termasuk manajemen user dan role

### 2.5 Halaman Informasi Lab (Dapat Diakses Semua Role)
Selain modul transaksional, sistem menyediakan halaman informasi/profil lab yang dapat dilihat semua role:
- **Beranda**: informasi singkat mengenai Laboratorium Riset Jaringan, Forensika Digital, dan Internet of Things
- **Profil Kepala Laboratorium**
- **Visi & Misi Laboratorium**
- **Daftar Dosen**: daftar dosen di KK JKF beserta halaman detail profil masing-masing
- **Roadmap Laboratorium**: peta jalan riset KK JKF (terhubung dengan roadmap riset pribadi Dosen di 3.7)

Admin mengelola (tambah/ubah/hapus) konten daftar dosen — mendaftarkan akun dosen lewat manajemen user maupun memperbarui data profilnya; konten lain (visi-misi, roadmap, profil kepala lab) dikelola Admin sebagai bagian dari data master sistem.

---

## 3. Alur Pengguna (User Flow)

### 3.1 Autentikasi
1. Pengguna membuka halaman login → memilih **Login dengan Google** → masuk dengan akun Google institusi (`@unsil.ac.id` untuk Dosen, `@student.unsil.ac.id` untuk Mahasiswa) → akun dibuat otomatis saat pertama kali login, langsung diarahkan ke dashboard sesuai role
2. Setelah memiliki akun, pengguna dapat mengatur password di halaman Profil agar bisa **login manual** (email + password) sebagai alternatif
3. Jika memilih login manual sebelum pernah mengatur password, sistem mengarahkan pengguna untuk login lewat Google terlebih dahulu
4. Jika kredensial salah saat login manual, sistem menampilkan notifikasi error dan meminta input ulang

### 3.1a Kelola Profil Akun
Berlaku untuk **semua role** terhadap akunnya masing-masing, lewat halaman **Profil Saya**:
1. Melihat kartu identitas (foto, nama, email, peran; mahasiswa: NPM/angkatan/prodi, dosen: NIDN/bidang minat)
2. **Mengedit data diri**: nama & nomor telepon (semua role); Dosen tambahan NIDN, jabatan fungsional, tempat & tanggal lahir, serta Bidang Minat (pilih dari master, boleh lebih dari satu); Mahasiswa tambahan prodi. **Email dan peran tidak dapat diubah** sendiri; **NPM dan angkatan mahasiswa juga tidak dapat diubah**
3. **Mengganti foto profil** dengan mengunggah gambar
4. **Mengatur/mengubah password** untuk mengaktifkan login manual

### 3.2 Dashboard
Setelah login, setiap role melihat dashboard yang relevan: Mahasiswa (status peminjaman, jadwal & tugas belum dikumpulkan); Dosen (portofolio & pemberian tugas kelas yang diampu); Supervisor/Admin (ringkasan operasional lab).

### 3.3 Peminjaman Ruangan Lab
1. Mahasiswa membuka menu **Jadwal Lab** → melihat kalender ketersediaan ruangan
2. Memilih ruangan & mengisi form peminjaman (tanggal, waktu, keperluan)
3. Pengajuan masuk ke antrian persetujuan Supervisor/Admin
4. Supervisor/Admin meninjau → **Approve** atau **Reject**
5. Status peminjaman terupdate dan terlihat oleh mahasiswa pengaju
6. Mahasiswa menerima **notifikasi in-app** (lihat 3.10) atas hasil pengajuan

### 3.3a Kelas Lab/Praktikum

**Pembukaan Kelas (Dosen untuk dirinya; Admin/Supervisor untuk semua kelas dengan menunjuk Dosen pengampu)**:
1. Membuka menu **Kelas Lab/Praktikum**
2. Mengisi data: mata kuliah (dipilih dari daftar yang sudah ada), ruangan, hari & jam (pola berulang mingguan), tanggal mulai–selesai semester, kuota peserta (maks. 30–40), nama sesi (mis. "Kelas A")
3. Dapat menambahkan beberapa sesi paralel (Kelas A, B, C) dari mata kuliah yang sama, masing-masing dengan kuota independen
4. Sistem memvalidasi tidak ada bentrok jadwal ruangan sebelum menyimpan
5. Jadwal Kelas Lab tersimpan dan otomatis "mengisi" slot kalender ruangan untuk seluruh rentang semester

**Pendaftaran Mahasiswa**:
1. Mahasiswa membuka **Kelas Lab/Praktikum** → menu **Daftar Kelas** menampilkan mata kuliah beserta sesi paralel yang tersedia + sisa kuota tiap sesi
2. Memilih satu sesi dan mendaftar → pendaftaran berstatus **menunggu persetujuan**
3. Sistem memvalidasi kuota belum penuh, **satu sesi per mata kuliah**, dan tidak bentrok jadwal kelas lain yang sudah diambil
4. **Dosen pengampu (atau Supervisor) menyetujui/menolak** pendaftaran; mahasiswa resmi menjadi peserta setelah disetujui. Halaman "Kelas Lab Saya" menampilkan status tiap pendaftaran
5. Mahasiswa menerima **notifikasi in-app** (lihat 3.10) atas hasil pendaftaran

> **Catatan**: Slot yang sudah terisi jadwal Kelas Lab diperlakukan sama seperti peminjaman yang sudah disetujui — tidak tersedia saat mengajukan peminjaman ruangan biasa. Admin **tidak** memiliki kewenangan membuka Kelas Lab.

### 3.4 Peminjaman & Perpanjangan Perangkat
1. Mahasiswa membuka menu **Peminjaman Perangkat** → melihat daftar perangkat berstatus "Tersedia"
2. Memilih perangkat → mengajukan peminjaman
3. Jika butuh waktu lebih lama, mengajukan **Perpanjangan** sebelum batas waktu pinjam habis
4. Admin/Supervisor melakukan CRUD data perangkat (nomor seri & status: Tersedia/Dipinjam/Perbaikan) serta menyetujui/menolak pengajuan dan mencatat pengembalian
5. Mahasiswa menerima **notifikasi in-app** (lihat 3.10) saat pengajuan peminjaman atau perpanjangan disetujui/ditolak

### 3.5 Pengumpulan Tugas Kelas Lab

**Menetapkan Tugas (Dosen pengampu / Supervisor / Admin)**:
1. Reviewer membuka **Detail Kelas Lab** → memilih salah satu dari **16 pertemuan**
2. Mengisi **Nama Materi** dan/atau **Deadline** pertemuan tersebut. Materi boleh berdiri sendiri (silabus) tanpa deadline; sebuah pertemuan dianggap "ada tugas" bila **memiliki deadline**

**Mengumpulkan Tugas (Mahasiswa)**:
1. Mahasiswa membuka menu **Kelas Lab** → **Kirim Tugas** untuk sesi yang diikutinya (harus berstatus peserta `disetujui`)
2. Memilih **pertemuan** (1–16), mengisi judul, dan menempelkan **tautan** hasil tugas (mis. Google Drive/GitHub). Satu tugas per pertemuan per mahasiswa
3. Bila melewati deadline, tugas tetap dapat dikirim namun **ditandai "Terlambat"**; mahasiswa juga menerima **notifikasi pengingat** saat deadline terlewati (lihat 3.10)

**Merekap (Dosen/Supervisor/Admin)**:
1. Reviewer membuka menu **Rekap Tugas** → melihat ringkasan kepatuhan tiap kelas + matriks detail (peserta × pertemuan: tepat/telat/belum)
2. Dapat **mengunduh rekap sebagai PDF atau Excel (.xlsx)**. Dosen hanya melihat kelas yang diampunya; Supervisor/Admin melihat semua kelas

### 3.6 Katalog Informasi Sertifikasi & Pelatihan
> Modul ini bersifat **informasional**. Sistem hanya menyediakan informasi sertifikasi/pelatihan yang diselenggarakan pihak eksternal (mis. Mikrotik, Oracle, Cisco, RedHat). Pendaftaran dilakukan mahasiswa langsung ke penyelenggara, di luar sistem.

1. Supervisor/Admin menambahkan & memperbarui entri katalog (nama, penyelenggara, jadwal, persyaratan, tautan/cara pendaftaran eksternal)
2. Mahasiswa membuka menu **Sertifikasi** untuk melihat daftar sebagai referensi
3. Mahasiswa yang berminat mengikuti instruksi/tautan menuju penyelenggara eksternal

### 3.7 Portofolio & Profil Riset
1. Mahasiswa mengunggah hasil riset/proyek/publikasi ke halaman portofolio pribadinya
2. Dosen memperbarui data penelitian, pengabdian, dan roadmap riset KK JKF agar dapat dilihat mahasiswa yang mencari topik tugas akhir/skripsi

### 3.8 Halaman Informasi Lab
1. Pengguna (role apa pun) yang sudah login dapat membuka menu **Beranda**, **Kepala Laboratorium**, **Visi & Misi**, **Daftar Dosen**, atau **Roadmap Laboratorium**
2. Pada **Daftar Dosen**, pengguna dapat memilih satu dosen untuk melihat halaman detail profilnya
3. Admin membuka panel kelola user/dosen untuk menambah, mengubah, atau menghapus entri dosen
4. Admin memperbarui konten Visi-Misi, Roadmap Lab, dan Profil Kepala Lab melalui panel data master

### 3.9 Laporan (Admin & Supervisor)
1. Admin/Supervisor membuka menu **Laporan** → memilih rentang tanggal
2. Sistem menampilkan rekap peminjaman ruangan, peminjaman perangkat, dan pengumpulan tugas pada rentang tersebut
3. Admin/Supervisor dapat mengunduh laporan dalam format PDF

> Rekap kepatuhan tugas yang lebih rinci (per pertemuan, per peserta) tersedia terpisah di menu **Rekap Tugas** — dapat diunduh sebagai PDF atau Excel, dan juga dapat diakses Dosen untuk kelas yang diampunya (lihat 3.5).

### 3.10 Notifikasi In-App
1. Setiap kali ada perubahan status yang relevan (pengajuan disetujui/ditolak, pengajuan baru masuk), sistem otomatis menyimpan notifikasi untuk pengguna terkait
2. Pengguna melihat ikon **lonceng** di navbar; muncul **angka merah** (badge) jika ada notifikasi belum dibaca
3. Mengklik lonceng → muncul daftar pesan, mis. *"Pengajuan peminjaman ruangan kamu pada 20 Juni 2026 telah disetujui"*
4. Notifikasi **tidak hilang otomatis** — tetap tersimpan sampai pengguna menandai dibaca atau menghapusnya
5. Pengguna dapat menandai satu/semua notifikasi sebagai dibaca, atau menghapus satu per satu

**Pemicu notifikasi**:
- Mahasiswa: pengajuan peminjaman ruangan/perangkat disetujui/ditolak
- Mahasiswa: pengajuan perpanjangan perangkat disetujui/ditolak; berhasil mendaftar ke sesi Kelas Lab
- Mahasiswa: **pengingat tenggat tugas terlewati** (deadline pertemuan lewat dan tugas belum dikumpulkan)
- Mahasiswa: **pengingat pengembalian perangkat** yang mendekati/melewati tanggal kembali rencana
- Dosen pengampu & Supervisor: **ada tugas baru masuk** pada kelas terkait
- Supervisor/Admin: ada pengajuan peminjaman baru (ruangan/perangkat/perpanjangan) yang menunggu persetujuan

> Notifikasi pengingat (tenggat tugas & pengembalian perangkat) dihasilkan lewat **penjadwalan berkala** dan bersifat idempoten (tidak menduplikasi pengingat yang sama).

### 3.11 Delegasi Asisten Lab (Aslab)
1. Admin membuka menu **Kelola User / Aslab** → melihat daftar kandidat (Mahasiswa) dan Aslab aktif (Supervisor)
2. Admin **menetapkan** seorang Mahasiswa menjadi Supervisor (Aslab); profil mahasiswanya dipertahankan agar sewaktu-waktu dapat **dikembalikan** ke peran Mahasiswa
3. Hanya transisi Mahasiswa ↔ Supervisor yang diperbolehkan lewat menu ini

---

## 4. Kriteria Keberhasilan Produk (MVP)

Produk dianggap berhasil jika:
1. Pengguna (semua role) dapat registrasi dan login dengan aman
2. Proses peminjaman ruangan & perangkat berjalan utuh dari pengajuan (mahasiswa) hingga persetujuan (supervisor/admin) **tanpa bentrok jadwal**
3. Pengumpulan tugas per pertemuan tercatat akurat, dengan penanda tepat/terlambat mengacu deadline (waktu lokal WIB)
4. Admin/Supervisor dapat menghasilkan laporan rekap yang dapat diunduh (PDF), dan Rekap Tugas dapat diunduh (PDF/Excel) oleh Admin/Supervisor/Dosen
5. Setiap role hanya dapat mengakses fitur dan data sesuai hak aksesnya (tidak ada kebocoran akses lintas role)
6. Notifikasi in-app terkirim secara akurat dan tepat waktu setiap kali ada perubahan status yang relevan

---

## 5. Lingkup di Luar Dokumen Ini
- Aturan validasi bisnis detail & matriks hak akses per endpoint → lihat `2_SRS_FINAL.md`
- Skema database, ERD, dan struktur API → lihat `3_SDD_FINAL.md`
- Daftar fitur/modul final per domain & per role → lihat `4_RINGKASAN_FITUR.md`
