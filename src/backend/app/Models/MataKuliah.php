<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Data master mata kuliah/praktikum; induk sesi Kelas Lab (3_SDD.md 3.6).
class MataKuliah extends Model
{
    // Nama tabel singular sesuai konvensi proyek (lihat dosen, mahasiswa, bidang_minat).
    protected $table = 'mata_kuliah';

    protected $fillable = ['kode_mk', 'nama_mk', 'sks'];

    protected function casts(): array
    {
        return [
            'sks' => 'integer',
        ];
    }

    public function kelasLab(): HasMany
    {
        return $this->hasMany(KelasLab::class);
    }
}
