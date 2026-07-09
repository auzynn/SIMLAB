<?php

namespace App\Console\Commands;

use App\Services\PengingatDeadlineService;
use Illuminate\Console\Command;

/**
 * Kirim pengingat tenggat tugas untuk semua deadline pertemuan yang sudah terlewati
 * dan belum dikumpulkan. Dijadwalkan (bootstrap/app.php) dan bisa dijalankan manual.
 */
class KirimPengingatDeadline extends Command
{
    protected $signature = 'pengingat:deadline';

    protected $description = 'Kirim notifikasi tenggat tugas terlewati ke mahasiswa yang belum mengumpulkan';

    public function handle(PengingatDeadlineService $pengingat): int
    {
        $jumlah = $pengingat->generate();

        $this->info("Pengingat tenggat tugas dibuat: {$jumlah}.");

        return self::SUCCESS;
    }
}
