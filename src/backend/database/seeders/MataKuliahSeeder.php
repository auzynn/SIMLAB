<?php

namespace Database\Seeders;

use App\Models\MataKuliah;
use Illuminate\Database\Seeder;

/**
 * Data awal mata kuliah/praktikum KK JKF — dipilih Dosen saat membuka Kelas Lab.
 * Idempotent via updateOrCreate berdasarkan kode_mk (3_SDD.md 3.6).
 */
class MataKuliahSeeder extends Seeder
{
    public function run(): void
    {
        $mataKuliah = [
            ['kode_mk' => 'JKF301', 'nama_mk' => 'Praktikum Jaringan Komputer', 'sks' => 3],
            ['kode_mk' => 'JKF302', 'nama_mk' => 'Praktikum Keamanan Jaringan', 'sks' => 3],
            ['kode_mk' => 'JKF303', 'nama_mk' => 'Praktikum Forensik Digital', 'sks' => 3],
            ['kode_mk' => 'JKF304', 'nama_mk' => 'Praktikum Internet of Things', 'sks' => 2],
        ];

        foreach ($mataKuliah as $data) {
            MataKuliah::updateOrCreate(['kode_mk' => $data['kode_mk']], $data);
        }
    }
}
