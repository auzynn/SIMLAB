<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Hasil riset/proyek/publikasi milik mahasiswa (3_SDD.md 3.14).
class Portofolio extends Model
{
    // Nama tabel singular sesuai konvensi proyek (lihat dosen, mahasiswa, mata_kuliah).
    protected $table = 'portofolio';

    protected $fillable = ['user_id', 'judul', 'deskripsi', 'tautan', 'tanggal'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    // Pemilik portofolio (Mahasiswa).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
