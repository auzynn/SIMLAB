<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Notifikasi in-app (3_SDD.md 3.16, 5.14, SRS UC-07). Dibuat otomatis oleh sistem
// sebagai efek samping aksi lain (approve/reject/pengajuan baru) dalam transaksi yang sama.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('pesan');
            // Jenis notifikasi menentukan ikon/warna di frontend.
            // 'pengingat' = pengingat tenggat pengembalian perangkat (UC-07).
            $table->enum('tipe', ['pengajuan_masuk', 'status_pengajuan', 'pendaftaran', 'pengingat']);
            // ID entitas pemicu (peminjaman/perpanjangan/kelas) untuk navigasi — tanpa FK (lintas tabel).
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Mempercepat hitung unread & list milik user (SDD 3.16).
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
