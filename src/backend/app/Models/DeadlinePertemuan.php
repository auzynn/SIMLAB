<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Deadline pengumpulan tugas untuk satu pertemuan sebuah Kelas Lab (ditetapkan Dosen/Supervisor).
class DeadlinePertemuan extends Model
{
    protected $table = 'deadline_pertemuan';

    protected $fillable = [
        'kelas_lab_id',
        'pertemuan',
        'materi',
        'deadline',
    ];

    protected function casts(): array
    {
        return [
            'pertemuan' => 'integer',
            'deadline' => 'datetime',
        ];
    }

    public function kelasLab(): BelongsTo
    {
        return $this->belongsTo(KelasLab::class);
    }
}
