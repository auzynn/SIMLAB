<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Fitur Presensi Lab digantikan modul Pengumpulan Tugas — tabel presensi tidak dipakai lagi.
// dropIfExists agar aman pada DB fresh (test) yang tak pernah membuat tabel ini.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('presensi');
    }

    public function down(): void
    {
        // Tidak dipulihkan — modul Presensi sudah dihentikan (diganti Tugas).
    }
};
