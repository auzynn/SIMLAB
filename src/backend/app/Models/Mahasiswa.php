<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mahasiswa extends Model
{
    // Nama tabel singular sesuai skema (3_SDD.md 3.3), bukan pluralisasi default
    protected $table = 'mahasiswa';

    protected $fillable = [
        'user_id',
        'dosen_pembimbing_id',
        'npm',
        'prodi',
        'angkatan',
        'foto',
    ];

    /**
     * Akun user pemilik profil mahasiswa ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dosen pembimbing (nullable).
     */
    public function dosenPembimbing(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_pembimbing_id');
    }
}
