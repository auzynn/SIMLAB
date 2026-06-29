<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Data master ruangan lab; dikelola Admin/Supervisor (3_SDD.md 3.4).
class Ruangan extends Model
{
    // Nama tabel singular sesuai konvensi proyek (lihat dosen, mahasiswa, bidang_minat).
    protected $table = 'ruangan';

    protected $fillable = ['nama_ruangan', 'kapasitas', 'status'];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
        ];
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(PeminjamanRuangan::class);
    }

    public function kelasLab(): HasMany
    {
        return $this->hasMany(KelasLab::class);
    }
}
