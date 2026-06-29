<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Pendaftaran mahasiswa sebagai peserta sesi Kelas Lab/Praktikum (3_SDD.md 3.8).
class KelasLabPeserta extends Model
{
    protected $table = 'kelas_lab_peserta';

    protected $fillable = [
        'kelas_lab_id',
        'mahasiswa_id',
        'status',
        'disetujui_oleh',
    ];

    public function kelasLab(): BelongsTo
    {
        return $this->belongsTo(KelasLab::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    // Dosen/Supervisor yang menyetujui/menolak pendaftaran.
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
