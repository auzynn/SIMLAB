<?php

namespace Database\Seeders;

use App\Models\InfoLab;
use Illuminate\Database\Seeder;

/**
 * Data awal konten info lab — satu baris per tipe (3_SDD.md 3.15, T2.4).
 * Konten placeholder; Admin menyunting lewat panel Konten Info Lab.
 */
class InfoLabSeeder extends Seeder
{
    public function run(): void
    {
        // Profil Kepala Lab — markdown tabel agar sepadan dengan tampilan halaman lama
        // (Jenis Kelamin, Jabatan Fungsional, NIDN, dst.). marked merender tabel GFM ini.
        // Visi & Misi — markdown (h3 + daftar bernomor) agar sepadan halaman lama.
        $visiMisi = <<<'MD'
        ### Visi

        Menjadi Laboratorium Fakultas Teknik yang memiliki karakter wirausaha, menghasilkan sumber daya manusia yang unggul dan berwawasan kebangsaan menuju kampus bahagia di tahun 2026

        ### Misi

        - Membangun ekosistem akademik di lingkungan Laboratorium Fakultas Teknik dengan menjunjung tinggi kaidah-kaidah norma agama dan norma sosial, kebhinekaan dan toleransi antar agama dan pemeluknya.
        - Mengembangkan Laboratorium untuk bidang ilmu Jaringan Komputer, Keamanan, Forensika Digital dan Internet of Things (IoT) di lingkungan Fakultas Teknik dengan memanfaatkan teknologi yang berkembang sehingga menghasilkan sumber daya manusia yang tangguh, mandiri dan bertanggung jawab.
        - Mengembangkan Ilmu Pengetahuan dan Teknologi melalui kerjasama/kolaborasi penelitian dan pengabdian masyarakat.
        - Melaksanakan pengabdian masyarakat sebagai wahana implementasi dari hasil penelitian untuk menumbuhkan kewirausahaan bidang ilmu Jaringan Komputer, Forensika Digital dan Internet of Things (IoT) dalam mendukung prioritas pembangunan nasional.
        - Melaksanakan layanan akademik mata kuliah praktikum dan layanan non-akademik berbasis MBKM di lingkungan Fakultas Teknik ataupun Program Studi Informatika secara transparan, akuntabel, kredibel, untuk menghasilkan sumber daya manusia yang memiliki daya saing global.
        - Meningkatkan dan mengembangkan harmonisasi kerjasama berbasis MBKM di lingkungan Program Studi Informatika ataupun Fakultas Teknik pada skala nasional dan internasional yang berkualitas untuk meningkatkan daya saing institusi menuju world class-university (WCU).
        - Meningkatkan layanan yang dapat mendukung kegiatan kemahasiswaan menuju mahasiswa yang kompetitif pada bidang keilmuan Jaringan Komputer, Forensika Digital dan Internet of Things (IoT).
        - Mewujudkan Laboratorium yang dapat mendukung program Green Campus.
        MD;

        $kepalaLab = <<<'MD'
        |  |  |
        | --- | --- |
        | **Jenis Kelamin** | : Laki-laki |
        | **Jabatan Fungsional** | : Lektor |
        | **NIDN** | : 310127203 |
        | **Tempat dan Tanggal Lahir** | : Jakarta, 10 Desember 1972 |
        | **Email** | : nur.widiyasono@unsil.ac.id, nur.w095@gmail.com |
        | **Nomor Telepon** | : 0819-0968-0432 / 0896-7641-6325 |
        | **Bidang Minat** | : Digital Forensik, Network Engineering, System Engineering, Internet of Things, AI, IoT, Cloud Computing, Security Engineering |
        MD;

        $defaults = [
            'beranda' => [
                'judul' => 'Beranda',
                'konten' => 'Selamat datang di Laboratorium Riset KK JKF Prodi Informatika.',
                'gambar' => null,
            ],
            'visi_misi' => [
                'judul' => 'Visi dan Misi',
                'konten' => $visiMisi,
                'gambar' => null,
            ],
            'kepala_lab' => [
                'judul' => 'Ir. Nur Widiyasono, S.Kom., M.Kom., CEH., CHFI., CITAP., MCE.',
                'konten' => $kepalaLab,
                // Foto disajikan dari frontend public/ → URL stabil, dapat diganti Admin lewat panel
                'gambar' => '/nur-widiyasono.jpg',
            ],
            'roadmap_kk' => [
                'judul' => 'Roadmap Laboratorium',
                'konten' => 'Roadmap riset Kelompok Keahlian JKF.',
                'gambar' => null,
            ],
        ];

        foreach ($defaults as $tipe => $row) {
            InfoLab::firstOrCreate(['tipe' => $tipe], $row);
        }
    }
}
