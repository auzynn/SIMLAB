# SUMMARY & RINGKASAN — Draft Pengganti

> Tempel menggantikan isi halaman SUMMARY dan RINGKASAN pada file Word.
> Gaya penulisan mengikuti format lama: satu blok paragraf + baris kata kunci.

---

## SUMMARY

The Research Laboratory of the Network, Computer, and Forensics (JKF) Expertise Group at the Informatics Study Program, Siliwangi University, still manages its administration manually, such as room reservations, equipment loans, lab class scheduling, and student assignment monitoring, which are spread across spreadsheets, paper forms, and instant messages. This manual process is prone to data duplication, difficult history tracking, and schedule conflicts between room reservations and ongoing lab class sessions. To overcome these problems, a web-based Research Laboratory Management Information System (SIM Lab. Riset) was designed and built, centralizing the entire lab administration process into a single platform with four user roles: Student, Lecturer, Supervisor (Lab Assistant), and Admin (Head of Laboratory). The system is built with an architecture that completely separates the frontend and the backend: the frontend is a Single Page Application using Vue 3, while the backend is a REST API using the Laravel framework with a MySQL database, communicating through JSON data exchange and token-based authentication (Laravel Sanctum) with Google OAuth 2.0 Single Sign-On restricted to UNSIL institutional emails. In this internship, the author was responsible for the backend side, covering architecture design, database schema design, and REST API implementation, including two-way schedule-conflict validation, role-based access control (RBAC) enforced through Laravel Gate/Policy, in-app notifications, and PDF/Excel report generation. The development was carried out iteratively in phases, and the final system passed 207 automated feature tests covering all modules.

Keywords: SIM Lab. Riset, REST API, Laravel, Vue 3, MySQL, RBAC

---

## RINGKASAN

Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) pada Program Studi Informatika Universitas Siliwangi masih mengelola administrasinya secara manual, seperti peminjaman ruangan, peminjaman perangkat, penjadwalan Kelas Lab/Praktikum, dan pemantauan tugas mahasiswa, yang tersebar di spreadsheet, formulir cetak, dan pesan singkat. Proses manual tersebut rentan menimbulkan duplikasi data, kesulitan pelacakan riwayat, serta bentrok jadwal antara peminjaman ruangan dengan sesi Kelas Lab yang sedang berjalan. Untuk mengatasi permasalahan tersebut, dirancang dan dibangun Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset) berbasis web yang memusatkan seluruh proses administrasi lab ke dalam satu platform dengan empat peran pengguna, yaitu Mahasiswa, Dosen, Supervisor (Asisten Lab), dan Admin (Kepala Lab). Sistem dibangun dengan arsitektur yang memisahkan sepenuhnya antara frontend dan backend: frontend berupa Single Page Application menggunakan Vue 3, sementara backend berupa REST API menggunakan framework Laravel dengan basis data MySQL, yang berkomunikasi melalui pertukaran data JSON dan autentikasi berbasis token (Laravel Sanctum) serta Single Sign-On Google OAuth 2.0 yang dibatasi untuk email institusi UNSIL. Dalam kerja praktek ini penulis bertanggung jawab pada sisi backend, meliputi perancangan arsitektur, perancangan skema basis data, dan implementasi REST API, termasuk validasi bentrok jadwal dua arah, pengendalian hak akses berbasis peran (RBAC) yang ditegakkan melalui Laravel Gate/Policy, notifikasi in-app, serta pembuatan laporan PDF/Excel. Pengembangan dilakukan secara iteratif per fase, dan sistem akhir lulus 207 pengujian fitur otomatis yang mencakup seluruh modul.

Kata Kunci: SIM Lab. Riset, REST API, Laravel, Vue 3, MySQL, RBAC
