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
        'jenis_kelamin',
        'jabatan_fungsional',
        'tempat_lahir',
        'tanggal_lahir',
        'biografi',
        'roadmap_riset',
        'publikasi',
        'foto',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date:Y-m-d',
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
     * Bidang minat yang dipilih dosen (many-to-many via pivot `dosen_bidang_minat`).
     * Satu-satunya sumber bidang minat (tak ada lagi kolom free-text di tabel dosen).
     */
    public function bidangMinat(): BelongsToMany
    {
        return $this->belongsToMany(BidangMinat::class, 'dosen_bidang_minat')
            ->orderBy('nama');
    }
}
