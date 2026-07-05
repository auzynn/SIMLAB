<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Pengajuan peminjaman perangkat oleh Mahasiswa (3_SDD.md 3.10).
class PeminjamanPerangkat extends Model
{
    protected $table = 'peminjaman_perangkat';

    protected $fillable = [
        'perangkat_id',
        'user_id',
        'tanggal_pinjam',
        'tanggal_kembali_rencana',
        'tanggal_kembali_aktual',
        'status',
        'disetujui_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pinjam' => 'date:Y-m-d',
            'tanggal_kembali_rencana' => 'date:Y-m-d',
            'tanggal_kembali_aktual' => 'date:Y-m-d',
        ];
    }

    public function perangkat(): BelongsTo
    {
        return $this->belongsTo(Perangkat::class);
    }

    // Pengaju peminjaman (selalu Mahasiswa — SRS Bagian 1).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Supervisor/Admin yang menyetujui/menolak/mengonfirmasi pengembalian.
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function perpanjangan(): HasMany
    {
        return $this->hasMany(PerpanjanganPeminjaman::class);
    }
}
