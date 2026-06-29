<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Pengajuan peminjaman ruangan oleh Mahasiswa/Dosen (3_SDD.md 3.5).
class PeminjamanRuangan extends Model
{
    protected $table = 'peminjaman_ruangan';

    protected $fillable = [
        'ruangan_id',
        'user_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'keperluan',
        'status',
        'disetujui_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    // Pengaju peminjaman (Mahasiswa/Dosen).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Supervisor/Admin yang menyetujui/menolak.
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
