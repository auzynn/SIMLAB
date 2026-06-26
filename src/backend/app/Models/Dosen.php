<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    // Nama tabel singular sesuai skema (3_SDD.md 3.2), bukan pluralisasi default
    protected $table = 'dosen';

    protected $fillable = [
        'user_id',
        'nidn',
        'bidang_riset',
        'roadmap_riset',
        'publikasi',
        'foto',
    ];

    /**
     * Akun user pemilik profil dosen ini (nama/email/avatar diambil dari sini).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mahasiswa yang dibimbing dosen ini (via dosen_pembimbing_id).
     */
    public function mahasiswaBimbingan(): HasMany
    {
        return $this->hasMany(Mahasiswa::class, 'dosen_pembimbing_id');
    }

    /**
     * Bidang riset yang dipilih dosen (many-to-many; kolom string `bidang_riset`
     * dipertahankan sebagai legacy/free-text, sumber utama kini relasi ini).
     */
    public function bidangRiset(): BelongsToMany
    {
        return $this->belongsToMany(BidangRiset::class, 'dosen_bidang_riset')
            ->orderBy('nama');
    }
}
