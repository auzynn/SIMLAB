<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Konten halaman informasi lab (beranda/visi_misi/kepala_lab/roadmap_kk) — 3_SDD.md 3.15.
class InfoLab extends Model
{
    // Nama tabel sesuai skema, bukan pluralisasi default
    protected $table = 'info_lab';

    protected $fillable = [
        'tipe',
        'judul',
        'konten',
        'gambar',
        'dosen_id',
        'updated_by',
    ];

    /**
     * Admin terakhir yang mengubah konten ini.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Dosen yang ditautkan (khusus tipe `kepala_lab`) untuk render kartu identitas.
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
