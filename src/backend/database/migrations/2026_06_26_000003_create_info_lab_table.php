<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Konten halaman informasi lab, satu baris per tipe konten (3_SDD.md 3.15).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('info_lab', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['beranda', 'visi_misi', 'kepala_lab', 'roadmap_kk'])->unique();
            $table->string('judul')->nullable();
            $table->longText('konten');
            $table->string('gambar')->nullable();
            // Admin terakhir yang mengubah konten
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('info_lab');
    }
};
