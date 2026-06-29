<?php

namespace Database\Seeders;

use App\Models\Ruangan;
use Illuminate\Database\Seeder;

/**
 * Data awal ruangan Laboratorium Riset KK JKF — agar form peminjaman & kelas lab
 * tidak kosong saat fresh install. Idempotent via updateOrCreate (3_SDD.md 3.4).
 */
class RuanganSeeder extends Seeder
{
    public function run(): void
    {
        $ruangan = [
            ['nama_ruangan' => 'Lab Riset JKF', 'kapasitas' => 30, 'status' => 'tersedia'],
            ['nama_ruangan' => 'Lab Jaringan Komputer', 'kapasitas' => 40, 'status' => 'tersedia'],
            ['nama_ruangan' => 'Lab Forensik Digital', 'kapasitas' => 20, 'status' => 'tersedia'],
        ];

        foreach ($ruangan as $data) {
            Ruangan::updateOrCreate(['nama_ruangan' => $data['nama_ruangan']], $data);
        }
    }
}
