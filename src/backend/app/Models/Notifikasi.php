<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Notifikasi in-app milik user (3_SDD.md 3.16, SRS UC-07).
class Notifikasi extends Model
{
    // Nama tabel singular sesuai konvensi proyek (lihat portofolio, mata_kuliah).
    protected $table = 'notifikasi';

    protected $fillable = ['user_id', 'judul', 'pesan', 'tipe', 'referensi_id', 'is_read'];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    // Penerima notifikasi.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
