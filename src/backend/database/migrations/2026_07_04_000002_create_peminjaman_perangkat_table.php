<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pengajuan peminjaman perangkat oleh Mahasiswa (3_SDD.md 3.10, SRS UC-03).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_perangkat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perangkat_id')->constrained('perangkat')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_pinjam');
            $table->date('tanggal_kembali_rencana');
            $table->date('tanggal_kembali_aktual')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'dikembalikan'])->default('menunggu');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Mempercepat cek peminjaman aktif per perangkat (mis. saat hapus perangkat/approve)
            $table->index(['perangkat_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_perangkat');
    }
};
