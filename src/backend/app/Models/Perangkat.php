<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Data master perangkat lab (3_SDD.md 3.9).
class Perangkat extends Model
{
    protected $table = 'perangkat';

    protected $fillable = [
        'nama_perangkat',
        'nomor_seri',
        'kategori',
        'status',
    ];

    public function peminjaman(): HasMany
    {
        return $this->hasMany(PeminjamanPerangkat::class);
    }
}
