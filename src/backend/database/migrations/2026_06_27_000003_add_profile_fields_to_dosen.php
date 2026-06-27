<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Field profil tambahan dosen agar halaman Detail Dosen sepadan dengan situs lama
// (Jenis Kelamin, Jabatan Fungsional, Tempat/Tanggal Lahir, Biografi). Lihat 3_SDD.md 3.2.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dosen', function (Blueprint $table) {
            $table->string('jenis_kelamin')->nullable()->after('nidn');
            $table->string('jabatan_fungsional')->nullable()->after('jenis_kelamin');
            $table->string('tempat_lahir')->nullable()->after('jabatan_fungsional');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->text('biografi')->nullable()->after('tanggal_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('dosen', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_kelamin',
                'jabatan_fungsional',
                'tempat_lahir',
                'tanggal_lahir',
                'biografi',
            ]);
        });
    }
};
