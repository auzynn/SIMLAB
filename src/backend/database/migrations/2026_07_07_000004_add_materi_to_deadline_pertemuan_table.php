<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nama materi per pertemuan bisa berdiri sendiri (silabus) tanpa harus ada tugas/deadline.
// Karena itu `deadline` dijadikan nullable, dan ditambah kolom `materi`.
// Sebuah record valid jika minimal salah satu (materi / deadline) terisi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deadline_pertemuan', function (Blueprint $table) {
            $table->string('materi')->nullable()->after('pertemuan');
            $table->dateTime('deadline')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('deadline_pertemuan', function (Blueprint $table) {
            $table->dropColumn('materi');
            $table->dateTime('deadline')->nullable(false)->change();
        });
    }
};
