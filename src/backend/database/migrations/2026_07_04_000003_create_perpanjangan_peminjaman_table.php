<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pengajuan perpanjangan waktu pinjam perangkat (3_SDD.md 3.11, SRS UC-03).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perpanjangan_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_perangkat_id')->constrained('peminjaman_perangkat')->cascadeOnDelete();
            $table->date('tanggal_kembali_baru');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perpanjangan_peminjaman');
    }
};
