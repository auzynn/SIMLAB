<?php

namespace App\Http\Requests;

use App\Models\Ruangan;
use App\Services\JadwalRuanganService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pengajuan peminjaman ruangan (SRS UC-02, 3_SDD.md 3.5).
 * Hanya Mahasiswa yang mengajukan; Admin/Supervisor menyetujui, Dosen tidak meminjam ruangan.
 */
class StorePeminjamanRuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'mahasiswa';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ruangan_id' => ['required', 'integer', 'exists:ruangan,id'],
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam_mulai' => ['required', 'date_format:H:i', 'after_or_equal:07:00'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai', 'before_or_equal:17:00'],
            'keperluan' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * Validasi bisnis (UC-02): status ruangan & bentrok terhadap peminjaman disetujui + kelas_lab.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Lewati cek bisnis bila aturan dasar sudah gagal (data belum lengkap/valid)
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ruangan = Ruangan::find($this->input('ruangan_id'));
            if ($ruangan && $ruangan->status !== 'tersedia') {
                $validator->errors()->add('ruangan_id', 'Ruangan tidak tersedia (status: '.$ruangan->status.').');

                return;
            }

            $bentrok = app(JadwalRuanganService::class)->peminjamanBentrok(
                (int) $this->input('ruangan_id'),
                $this->input('tanggal'),
                $this->input('jam_mulai'),
                $this->input('jam_selesai'),
            );

            if ($bentrok) {
                $validator->errors()->add('jam_mulai', 'Kuota ruangan pada slot ini sudah penuh atau bentrok dengan Kelas Lab pada waktu tersebut.');
            }
        });
    }
}
