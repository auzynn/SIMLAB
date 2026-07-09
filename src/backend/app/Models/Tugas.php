<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Pengumpulan Tugas mahasiswa (tautan/URL) untuk sebuah sesi Kelas Lab yang diikutinya.
class Tugas extends Model
{
    // Nama tabel singular sesuai skema, bukan pluralisasi default.
    protected $table = 'tugas';

    protected $fillable = [
        'kelas_lab_id',
        'pertemuan',
        'mahasiswa_id',
        'judul',
        'tautan',
    ];

    protected function casts(): array
    {
        return [
            'pertemuan' => 'integer',
        ];
    }

    // Sesi Kelas Lab yang menjadi konteks tugas.
    public function kelasLab(): BelongsTo
    {
        return $this->belongsTo(KelasLab::class);
    }

    // Mahasiswa pengirim tugas.
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
