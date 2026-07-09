<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pengumpulan Tugas — mahasiswa mengirim tautan tugas untuk Kelas Lab yang diikutinya.
// Dosen pengampu kelas (+ Supervisor) melihat & membuka tautan. Menggantikan modul Presensi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_lab_id')->constrained('kelas_lab')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('judul');
            $table->string('tautan', 2048);
            $table->timestamps();

            // Mempercepat pengambilan tugas per kelas (rekap dosen).
            $table->index('kelas_lab_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};
