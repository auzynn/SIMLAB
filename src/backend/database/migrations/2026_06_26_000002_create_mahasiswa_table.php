<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Profil mahasiswa. Dibuat otomatis saat user @student.unsil.ac.id registrasi (3_SDD.md 3.3).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            // Dosen pembimbing untuk validasi bimbingan; nullable, set null saat dosen dihapus
            $table->foreignId('dosen_pembimbing_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('npm')->unique();            // diisi otomatis dari local-part email, immutable
            $table->string('prodi')->nullable();        // diisi menyusul
            $table->string('angkatan', 4);              // 2 digit awal npm + prefix "20" (string)
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
