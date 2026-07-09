<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Jadwal Kelas Lab/Praktikum — satu sesi terjadwal mingguan satu semester (3_SDD.md 3.7).
class KelasLab extends Model
{
    protected $table = 'kelas_lab';

    protected $fillable = [
        'mata_kuliah_id',
        'dosen_id',
        'ruangan_id',
        'dibuat_oleh',
        'nama_sesi',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'tanggal_mulai_semester',
        'tanggal_selesai_semester',
        'kuota',
        'tautan_pengumpulan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai_semester' => 'date:Y-m-d',
            'tanggal_selesai_semester' => 'date:Y-m-d',
            'kuota' => 'integer',
        ];
    }

    /**
     * Sisa kuota = kuota - peserta yang mengisi slot (menunggu + disetujui; `ditolak` dilepas).
     * Memakai `peserta_count` jika sudah di-load via withCount (lihat constraint di controller).
     */
    protected function sisaKuota(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->kuota - ($this->peserta_count ?? $this->peserta()->where('status', '!=', 'ditolak')->count()),
        );
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    // User pembuat entri (Dosen sendiri atau Supervisor atas permintaan Dosen).
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(KelasLabPeserta::class);
    }

    // Deadline/tugas yang ditetapkan per pertemuan untuk kelas ini.
    public function deadlinePertemuan(): HasMany
    {
        return $this->hasMany(DeadlinePertemuan::class);
    }
}
