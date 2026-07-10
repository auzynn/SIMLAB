<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pemilik entri katalog sertifikasi (2_SRS.md Bagian 1, revisi RBAC): Dosen boleh
// menambah referensi sertifikasinya sendiri & hanya boleh ubah/hapus miliknya.
// Admin/Supervisor tetap kelola semua. Entri lama: created_by NULL (kelola Admin/Supervisor).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sertifikasi', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('tautan_pendaftaran')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sertifikasi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
