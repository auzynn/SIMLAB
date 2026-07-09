<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Katalog informasi sertifikasi eksternal (Mikrotik, Oracle, Cisco, dll) — 3_SDD.md 3.13.
// Berdiri sendiri tanpa relasi ke users; hanya menampilkan info, bukan transaksi pendaftaran.
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
    ];
}
