<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Master bidang minat; dipilih Dosen di Edit Profil (banyak-banyak via dosen_bidang_minat).
class BidangMinat extends Model
{
    // Nama tabel singular sesuai konvensi proyek (lihat dosen, mahasiswa, info_lab).
    protected $table = 'bidang_minat';

    protected $fillable = ['nama'];

    public function dosen(): BelongsToMany
    {
        return $this->belongsToMany(Dosen::class, 'dosen_bidang_minat');
    }
}
