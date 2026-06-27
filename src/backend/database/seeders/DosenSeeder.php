<?php

namespace Database\Seeders;

use App\Models\BidangMinat;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal dosen — menyalin profil dari situs lama (halaman Profil/Detail Dosen).
 * Saat ini hanya satu dosen nyata: Ir. Nur Widiyasono (yang juga Kepala Lab).
 *
 * Akun dosen normalnya lahir lewat Google OAuth (password NULL); di-seed di sini
 * agar Daftar Dosen tidak kosong saat fresh install. Idempotent via updateOrCreate.
 */
class DosenSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'nur.widiyasono@unsil.ac.id'],
            [
                'name' => 'Ir. Nur Widiyasono, S.Kom., M.Kom., CEH., CHFI., CITAP., MCE.',
                'no_telp' => '0819-0968-0432 / 0896-7641-6325',
                'role' => 'dosen',
                'avatar' => '/nur-widiyasono.jpg',
                'email_verified_at' => now(),
                // Password di-set agar dapat langsung login manual untuk keperluan demo/uji
                'password' => Hash::make('dosen123'),
            ]
        );

        $dosen = Dosen::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nidn' => '310127203',
                'jenis_kelamin' => 'Laki-laki',
                'jabatan_fungsional' => 'Lektor',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1972-12-10',
                'biografi' => 'Ir. Nur Widiyasono, S.Kom., M.Kom., merupakan dosen tetap Program Studi Informatika Universitas Siliwangi dengan jabatan fungsional Lektor. Beliau menekuni bidang Digital Forensik, Network Engineering, dan Keamanan Sistem, serta aktif membimbing riset mahasiswa di Laboratorium Riset KK JKF.',
                'roadmap_riset' => "Roadmap penelitian difokuskan pada pengembangan riset Digital Forensik dan keamanan jaringan:\n\n- 2023–2024: Penguatan riset forensik digital & analisis bukti elektronik.\n- 2024–2025: Pengembangan keamanan jaringan dan Internet of Things (IoT).\n- 2025–2026: Integrasi kecerdasan buatan untuk deteksi ancaman siber.\n- 2026 ke depan: Kolaborasi riset cloud security & forensik berbasis AI.",
                'foto' => '/nur-widiyasono.jpg',
            ]
        );

        // Master Bidang Minat dari profil lama → entri master + dilampirkan ke dosen ybs.
        $bidangMinat = [
            'Digital Forensik',
            'Network Engineering',
            'System Engineering',
            'Internet of Things',
            'Artificial Intelligence',
            'Cloud Computing',
            'Security Engineering',
        ];

        $ids = collect($bidangMinat)
            ->map(fn ($nama) => BidangMinat::firstOrCreate(['nama' => $nama])->id)
            ->all();

        // syncWithoutDetaching: idempoten & tak menghapus relasi lain bila sudah ada
        $dosen->bidangMinat()->syncWithoutDetaching($ids);
    }
}
