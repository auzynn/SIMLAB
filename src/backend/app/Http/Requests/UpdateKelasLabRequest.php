<?php

namespace App\Http\Requests;

use App\Models\Ruangan;
use App\Services\JadwalRuanganService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi update jadwal/kuota Kelas Lab (SRS UC-02a, 3_SDD.md 5.7).
 * Otorisasi via KelasLabPolicy::update (pemilik dosen atau Supervisor).
 * `dosen_id` tidak dapat diubah lewat endpoint ini (kepemilikan tetap).
 */
class UpdateKelasLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('kelasLab'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mata_kuliah_id' => ['required', 'integer', 'exists:mata_kuliah,id'],
            'ruangan_id' => ['required', 'integer', 'exists:ruangan,id'],
            'nama_sesi' => ['required', 'string', 'max:255'],
            'hari' => ['required', Rule::in(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'])],
            'jam_mulai' => ['required', 'date_format:H:i', 'after_or_equal:07:00'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai', 'before_or_equal:17:00'],
            'tanggal_mulai_semester' => ['required', 'date'],
            'tanggal_selesai_semester' => ['required', 'date', 'after_or_equal:tanggal_mulai_semester'],
            'kuota' => ['required', 'integer', 'min:1', 'max:40'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ruangan = Ruangan::find($this->input('ruangan_id'));
            if ($ruangan && $ruangan->status !== 'tersedia') {
                $validator->errors()->add('ruangan_id', 'Ruangan tidak tersedia (status: '.$ruangan->status.').');

                return;
            }

            // Abaikan kelas ini sendiri saat cek bentrok (kondisi normal saat edit)
            $bentrok = app(JadwalRuanganService::class)->kelasBentrok(
                (int) $this->input('ruangan_id'),
                $this->input('hari'),
                $this->input('jam_mulai'),
                $this->input('jam_selesai'),
                $this->input('tanggal_mulai_semester'),
                $this->input('tanggal_selesai_semester'),
                $this->route('kelasLab')->id,
            );

            if ($bentrok) {
                $validator->errors()->add('jam_mulai', 'Jadwal bentrok dengan Kelas Lab lain atau peminjaman disetujui pada ruangan & waktu tersebut.');
            }
        });
    }
}
