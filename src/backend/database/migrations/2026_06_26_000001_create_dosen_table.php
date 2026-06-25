<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Profil publik dosen. Dibuat otomatis saat user @unsil.ac.id registrasi (3_SDD.md 3.2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nidn')->nullable();
            $table->string('bidang_riset')->nullable();
            $table->text('roadmap_riset')->nullable();
            $table->text('publikasi')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosen');
    }
};
