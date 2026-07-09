<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Deadline pengumpulan tugas per pertemuan (1–16) sebuah Kelas Lab, ditetapkan
// Dosen pengampu / Supervisor. Pertemuan tanpa record = tidak ada tugas/deadline.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_pertemuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_lab_id')->constrained('kelas_lab')->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan');
            $table->dateTime('deadline');
            $table->timestamps();

            // Satu deadline per pertemuan per kelas.
            $table->unique(['kelas_lab_id', 'pertemuan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_pertemuan');
    }
};
