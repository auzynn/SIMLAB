<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Katalog informasi sertifikasi eksternal (Mikrotik, Oracle, Cisco, dll) — 3_SDD.md 3.13.
// Hanya menampilkan info, bukan transaksi pendaftaran. `created_by` menandai pemilik entri
// (Dosen hanya boleh ubah/hapus miliknya; Admin/Supervisor kelola semua) — SertifikasiPolicy.
class Sertifikasi extends Model
{
    // Nama tabel singular sesuai konvensi proyek (lihat dosen, mahasiswa, mata_kuliah).
    protected $table = 'sertifikasi';

    protected $fillable = [
        'nama_sertifikasi',
        'penyelenggara',
        'jadwal',
        'persyaratan',
        'tautan_pendaftaran',
        'created_by',
    ];

    // Pembuat entri (nullable untuk entri lama sebelum kolom pemilik ditambahkan).
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
