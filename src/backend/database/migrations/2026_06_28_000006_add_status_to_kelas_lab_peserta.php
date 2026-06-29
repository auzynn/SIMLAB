<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pendaftaran Kelas Lab kini butuh persetujuan Dosen/Supervisor (status + penyetuju).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas_lab_peserta', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu')->after('mahasiswa_id');
            $table->foreignId('disetujui_oleh')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kelas_lab_peserta', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disetujui_oleh');
            $table->dropColumn('status');
        });
    }
};
