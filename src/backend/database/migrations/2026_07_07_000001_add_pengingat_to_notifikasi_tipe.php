<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah nilai 'pengingat' pada enum notifikasi.tipe untuk DB yang SUDAH ter-migrasi
 * (dev MySQL) — non-destruktif, tidak menyentuh data. Fresh install & test (sqlite)
 * sudah memuat nilai ini dari migrasi create_notifikasi_table. ALTER ... MODIFY ENUM
 * khusus MySQL; sqlite dilewati (kolom di sana varchar + check dari enum()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifikasi MODIFY tipe ENUM('pengajuan_masuk', 'status_pengajuan', 'pendaftaran', 'pengingat') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifikasi MODIFY tipe ENUM('pengajuan_masuk', 'status_pengajuan', 'pendaftaran') NOT NULL");
        }
    }
};
