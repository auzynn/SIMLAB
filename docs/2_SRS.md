# 2. Software Requirements Specification (SRS)

**Nama Produk**: Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset)
**Unit Terkait**: Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) — Prodi Informatika
**Versi Dokumen**: 1.2
**Dokumen Acuan**: `1_PRD.md`

> **Perubahan v1.2 (per 2026-07-10)**: penyesuaian wewenang RBAC — (1) **Kelas Lab/Praktikum**: Admin diberi hak akses penuh (CRUD semua kelas + approve/reject pendaftaran, menunjuk dosen pengampu saat membuka); (2) **Sertifikasi**: Dosen boleh Create + Update/Delete entri miliknya sendiri (`created_by`); (3) **Halaman Informasi Lab**: Supervisor diberi CRUD (kelola konten). Backend diselaraskan (KelasLabPolicy, SertifikasiPolicy, Gate `manage-info-lab`).

> **Perubahan v1.1 (per 2026-07-09)**: modul **Presensi digantikan Pengumpulan Tugas** (UC-04 ditulis ulang). Matriks RBAC baris "Presensi" → "Pengumpulan Tugas"; ditambah baris "Deadline & Materi Pertemuan". UC-06a (Rekap Tugas) & UC-07 (pemicu pengingat) diselaraskan dengan implementasi. Delegasi Aslab didokumentasikan.

> Dokumen ini adalah **sumber kebenaran** untuk aturan validasi bisnis dan hak akses (RBAC). Semua AI Agent membaca dokumen ini untuk memastikan setiap endpoint dan fitur yang diimplementasikan mematuhi aturan otorisasi dan validasi yang didefinisikan di sini — lihat `.clinerules/agent.md`.

---

## 1. Matriks Hak Akses (RBAC)

Tabel berikut adalah rujukan **wajib** sebelum mengimplementasikan middleware/Policy/Gate di backend. `C` = Create, `R` = Read, `U` = Update, `D` = Delete. Tanda `–` berarti tidak punya akses ke modul tersebut.

| Modul | Admin | Supervisor | Dosen | Mahasiswa |
|---|---|---|---|---|
| Data User & Role | CRUD | – | – | – |
| Delegasi Asisten Lab (Mahasiswa ↔ Supervisor) | C, R, D | – | – | – |
| Data Master (ruangan, perangkat, mata kuliah) | CRUD | CRUD | – | – |
| Jadwal Peminjaman Lab | CRUD | CRUD | R | R |
| Kelas Lab/Praktikum (jadwal semester) | CRUD (semua kelas) + approve/reject pendaftaran (menunjuk dosen pengampu saat membuka) | C (atas permintaan Dosen), R, U, D + approve/reject pendaftaran | CRUD (milik sendiri) + approve/reject pendaftaran kelasnya | R, daftar sebagai peserta (butuh persetujuan) |
| Pengajuan Peminjaman Ruangan | R (approve/reject) | R (approve/reject) | – | C, R (milik sendiri) |
| Pengajuan Peminjaman Perangkat & Perpanjangan | R (approve/reject) | R (approve/reject) | – | C, R, U (milik sendiri, sebelum disetujui) |
| Pengumpulan Tugas (menggantikan Presensi) | R (semua), D | R (semua), D | R (kelas milik sendiri), D | C, R, D (milik sendiri) |
| Deadline & Materi Pertemuan Kelas Lab | CRU (semua kelas) | CRU (semua kelas) | CRU (kelas milik sendiri) | R |
| Sertifikasi (katalog informasi) | CRUD | CRUD | C, R + U, D (milik sendiri) | R |
| Portofolio Mahasiswa | R | R | R | CRUD (milik sendiri) |
| Profil & Roadmap Dosen | R | R | CRUD (milik sendiri) | R |
| Profil Mahasiswa (data diri, kecuali NPM) | R | R | R (mahasiswa bimbingan) | U (milik sendiri) |
| Laporan/Report (unduh) | C, R | C, R | – | – |
| Rekap Tugas Kelas Lab (unduh PDF/Excel) | R (semua kelas) | R (semua kelas) | R (kelas milik sendiri) | – |
| Halaman Informasi Lab (Beranda, Visi-Misi, Daftar Dosen, Roadmap Lab, Profil Kepala Lab) | CRUD (kelola konten) | CRUD (kelola konten) | R | R |
| Edit Profil Akun Pribadi | U (milik sendiri) | U (milik sendiri) | U (milik sendiri) | U (milik sendiri) |
| Notifikasi In-App | U (tandai baca), D (milik sendiri) | U (tandai baca), D (milik sendiri) | U (tandai baca), D (milik sendiri) | U (tandai baca), D (milik sendiri) |

**Aturan implementasi**:
- Setiap endpoint API **wajib** diproteksi middleware `auth:sanctum` kecuali endpoint publik yang eksplisit didefinisikan di Bagian 4
- Pengecekan role **wajib** dilakukan lewat Laravel Policy/Gate, bukan pengecekan manual `if ($user->role === ...)` yang tersebar di Controller
- "Milik sendiri" berarti sistem **wajib** memvalidasi kolom pemilik data (`user_id`/`dosen_id`/`mahasiswa_id`, atau `created_by` pada Sertifikasi) cocok dengan user yang sedang login, sebelum mengizinkan operasi Update/Delete. Untuk Kelas Lab, Admin & Supervisor mengelola semua kelas (menunjuk `dosen_id` pengampu saat membuka), sedangkan Dosen dibatasi kelas miliknya.
- Hubungan "mahasiswa bimbingan" (untuk validasi akses Dosen ke profil mahasiswa bimbingan) didefinisikan melalui foreign key `dosen_pembimbing_id` pada entri mahasiswa yang merujuk ke id dosen. (Akses Dosen ke tugas kelas mengikuti kepemilikan kelas `dosen_id`, bukan relasi bimbingan.)
- Pada **Edit Profil Akun Pribadi** (`PATCH /api/auth/profile`), field `email` dan `role` **wajib immutable** (tidak dapat diubah pemilik akun — `role` hanya lewat Kelola User oleh Admin); untuk Mahasiswa, `npm` dan `angkatan` juga immutable. Validasi penolakan field ini dilakukan di backend (Form Request/Controller), bukan hanya disembunyikan di frontend.

---

## 2. Kebutuhan Fungsional per Role

### 2.1 Admin
| ID | Fungsi | Keterangan |
|---|---|---|
| F-AD-01 | Login via Google OAuth UNSIL | Jalur utama, sekaligus pembuatan akun pertama kali |
| F-AD-02 | Login manual (email/password) | Alternatif, hanya aktif setelah password diatur di Profil (lihat UC-01b) |
| F-AD-03 | Kelola data user | CRUD data user lintas role |
| F-AD-03a | Delegasi Asisten Lab (Aslab) | Menetapkan Mahasiswa menjadi Supervisor & mengembalikannya (hanya transisi Mahasiswa↔Supervisor) |
| F-AD-04 | Kelola data dosen | Tambah, ubah, hapus, lihat detail profil dosen |
| F-AD-05 | Kelola konten informasi lab | Update Beranda, Visi-Misi, Profil Kepala Lab |
| F-AD-06 | Kelola roadmap lab (tingkat KK) | Tambah/ubah konten roadmap riset KK JKF |
| F-AD-07 | Kelola jadwal peminjaman lab | Tambah, ubah, hapus jadwal & ruangan |
| F-AD-08 | Approve/reject peminjaman ruangan & perangkat | Sama seperti Supervisor |
| F-AD-09 | Kelola data perangkat & sertifikasi | CRUD penuh |
| F-AD-10 | Terima & kelola notifikasi in-app | Tandai baca (satu/semua), hapus; notifikasi masuk otomatis saat ada pengajuan baru yang menunggu persetujuan |
| F-AD-11 | Edit profil akun pribadi | Update nama & no. telepon, unggah foto profil, atur/ubah password (milik sendiri) |
| F-AD-12 | Unduh laporan & Rekap Tugas | Report (PDF, per rentang tanggal) + Rekap Tugas semua kelas (PDF/Excel) — lihat UC-06, UC-06a |
| F-AD-13 | Kelola Kelas Lab/Praktikum (semua kelas) | Hak akses penuh: buka/ubah/hapus kelas dengan menunjuk dosen pengampu, serta approve/reject pendaftaran peserta semua kelas (lihat UC-02a) |

### 2.2 Supervisor (Asisten Lab)
| ID | Fungsi | Keterangan |
|---|---|---|
| F-SV-01 | Login via Google OAuth UNSIL + login manual (setelah set password) | Sama seperti Admin |
| F-SV-02 | Lihat daftar dosen & profil lab | Read-only |
| F-SV-03 | Kelola jadwal peminjaman lab | Tambah, ubah, hapus jadwal & ruangan |
| F-SV-04 | Approve/reject pengajuan peminjaman ruangan | Wajib validasi tidak ada bentrok jadwal sebelum approve |
| F-SV-05 | Approve/reject pengajuan peminjaman & perpanjangan perangkat | — |
| F-SV-06 | Kelola data perangkat | CRUD nomor seri, status (Tersedia/Dipinjam/Perbaikan) |
| F-SV-07 | Kelola katalog informasi sertifikasi | Tambah/ubah/hapus info sertifikasi eksternal (penyelenggara, jadwal, syarat, link/kontak pendaftaran) yang ditampilkan ke mahasiswa |
| F-SV-08 | Unduh laporan (report) & Rekap Tugas | Report (filter rentang tanggal, ekspor PDF) + Rekap Tugas semua kelas (PDF/Excel) — lihat UC-06, UC-06a |
| F-SV-09 | Membuka & mengelola Kelas Lab/Praktikum (semua kelas) | Membuat/ubah/hapus jadwal kelas atas nama Dosen terkait serta approve/reject pendaftaran; Admin kini juga memiliki kewenangan penuh ini (lihat F-AD-13) |
| F-SV-10 | Terima & kelola notifikasi in-app | Tandai baca (satu/semua), hapus; notifikasi masuk otomatis saat ada pengajuan baru yang menunggu persetujuan |
| F-SV-11 | Edit profil akun pribadi | Update nama & no. telepon, unggah foto profil, atur/ubah password (milik sendiri) |
| F-SV-12 | Kelola konten Halaman Informasi Lab | Update Beranda, Visi-Misi, Profil Kepala Lab, Roadmap KK (didelegasikan; setara Admin — Gate `manage-info-lab`) |

### 2.3 Dosen
| ID | Fungsi | Keterangan |
|---|---|---|
| F-DS-01 | Login via Google OAuth UNSIL + login manual (setelah set password) | Sama seperti Admin |
| F-DS-02 | Edit profil pribadi | Update data diri (nama, no. telepon, NIDN, jabatan fungsional, tempat & tanggal lahir, Bidang Minat), unggah foto profil; email & peran tidak dapat diubah sendiri |
| F-DS-03 | Kelola portofolio & roadmap riset pribadi | CRUD konten riset pribadi |
| F-DS-04 | Kelola tugas kelas & rekap kepatuhan | Menetapkan materi & deadline per pertemuan (kelas yang diampu), melihat tugas masuk, mengunduh Rekap Tugas (PDF/Excel) kelasnya (lihat UC-04, UC-06a) |
| F-DS-05 | Lihat jadwal ketersediaan lab | Read-only (kalender ketersediaan). Dosen **tidak** mengajukan peminjaman ruangan — itu hak Mahasiswa (lihat UC-02) |
| F-DS-06 | Membuka & mengelola Kelas Lab/Praktikum | Tentukan mata kuliah (dari daftar yang sudah ada), ruangan, jadwal berulang (mingguan) selama satu semester, kuota peserta (30-40), bisa multi-sesi paralel; setujui/tolak pendaftaran peserta (lihat UC-02a) |
| F-DS-07 | Lihat daftar mata kuliah | Read-only, untuk dipilih saat membuka Kelas Lab |
| F-DS-08 | Terima & kelola notifikasi in-app | Tandai baca (satu/semua), hapus; menerima notifikasi saat ada tugas baru masuk pada kelasnya |
| F-DS-09 | Tambah referensi katalog Sertifikasi | Menambah entri sertifikasi eksternal sebagai referensi; hanya dapat mengubah/menghapus entri yang dibuatnya sendiri (`created_by`) |

### 2.4 Mahasiswa
| ID | Fungsi | Keterangan |
|---|---|---|
| F-MH-01 | Registrasi akun (otomatis) | Akun **tercipta otomatis** saat login Google pertama kali dengan email `@student.unsil.ac.id` — tidak ada form registrasi terpisah |
| F-MH-02 | Login via Google OAuth UNSIL + login manual (setelah set password) | Sama seperti role lain |
| F-MH-03 | Edit profil pribadi | Update data diri (nama, no. telepon, prodi), unggah foto profil; email, peran, NPM & angkatan tidak dapat diubah |
| F-MH-04 | Lihat jadwal ketersediaan lab | Read-only, tampilan kalender |
| F-MH-05 | Ajukan peminjaman ruangan lab | Isi form (tanggal, waktu, keperluan); slot yang sudah dipakai Kelas Lab/Praktikum tidak tersedia |
| F-MH-05a | Mendaftar sesi Kelas Lab/Praktikum | Pilih sesi (mis. Kelas A/B/C) selama kuota belum penuh |
| F-MH-06 | Ajukan peminjaman perangkat | Pilih dari daftar perangkat status "Tersedia" |
| F-MH-07 | Ajukan perpanjangan peminjaman perangkat | Sebelum batas waktu pinjam habis |
| F-MH-08 | Kumpulkan tugas Kelas Lab | Kirim tautan hasil tugas per pertemuan (1–16) untuk kelas yang diikuti (status peserta `disetujui`); satu tugas per pertemuan; ditandai "Terlambat" bila lewat deadline (lihat UC-04) |
| F-MH-09 | Lihat katalog informasi sertifikasi | Melihat daftar sertifikasi eksternal yang tersedia (penyelenggara, jadwal, syarat, cara mendaftar) — SIM Lab. Riset hanya menampilkan info, pendaftaran dilakukan langsung ke pihak penyelenggara |
| F-MH-10 | Kelola portofolio pribadi | CRUD hasil riset/proyek/publikasi milik sendiri |
| F-MH-11 | Terima & kelola notifikasi in-app | Tandai baca (satu/semua), hapus; menerima notifikasi status pengajuan dan konfirmasi pendaftaran Kelas Lab |

---

## 3. Spesifikasi Use Case Kritis

Bagian ini merinci skenario normal dan alternatif untuk use case yang punya potensi ambiguitas tinggi (validasi, bentrok data, dsb). AI Agent **wajib** mengimplementasikan kedua skenario, bukan hanya jalur normal/"happy path".

### UC-01: Login
**Aktor**: Admin, Dosen, Supervisor, Mahasiswa

> **Catatan**: Akun **hanya bisa dibuat** lewat Login Google UNSIL (domain `@unsil.ac.id` untuk Dosen, `@student.unsil.ac.id` untuk Mahasiswa; Admin/Supervisor dibuat manual saat development). Login manual (email + password) adalah **alternatif** yang baru bisa dipakai **setelah** user mengatur password-nya sendiri di halaman Profil — lihat UC-01b.

**1a. Login via Google (jalur utama, sekaligus pembuatan akun baru)**

| Skenario Normal | Skenario Alternatif (Domain Email Tidak Valid) |
|---|---|
| 1. Pengguna menekan *Login dengan Google* | 1. Pengguna menekan *Login dengan Google* |
| 2. Pengguna memilih akun Google institusi | 2. Pengguna memilih akun Google **non-UNSIL** |
| 3. Sistem memvalidasi domain email (`@unsil.ac.id`/`@student.unsil.ac.id`) | 3. Sistem mendeteksi domain tidak sesuai |
| 4. Jika akun baru: sistem membuat `users` + entri `dosen`/`mahasiswa` otomatis; jika sudah ada: lanjut login | 4. Sistem menolak, pesan "Gunakan email institusi UNSIL" |
| 5. Sistem mengarahkan ke dashboard sesuai role | |

**1b. Login Manual (alternatif, hanya untuk akun yang sudah pernah set password)**

| Skenario Normal | Skenario Alternatif |
|---|---|
| 1. Pengguna memilih opsi *Login dengan Email & Password* | 1a. Pengguna memasukkan password yang salah |
| 2. Pengguna memasukkan email & password | 1b. Sistem menampilkan notifikasi "Email atau password salah" |
| 3. Sistem mengecek email terdaftar **dan** kolom `password` sudah terisi | 2a. Email terdaftar tapi `password` masih NULL (belum pernah di-set) |
| 4. Sistem memvalidasi kecocokan password | 2b. Sistem menolak dengan pesan "Akun ini belum mengaktifkan login manual. Silakan login dengan Google UNSIL, lalu atur password di halaman Profil." |
| 5. Sistem mengarahkan ke dashboard sesuai role | |

**Kebutuhan teknis**: Integrasi Laravel Socialite untuk Google OAuth, penanganan token via Laravel Sanctum untuk SPA, hashing password dengan bcrypt bawaan Laravel.

### UC-01b: Mengatur & Mengubah Password
**Aktor**: Admin, Dosen, Supervisor, Mahasiswa (semua role, terhadap akun masing-masing)

| Atur Password Pertama Kali | Ubah Password (Sudah Pernah Diatur) |
|---|---|
| 1. User (sudah login via Google) membuka Profil → "Atur Password Login" | 1. User membuka Profil → "Ubah Password" |
| 2. User mengisi password baru + konfirmasi password | 2. User mengisi password lama + password baru + konfirmasi |
| 3. Sistem menyimpan password (di-hash) ke akun | 3. Sistem memvalidasi password lama cocok |
| | 4. Jika cocok, sistem memperbarui password; jika tidak, tolak dengan pesan error |

**Catatan konteks keamanan**: Karena sistem ini hanya digunakan di lingkup internal kampus dan akun hanya bisa dibuat lewat Google OAuth UNSIL terlebih dahulu, proteksi tambahan seperti rate limiting percobaan login atau syarat kompleksitas password **tidak diwajibkan** untuk MVP ini.

### UC-02: Pengajuan Peminjaman Ruangan Lab
**Aktor**: Mahasiswa (pengaju), Supervisor/Admin (penyetuju)

> **Catatan**: Terpisah dari UC-02a (Kelas Lab/Praktikum). Slot yang sudah terisi jadwal Kelas Lab/Praktikum diperlakukan sama seperti pengajuan berstatus "Disetujui" — ikut dicek dalam validasi bentrok di langkah 3.

| Skenario Normal | Skenario Alternatif (Bentrok Jadwal / Ruangan Tidak Tersedia) |
|---|---|
| 1. Pengaju membuka kalender ketersediaan ruangan | 1. Pengaju membuka kalender ketersediaan ruangan |
| 2. Pengaju memilih ruangan, mengisi tanggal/waktu/keperluan | 2. Pengaju memilih ruangan & waktu yang **sudah dipesan/disetujui pengguna lain, sudah terisi jadwal Kelas Lab/Praktikum, atau ruangan berstatus tidak 'tersedia' ('perbaikan' / 'dipakai')** |
| 3. Sistem memvalidasi tidak ada bentrok jadwal pada ruangan & waktu yang sama (termasuk terhadap jadwal Kelas Lab/Praktikum) serta memastikan status ruangan adalah 'tersedia' | 3. Sistem mendeteksi bentrok atau ruangan tidak tersedia → menolak pengajuan dengan pesan error yang jelas |
| 4. Pengajuan tersimpan dengan status "Menunggu Persetujuan" | 4. Pengaju diarahkan memilih ulang waktu/ruangan lain |
| 5. Supervisor/Admin meninjau → Approve/Reject | |
| 6. Status pengajuan terupdate, pengaju menerima notifikasi | |

**Aturan validasi kunci**: Sistem **wajib** mencegah dua pengajuan dengan status "Disetujui" pada ruangan dan rentang waktu yang sama, mencegah pengajuan pada slot yang sudah terisi jadwal Kelas Lab/Praktikum (UC-02a), dan mencegah peminjaman pada ruangan yang statusnya bukan "tersedia" (misal: "perbaikan" atau "dipakai"). Jam peminjaman **wajib** dalam rentang operasional lab **07.00–17.00 WIB**. Peminjaman ruangan **hanya diajukan Mahasiswa** (Dosen tidak meminjam ruangan). Validasi dilakukan di level backend (bukan hanya di frontend) menggunakan Form Request.

> **Pengajuan satu/beberapa hari**: form peminjaman mendukung mode **Satu hari** (satu tanggal) atau **Beberapa hari** (pilih ≥2 hari + tanggal per hari, jam sama untuk semua). Setiap tanggal menghasilkan satu pengajuan terpisah; backend memvalidasi bentrok per tanggal.

### UC-02a: Membuka & Mendaftar Kelas Lab/Praktikum
**Aktor**: Admin/Supervisor/Dosen (pembuka kelas), Mahasiswa (pendaftar peserta)

> **Catatan**: Mekanisme **terpisah** dari UC-02. Dibuka di awal semester untuk sesi mengajar terjadwal tetap (umumnya mingguan) hingga akhir semester. **Admin & Supervisor** dapat membuka/mengelola **semua kelas** (wajib menunjuk `dosen_id` pengampu); **Dosen** hanya kelas miliknya sendiri.

| Skenario Normal (Pembukaan Kelas) | Skenario Pendaftaran Peserta |
|---|---|
| 1. Dosen (untuk dirinya) atau Admin/Supervisor (atas nama Dosen, menunjuk dosen pengampu) membuka menu Kelas Lab/Praktikum | 1. Mahasiswa membuka menu Kelas Lab/Praktikum |
| 2. Mengisi data: nama kelas/mata kuliah, ruangan, hari & jam (pola berulang mingguan), tanggal mulai-selesai semester, kuota peserta (maks. 30-40) | 2. Mahasiswa melihat daftar sesi yang tersedia (mis. Kelas A 08.00, Kelas B 10.00) beserta sisa kuota |
| 3. Dapat menambahkan beberapa sesi paralel untuk kelas/mata kuliah yang sama, masing-masing dengan kuota independen | 3. Mahasiswa memilih satu sesi dan mendaftar → status **menunggu persetujuan** |
| 4. Sistem memvalidasi tidak ada bentrok dengan jadwal ruangan lain yang sudah ada (sesuai UC-02 aturan validasi kunci) serta memastikan status ruangan adalah 'tersedia' | 4. Sistem memvalidasi kuota (slot dipesan menunggu+disetujui), 1-sesi-per-mata-kuliah, dan tidak bentrok jadwal kelas mahasiswa lainnya |
| 5. Jadwal Kelas Lab/Praktikum tersimpan dan langsung "mengisi" kalender ruangan untuk seluruh rentang semester | 5. **Dosen pengampu (atau Supervisor) menyetujui/menolak** pendaftaran lewat menu Persetujuan Pendaftaran; mahasiswa resmi jadi peserta setelah disetujui |

**Aturan validasi kunci**:
- Pendaftaran peserta **wajib disetujui** Dosen pengampu kelas atau Supervisor sebelum mahasiswa resmi menjadi peserta (status `menunggu` → `disetujui`/`ditolak`).
- Sistem **wajib** menolak pendaftaran baru jika kuota sesi sudah penuh (slot dihitung dari pendaftaran `menunggu` + `disetujui`).
- Satu mahasiswa **hanya boleh satu sesi per mata kuliah**, namun boleh mengambil mata kuliah berbeda selama **tidak bentrok jadwal** (hari sama + jam tumpang tindih).
- Sistem **wajib** mencegah pembukaan Kelas Lab/Praktikum baru yang bentrok dengan jadwal ruangan yang sudah terisi (Kelas Lab lain maupun peminjaman ruangan disetujui) atau jika status ruangan bukan 'tersedia'.
- Jam kelas **wajib** dalam rentang operasional lab **07.00–17.00 WIB**.

### UC-03: Peminjaman & Perpanjangan Perangkat
**Aktor**: Mahasiswa

| Skenario Normal | Skenario Tambahan (Perpanjangan) |
|---|---|
| 1. Mahasiswa membuka daftar perangkat berstatus "Tersedia" | 1. Mahasiswa membuka halaman *Peminjaman Saya* |
| 2. Mahasiswa memilih perangkat yang dibutuhkan | 2. Mahasiswa memilih item yang ingin diperpanjang |
| 3. Sistem mencatat durasi peminjaman, status perangkat berubah menjadi "Dipinjam" | 3. Mahasiswa mengajukan perpanjangan sebelum batas waktu pinjam habis |
| | 4. Pengajuan perpanjangan masuk ke antrian persetujuan Supervisor/Admin |

**Aturan validasi kunci**: Mahasiswa **tidak dapat** mengajukan perpanjangan setelah batas waktu pinjam terlewati (harus mengembalikan dan mengajukan peminjaman baru).

### UC-04: Pengumpulan Tugas Kelas Lab (menggantikan Presensi)
**Aktor**: Mahasiswa (pengumpul), Dosen pengampu/Supervisor/Admin (penetap deadline & peninjau)

> **Catatan**: Modul **Presensi** (check-in/check-out) pada v1.0 **dihapus** dan diganti modul **Pengumpulan Tugas**. Fokus lab bergeser dari pencatatan kehadiran fisik ke pemantauan kepatuhan pengumpulan tugas praktikum per pertemuan.

**Menetapkan materi/deadline (Dosen pengampu / Supervisor / Admin)**:
1. Reviewer membuka Detail Kelas Lab → memilih salah satu dari **16 pertemuan**
2. Reviewer mengisi **Nama Materi** dan/atau **Deadline**. Materi boleh berdiri sendiri (silabus) tanpa deadline; pertemuan tanpa deadline **tidak dihitung sebagai tugas**
3. Bila materi & deadline dikosongkan keduanya, record pertemuan dihapus (kembali "kosong")

**Mengumpulkan tugas (Mahasiswa)**:

| Skenario Normal | Skenario Alternatif |
|---|---|
| 1. Mahasiswa membuka Kelas Lab yang diikutinya → *Kirim Tugas* | 1a. Mahasiswa bukan peserta `disetujui` pada kelas → ditolak |
| 2. Memilih pertemuan (1–16), mengisi judul + tautan hasil tugas | 2a. Sudah pernah mengirim tugas untuk pertemuan yang sama → ditolak (satu tugas per pertemuan) |
| 3. Sistem menyimpan tugas; bila melewati deadline, tugas ditandai **"Terlambat"** (tetap diterima) | 3a. Tautan bukan URL valid → ditolak (validasi `url`) |
| 4. Dosen pengampu & Supervisor menerima notifikasi "tugas baru masuk" | |

**Aturan validasi kunci**:
- Tugas hanya boleh dikirim untuk Kelas Lab yang diikuti mahasiswa dengan status peserta **`disetujui`**.
- **Satu tugas per (kelas, pertemuan, mahasiswa)** — pengiriman kedua untuk pertemuan yang sama ditolak (hapus dulu yang lama untuk mengganti).
- **"Tanpa deadline = tidak ada tugas"**: status tepat/telat/belum hanya dihitung untuk pertemuan yang memiliki `deadline`.
- Deadline **tidak memblokir** pengiriman terlambat — hanya menandai; timestamp dibandingkan dalam **waktu lokal WIB**.
- Penetapan materi/deadline hanya oleh Dosen pengampu kelas (`dosen_id` cocok), Supervisor, atau Admin (scoping di backend).

### UC-05: Melihat Katalog Informasi Sertifikasi
**Aktor**: Mahasiswa (pengunjung informasi), Admin/Supervisor (pengelola konten)

> **Catatan sifat modul**: Modul ini **murni informasional**. SIM Lab. Riset tidak menangani proses pendaftaran sertifikasi (tidak ada form registrasi, upload berkas, kuota, atau status seleksi di dalam sistem). Sistem hanya menampilkan katalog sertifikasi yang diselenggarakan pihak eksternal (mis. Mikrotik, Oracle, Cisco, RedHat), sebagai referensi bagi mahasiswa. Proses pendaftaran sertifikasi yang sesungguhnya dilakukan mahasiswa secara langsung ke penyelenggara terkait, di luar sistem.

| Skenario Normal |
|---|
| 1. Admin/Supervisor menambahkan/mengubah entri sertifikasi di panel kelola (nama sertifikasi, penyelenggara, jadwal, persyaratan, cara/tautan pendaftaran ke pihak eksternal) |
| 2. Mahasiswa membuka menu Sertifikasi |
| 3. Sistem menampilkan daftar sertifikasi yang tersedia beserta detail informasinya |
| 4. Mahasiswa membaca detail dan, jika berminat, mengikuti instruksi/tautan pendaftaran menuju pihak penyelenggara eksternal |

### UC-06: Mengunduh Laporan
**Aktor**: Supervisor (Admin juga punya akses yang sama)

1. Supervisor membuka menu Report
2. Sistem menampilkan rekap (default: 1 bulan terakhir)
3. Supervisor memilih rentang tanggal kustom
4. Sistem menampilkan hasil rekap sesuai rentang yang dipilih
5. Supervisor memilih *Download PDF*
6. Sistem menghasilkan dan mengunduh file laporan

### UC-06a: Rekap Tugas Kelas Lab (per pertemuan)
**Aktor**: Admin, Supervisor, Dosen (Dosen di-scope ke kelas miliknya)

1. Pengguna membuka menu **Rekap Tugas**
2. Sistem menampilkan **ringkasan kepatuhan** semua kelas (untuk Dosen: hanya kelasnya) + **matriks detail per kelas** (peserta × pertemuan bertugas: tepat/telat/belum)
3. Data selalu mencerminkan tugas terbaru yang masuk (dihitung on-request, tanpa snapshot)
4. Pengguna memilih **Unduh PDF** atau **Unduh Excel (.xlsx)**
5. Sistem menghasilkan dan mengunduh file rekap terkini

**Aturan validasi kunci**: Otorisasi via Gate `view-rekap-tugas` (Admin/Supervisor/Dosen). Dosen **wajib** hanya dapat merekap kelas yang `dosen_id`-nya miliknya (scoping di backend, bukan hanya UI). Kolom matriks hanya mencakup pertemuan yang memiliki `deadline` (pertemuan tanpa deadline tidak dihitung sebagai tugas).

> **Rencana lanjutan (Tahap 2)**: sinkronisasi otomatis rekap ke Google Sheets (auto-update saat ada tugas masuk), ditunda hingga kredensial Google Cloud tersedia.

### UC-07: Notifikasi In-App
**Aktor**: Semua role (penerima); sistem (pembuat — otomatis, bukan aksi pengguna)

> **Sifat modul**: Notifikasi dibuat **otomatis oleh sistem** sebagai efek samping dari aksi lain. Pengguna tidak membuat notifikasi secara langsung.

**Pemicu dan penerima**:
| Pemicu | Penerima |
|---|---|
| Pengajuan peminjaman ruangan disetujui/ditolak | Pengaju (Mahasiswa/Dosen) |
| Pengajuan peminjaman perangkat disetujui/ditolak | Pengaju (Mahasiswa) |
| Pengajuan perpanjangan perangkat disetujui/ditolak | Pengaju (Mahasiswa) |
| Ada pengajuan peminjaman ruangan baru (status `menunggu`) | Semua Supervisor & Admin |
| Ada pengajuan peminjaman perangkat baru (status `menunggu`) | Semua Supervisor & Admin |
| Ada pengajuan perpanjangan baru (status `menunggu`) | Semua Supervisor & Admin |
| Pendaftaran Kelas Lab berhasil | Mahasiswa yang mendaftar |
| Tugas baru masuk pada sebuah kelas | Dosen pengampu kelas + semua Supervisor |
| Tenggat tugas terlewati & belum dikumpulkan (terjadwal) | Mahasiswa peserta `disetujui` yang belum mengumpulkan |
| Tenggat pengembalian perangkat mendekati/terlewati (terjadwal) | Mahasiswa peminjam perangkat terkait |

> Dua pemicu terakhir bertipe `pengingat` dan dihasilkan oleh **penjadwalan berkala** (Laravel Scheduler: `pengingat:deadline` per jam, `pengingat:pengembalian` harian) — bukan aksi langsung pengguna. Bersifat **idempoten** (tidak menduplikasi pengingat yang sama untuk pasangan pengguna–deadline yang sama).

**Alur pengguna**:
| Skenario Normal | Skenario Tandai Baca / Hapus |
|---|---|
| 1. Sistem membuat entri `notifikasi` otomatis saat pemicu terjadi (dalam transaksi yang sama) | 1. Pengguna mengklik ikon lonceng di navbar |
| 2. Badge angka merah pada ikon lonceng bertambah untuk penerima | 2. Pengguna memilih notifikasi → status berubah jadi "sudah dibaca", badge berkurang |
| 3. Pengguna melihat badge → klik ikon lonceng → daftar notifikasi tampil | 3. Atau: pengguna menekan "Tandai Semua Sudah Dibaca" |
| 4. Notifikasi belum dibaca ditandai secara visual | 4. Pengguna menghapus notifikasi satu per satu; notifikasi tidak hilang otomatis |

**Aturan validasi kunci**:
- Insert `notifikasi` dilakukan dalam **DB transaction yang sama** dengan aksi pemicunya — jika salah satu gagal, keduanya rollback
- Jumlah notifikasi belum dibaca (`is_read = false`) **wajib** disertakan di response `GET /api/auth/me` sebagai field `unread_notifications_count` — agar badge navbar tidak membutuhkan request terpisah saat pertama kali halaman dimuat
- Tidak ada auto-delete atau TTL — notifikasi hanya hilang jika pengguna eksplisit menghapusnya

---

## 4. Kebutuhan Non-Fungsional (NFR)

### 4.1 Keamanan (Security)
- Setiap endpoint API **wajib** diproteksi middleware `auth:sanctum`, kecuali endpoint publik (login, register, dan opsional: halaman informasi lab jika diputuskan dapat diakses tanpa login — defaultnya tetap memerlukan login, lihat PRD 2.5)
- Pengecekan role/hak akses **wajib** ketat sesuai matriks RBAC di Bagian 1, diimplementasikan via Laravel Policy/Gate
- Pencegahan SQL Injection, XSS, dan CSRF menggunakan fitur bawaan Laravel (Eloquent ORM, Form Request validation, CSRF token untuk form berbasis sesi bila relevan)
- Password disimpan dengan hashing bawaan Laravel (bcrypt/argon2), tidak pernah disimpan/dikirim dalam bentuk plain text
- File upload (berkas sertifikasi, portofolio) divalidasi tipe dan ukurannya sebelum disimpan

### 4.2 Ketersediaan & Performa (Availability & Performance)
- Aplikasi dikembangkan sebagai **Single Page Application (SPA)** terpisah dari backend, untuk transisi halaman yang cepat dan penghematan bandwidth lewat pemrosesan berbasis API JSON
- Query yang menampilkan data dalam jumlah besar (daftar perangkat, daftar tugas, laporan) **wajib** menggunakan pagination, tidak mengambil seluruh data sekaligus

### 4.3 Pemeliharaan (Maintainability)
- Pemisahan kode bersih antara logika bisnis backend (Laravel Controllers, Models, Policies, Form Requests) dan presentasi frontend (Vue Components, Composables)
- Mengikuti standar format kode otomatis: Laravel Pint (backend), Prettier (frontend) — lihat `.vscode/settings.json`
- Setiap perubahan skema database **wajib** lewat migration, tidak ada perubahan manual ke database produksi

### 4.4 Kualitas Perangkat Lunak
- **Ketersediaan**: sistem harus dapat diandalkan untuk menangani pengumpulan tugas dan pengajuan peminjaman setiap hari kerja lab
- **Ketepatan**: alur sistem sesuai dengan use case yang didefinisikan di Bagian 3, termasuk skenario alternatif/error handling
- **Pemulihan data**: backup database dilakukan berkala; jika terjadi kerusakan, pemulihan dilakukan dari backup terakhir atau log transaksi

---

## 5. Lingkup di Luar Dokumen Ini

Dokumen ini sengaja **tidak** membahas:
- Visi produk, persona, dan alur pengguna tingkat tinggi → lihat `1_PRD.md`
- Skema database (ERD), struktur API, dan arsitektur sistem → lihat `3_SDD.md`
- Breakdown task implementasi → lihat `4_TASK_BREAKDOWN.md`
