<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Pengajuan perpanjangan waktu pinjam perangkat (3_SDD.md 3.11, SRS UC-03).
class PerpanjanganPeminjaman extends Model
{
    protected $table = 'perpanjangan_peminjaman';

    protected $fillable = [
        'peminjaman_perangkat_id',
        'tanggal_kembali_baru',
        'status',
        'disetujui_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kembali_baru' => 'date:Y-m-d',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(PeminjamanPerangkat::class, 'peminjaman_perangkat_id');
    }

    // Supervisor/Admin yang menyetujui/menolak.
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
