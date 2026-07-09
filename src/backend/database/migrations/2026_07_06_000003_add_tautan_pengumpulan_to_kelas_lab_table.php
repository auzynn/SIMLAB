<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tautan pengumpulan dokumen (tempat unggah PDF/DOCX) yang diisi dosen saat membuat Kelas Lab,
// ditampilkan ke mahasiswa di form Kirim Tugas. Nullable di DB agar baris lama tetap valid;
// wajib diisi lewat validasi Store/Update KelasLab.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas_lab', function (Blueprint $table) {
            $table->string('tautan_pengumpulan', 2048)->nullable()->after('kuota');
        });
    }

    public function down(): void
    {
        Schema::table('kelas_lab', function (Blueprint $table) {
            $table->dropColumn('tautan_pengumpulan');
        });
    }
};
