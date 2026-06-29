<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pengajuan peminjaman ruangan oleh Mahasiswa/Dosen (3_SDD.md 3.5, SRS UC-02).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_ruangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruangan_id')->constrained('ruangan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->text('keperluan');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Mempercepat cek bentrok & filter kalender per ruangan/tanggal/status
            $table->index(['ruangan_id', 'tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_ruangan');
    }
};
