<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\KelasLab;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use Illuminate\Database\Seeder;

/**
 * Data demo Kelas Lab/Praktikum agar daftar & kalender ruangan tidak kosong saat fresh install.
 * Bergantung pada DosenSeeder, RuanganSeeder, MataKuliahSeeder. Idempotent via firstOrCreate.
 */
class KelasLabSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = Dosen::first();
        $ruangan = Ruangan::where('nama_ruangan', 'Lab Jaringan Komputer')->first() ?? Ruangan::first();
        $mk = MataKuliah::where('kode_mk', 'JKF301')->first() ?? MataKuliah::first();

        // Tanpa data master pendukung, tidak ada yang bisa di-seed.
        if (! $dosen || ! $ruangan || ! $mk) {
            return;
        }

        $sesi = [
            ['nama_sesi' => 'Kelas A', 'hari' => 'senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00'],
            ['nama_sesi' => 'Kelas B', 'hari' => 'rabu', 'jam_mulai' => '10:00:00', 'jam_selesai' => '12:00:00'],
        ];

        foreach ($sesi as $data) {
            KelasLab::firstOrCreate(
                [
                    'mata_kuliah_id' => $mk->id,
                    'nama_sesi' => $data['nama_sesi'],
                ],
                [
                    'dosen_id' => $dosen->id,
                    'ruangan_id' => $ruangan->id,
                    'dibuat_oleh' => $dosen->user_id,
                    'hari' => $data['hari'],
                    'jam_mulai' => $data['jam_mulai'],
                    'jam_selesai' => $data['jam_selesai'],
                    'tanggal_mulai_semester' => '2026-09-01',
                    'tanggal_selesai_semester' => '2026-12-31',
                    'kuota' => 30,
                ],
            );
        }
    }
}
