<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Setiap tugas kini terikat satu pertemuan (1–16) dalam satu semester Kelas Lab,
// sehingga pengumpulan tugas projek terpetakan per pertemuan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas', function (Blueprint $table) {
            $table->unsignedTinyInteger('pertemuan')->default(1)->after('kelas_lab_id');
        });
    }

    public function down(): void
    {
        Schema::table('tugas', function (Blueprint $table) {
            $table->dropColumn('pertemuan');
        });
    }
};
