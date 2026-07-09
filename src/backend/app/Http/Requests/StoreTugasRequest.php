<?php

namespace App\Http\Requests;

use App\Models\KelasLabPeserta;
use App\Models\Tugas;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi kirim Tugas (mahasiswa). Tugas hanya boleh dikirim untuk Kelas Lab
 * yang diikuti mahasiswa dengan status peserta 'disetujui'.
 */
class StoreTugasRequest extends FormRequest
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
            'kelas_lab_id' => ['required', 'integer', 'exists:kelas_lab,id'],
            'pertemuan' => ['required', 'integer', 'between:1,16'],
            'judul' => ['required', 'string', 'max:255'],
            'tautan' => ['required', 'url', 'max:2048'],
        ];
    }

    /**
     * Mahasiswa harus peserta terdaftar (disetujui) pada sesi Kelas Lab yang dituju.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $mahasiswa = $this->user()->mahasiswa;
            if (! $mahasiswa) {
                $validator->errors()->add('kelas_lab_id', 'Profil mahasiswa tidak ditemukan.');

                return;
            }

            $peserta = KelasLabPeserta::where('kelas_lab_id', $this->input('kelas_lab_id'))
                ->where('mahasiswa_id', $mahasiswa->id)
                ->where('status', 'disetujui')
                ->exists();

            if (! $peserta) {
                $validator->errors()->add('kelas_lab_id', 'Anda bukan peserta terdaftar (disetujui) pada Kelas Lab ini.');

                return;
            }

            // Satu tugas per pertemuan per mahasiswa pada kelas yang sama.
            $sudahAda = Tugas::where('kelas_lab_id', $this->input('kelas_lab_id'))
                ->where('mahasiswa_id', $mahasiswa->id)
                ->where('pertemuan', $this->input('pertemuan'))
                ->exists();

            if ($sudahAda) {
                $validator->errors()->add('pertemuan', 'Anda sudah mengirim tugas untuk pertemuan ini. Hapus tugas lama bila ingin mengganti.');
            }
        });
    }
}
