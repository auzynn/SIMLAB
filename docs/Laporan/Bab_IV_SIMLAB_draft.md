# BAB IV — SIMPULAN DAN SARAN (Draft Pengganti)

> Tempel menggantikan isi BAB IV lama. Judul bab di dokumen lama tertulis "SIMPULAN" saja — pertimbangkan menjadi "SIMPULAN DAN SARAN" agar sesuai isi.

---

## 4.1 Simpulan

Berdasarkan hasil kerja praktek yang dilaksanakan di Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) Program Studi Informatika Universitas Siliwangi, dapat disimpulkan:

1. Telah berhasil dirancang dan dibangun backend Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset) berbasis REST API menggunakan framework Laravel dengan basis data MySQL, yang memusatkan administrasi laboratorium — peminjaman ruangan, peminjaman dan perpanjangan perangkat, penjadwalan Kelas Lab/Praktikum, pengumpulan dan rekap tugas, katalog sertifikasi, portofolio, laporan, serta notifikasi in-app — ke dalam satu platform dengan empat peran pengguna (Admin, Supervisor, Dosen, dan Mahasiswa).
2. Arsitektur yang memisahkan sepenuhnya frontend (Vue 3 SPA) dan backend (Laravel REST API) melalui pertukaran data JSON dan autentikasi token Laravel Sanctum, ditambah Single Sign-On Google OAuth 2.0 yang dibatasi email institusi UNSIL, terbukti memudahkan pembagian kerja tim serta memusatkan seluruh logika bisnis dan otorisasi (RBAC melalui Laravel Gate/Policy) di sisi backend.
3. Validasi bentrok jadwal dua arah antara peminjaman ruangan dan jadwal Kelas Lab, alur persetujuan berjenjang, serta notifikasi in-app yang dibuat dalam transaksi basis data yang sama dengan aksi pemicunya, menjadikan proses administrasi laboratorium transparan, terlacak, dan bebas bentrok.
4. Kualitas sistem diverifikasi melalui 207 pengujian fitur otomatis (feature test) dengan 494 assertion yang seluruhnya lulus, mencakup skenario normal maupun skenario alternatif pada seluruh modul backend.

## 4.2 Saran

Adapun saran yang diberikan untuk pengembangan selanjutnya adalah sebagai berikut:

1. Sistem yang dibangun merupakan versi pengembangan pertama; diperlukan pengujian lanjutan seperti usability testing kepada pengguna nyata (mahasiswa, dosen, dan pengelola lab), pengujian performa dengan data berskala besar, serta penetration testing sebelum digunakan secara penuh di lingkungan produksi.
2. Deployment ke server institusi beserta konfigurasi pendukungnya (HTTPS, backup basis data berkala, dan pemantauan) perlu dilakukan agar sistem dapat segera dimanfaatkan oleh Laboratorium Riset KK JKF.
3. Fitur notifikasi dapat dikembangkan lebih lanjut ke kanal eksternal (email atau push notification) agar pengguna tetap terinformasi tanpa harus membuka aplikasi.
4. Modul katalog sertifikasi yang saat ini murni informasional dapat dikembangkan menjadi modul pendaftaran terintegrasi (kuota, unggah berkas, dan status seleksi) apabila dibutuhkan oleh laboratorium.

---

# CATATAN REVISI DAFTAR PUSTAKA & LAMPIRAN

- **Daftar Pustaka**: rujukan lama seputar CMS/monolith perlu diganti dengan rujukan yang relevan: dokumentasi Laravel (laravel.com/docs), Vue.js (vuejs.org), MySQL, Laravel Sanctum, Google OAuth 2.0/Socialite, serta pustaka REST API (mis. Fielding, R. T. — *Architectural Styles and the Design of Network-based Software Architectures*, 2000, untuk definisi REST).
- **Lampiran**: ganti tangkapan layar lama (Siswa Foundation) dengan: tampilan halaman SIM Lab. Riset, hasil eksekusi test, contoh PDF/Excel rekap, dan potongan kode lengkap (Model/Controller/Service) yang dirujuk dari Bab III.
- **Daftar Gambar/Tabel**: perbarui setelah semua bagian ditempel (References → Update Table di Word).
