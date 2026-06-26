<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Master data bidang riset; dikelola oleh Admin/Supervisor lalu dipilih Dosen lewat Edit Profil.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidang_riset', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        // Pivot many-to-many dosen ↔ bidang_riset (dosen bisa memilih banyak bidang)
        Schema::create('dosen_bidang_riset', function (Blueprint $table) {
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->foreignId('bidang_riset_id')->constrained('bidang_riset')->cascadeOnDelete();
            $table->primary(['dosen_id', 'bidang_riset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosen_bidang_riset');
        Schema::dropIfExists('bidang_riset');
    }
};
