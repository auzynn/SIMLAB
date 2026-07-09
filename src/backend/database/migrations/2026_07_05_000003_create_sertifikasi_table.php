<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Katalog informasi sertifikasi eksternal — murni informasional, tanpa relasi ke users
// (3_SDD.md 3.13, SRS UC-05). CUD Admin/Supervisor; read semua role login.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sertifikasi');
            $table->string('penyelenggara');
            // Jadwal boleh berupa rentang/teks bebas bila belum pasti — disimpan sebagai string.
            $table->string('jadwal')->nullable();
            $table->text('persyaratan')->nullable();
            $table->string('tautan_pendaftaran')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikasi');
    }
};
