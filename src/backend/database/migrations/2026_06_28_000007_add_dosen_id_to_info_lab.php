<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Profil Kepala Lab kini bisa ditautkan ke satu entri dosen agar dirender sebagai
// kartu identitas terstruktur (bukan hanya konten bebas). Nullable & berlaku khusus tipe kepala_lab.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('info_lab', function (Blueprint $table) {
            $table->foreignId('dosen_id')->nullable()->after('gambar')->constrained('dosen')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('info_lab', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dosen_id');
        });
    }
};
