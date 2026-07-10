<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah nilai 'kadaluarsa' pada enum peminjaman_ruangan.status untuk DB yang SUDAH
 * ter-migrasi (dev MySQL) — non-destruktif, tidak menyentuh data. Fresh install & test
 * (sqlite) sudah memuat nilai ini dari migrasi create_peminjaman_ruangan_table. ALTER ...
 * MODIFY ENUM khusus MySQL; sqlite dilewati (kolom di sana varchar + check dari enum()).
 *
 * 'kadaluarsa' = pengajuan otomatis gugur saat approve karena slot sudah penuh/bentrok,
 * dibedakan dari 'ditolak' yang berarti penolakan manual oleh approver.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE peminjaman_ruangan MODIFY status ENUM('menunggu', 'disetujui', 'ditolak', 'kadaluarsa') NOT NULL DEFAULT 'menunggu'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE peminjaman_ruangan MODIFY status ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu'");
        }
    }
};
