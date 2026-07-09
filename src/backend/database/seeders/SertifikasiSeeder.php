<?php

namespace Database\Seeders;

use App\Models\Sertifikasi;
use Illuminate\Database\Seeder;

/**
 * Katalog awal sertifikasi eksternal yang relevan bagi mahasiswa KK JKF
 * (jaringan, keamanan, forensik digital). Idempotent via updateOrCreate — 3_SDD.md 3.13.
 */
class SertifikasiSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'nama_sertifikasi' => 'Mikrotik Certified Network Associate (MTCNA)',
                'penyelenggara' => 'Mikrotik',
                'jadwal' => 'Batch berkala — cek jadwal penyelenggara',
                'persyaratan' => 'Pemahaman dasar jaringan TCP/IP dan pengalaman konfigurasi RouterOS.',
                'tautan_pendaftaran' => 'https://mikrotik.com/training',
            ],
            [
                'nama_sertifikasi' => 'Cisco Certified Network Associate (CCNA)',
                'penyelenggara' => 'Cisco',
                'jadwal' => 'Sepanjang tahun (ujian terjadwal via Pearson VUE)',
                'persyaratan' => 'Dasar jaringan, routing, dan switching. Tidak ada prasyarat formal.',
                'tautan_pendaftaran' => 'https://www.cisco.com/site/us/en/learn/training-certifications/certifications/enterprise/ccna/index.html',
            ],
            [
                'nama_sertifikasi' => 'Certified Ethical Hacker (CEH)',
                'penyelenggara' => 'EC-Council',
                'jadwal' => 'Batch berkala — cek jadwal penyelenggara',
                'persyaratan' => 'Pengetahuan dasar keamanan jaringan dan sistem operasi.',
                'tautan_pendaftaran' => 'https://www.eccouncil.org/train-certify/certified-ethical-hacker-ceh/',
            ],
            [
                'nama_sertifikasi' => 'Oracle Database SQL Certified Associate',
                'penyelenggara' => 'Oracle',
                'jadwal' => 'Sepanjang tahun (ujian terjadwal)',
                'persyaratan' => 'Pemahaman dasar SQL dan basis data relasional.',
                'tautan_pendaftaran' => 'https://education.oracle.com/certification',
            ],
        ];

        foreach ($items as $item) {
            Sertifikasi::updateOrCreate(
                ['nama_sertifikasi' => $item['nama_sertifikasi']],
                $item,
            );
        }
    }
}
