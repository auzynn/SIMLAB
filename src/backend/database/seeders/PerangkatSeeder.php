<?php

namespace Database\Seeders;

use App\Models\Perangkat;
use Illuminate\Database\Seeder;

/**
 * Data awal perangkat lab (PC, Router, Switch, IoT Kit, dll) — agar halaman
 * Perangkat Lab & form peminjaman perangkat tidak kosong saat fresh install.
 * Idempotent via updateOrCreate berdasarkan nomor_seri (3_SDD.md 3.9, SRS UC-03).
 */
class PerangkatSeeder extends Seeder
{
    public function run(): void
    {
        $perangkat = [
            ['nama_perangkat' => 'PC Desktop Dell OptiPlex', 'nomor_seri' => 'PC-JKF-001', 'kategori' => 'PC', 'status' => 'tersedia'],
            ['nama_perangkat' => 'PC Desktop Dell OptiPlex', 'nomor_seri' => 'PC-JKF-002', 'kategori' => 'PC', 'status' => 'tersedia'],
            ['nama_perangkat' => 'Laptop ThinkPad T14', 'nomor_seri' => 'LP-JKF-001', 'kategori' => 'Laptop', 'status' => 'tersedia'],
            ['nama_perangkat' => 'Router Mikrotik RB941', 'nomor_seri' => 'RT-JKF-001', 'kategori' => 'Router', 'status' => 'tersedia'],
            ['nama_perangkat' => 'Router Mikrotik RB941', 'nomor_seri' => 'RT-JKF-002', 'kategori' => 'Router', 'status' => 'dipinjam'],
            ['nama_perangkat' => 'Switch Cisco Catalyst 2960', 'nomor_seri' => 'SW-JKF-001', 'kategori' => 'Switch', 'status' => 'tersedia'],
            ['nama_perangkat' => 'Access Point TP-Link EAP225', 'nomor_seri' => 'AP-JKF-001', 'kategori' => 'Access Point', 'status' => 'tersedia'],
            ['nama_perangkat' => 'IoT Kit Arduino Uno', 'nomor_seri' => 'IOT-JKF-001', 'kategori' => 'IoT Kit', 'status' => 'tersedia'],
            ['nama_perangkat' => 'IoT Kit Raspberry Pi 4', 'nomor_seri' => 'IOT-JKF-002', 'kategori' => 'IoT Kit', 'status' => 'tersedia'],
            ['nama_perangkat' => 'IoT Kit Raspberry Pi 4', 'nomor_seri' => 'IOT-JKF-003', 'kategori' => 'IoT Kit', 'status' => 'perbaikan'],
        ];

        foreach ($perangkat as $data) {
            Perangkat::updateOrCreate(['nomor_seri' => $data['nomor_seri']], $data);
        }
    }
}
