<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pendaftaran mahasiswa sebagai peserta sesi Kelas Lab/Praktikum (3_SDD.md 3.8, SRS UC-02a).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_lab_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_lab_id')->constrained('kelas_lab')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->timestamps();

            // Satu mahasiswa tak boleh mendaftar dua kali ke sesi yang sama (SRS UC-02a)
            $table->unique(['kelas_lab_id', 'mahasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_lab_peserta');
    }
};
