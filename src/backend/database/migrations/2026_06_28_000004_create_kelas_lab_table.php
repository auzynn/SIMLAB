<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Jadwal Kelas Lab/Praktikum — sesi terjadwal mingguan satu semester (3_SDD.md 3.7, SRS UC-02a).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_lab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->foreignId('ruangan_id')->constrained('ruangan')->cascadeOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->string('nama_sesi');
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->date('tanggal_mulai_semester');
            $table->date('tanggal_selesai_semester');
            $table->integer('kuota');
            $table->timestamps();

            // Mempercepat cek bentrok per ruangan + hari
            $table->index(['ruangan_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_lab');
    }
};
