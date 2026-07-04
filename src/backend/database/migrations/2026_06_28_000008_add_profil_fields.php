<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Email cadangan (bukan login) untuk semua akun + field akademik tambahan dosen.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Email pribadi/cadangan — hanya info kontak, TIDAK dipakai untuk login.
            $table->string('email_pribadi')->nullable()->after('email');
        });

        Schema::table('dosen', function (Blueprint $table) {
            $table->text('credential')->nullable()->after('biografi');
            $table->text('buku')->nullable()->after('publikasi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_pribadi');
        });
        Schema::table('dosen', function (Blueprint $table) {
            $table->dropColumn(['credential', 'buku']);
        });
    }
};
