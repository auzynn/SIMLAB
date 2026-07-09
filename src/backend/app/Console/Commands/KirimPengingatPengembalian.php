<?php

namespace App\Console\Commands;

use App\Services\PengingatPengembalianService;
use Illuminate\Console\Command;

/**
 * Kirim pengingat pengembalian perangkat untuk semua peminjaman yang jatuh tempo.
 * Dijadwalkan harian (bootstrap/app.php) dan bisa dijalankan manual saat demo.
 */
class KirimPengingatPengembalian extends Command
{
    protected $signature = 'pengingat:pengembalian';

    protected $description = 'Kirim notifikasi pengingat pengembalian perangkat yang jatuh tempo';

    public function handle(PengingatPengembalianService $pengingat): int
    {
        $jumlah = $pengingat->generate();

        $this->info("Pengingat pengembalian dibuat: {$jumlah}.");

        return self::SUCCESS;
    }
}
