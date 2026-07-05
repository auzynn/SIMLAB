<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data master perangkat lab: PC, Router, Switch, IoT Kit, dll (3_SDD.md 3.9, PRD 3.4).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perangkat', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perangkat');
            $table->string('nomor_seri')->unique();
            $table->string('kategori')->nullable();
            $table->enum('status', ['tersedia', 'dipinjam', 'perbaikan'])->default('tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat');
    }
};
