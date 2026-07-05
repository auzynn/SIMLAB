<?php

namespace App\Http\Requests;

use App\Models\Perangkat;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pengajuan peminjaman perangkat (SRS UC-03, 3_SDD.md 3.10).
 * Hanya Mahasiswa yang mengajukan; Admin/Supervisor menyetujui.
 */
class StorePeminjamanPerangkatRequest extends FormRequest
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
            'perangkat_id' => ['required', 'integer', 'exists:perangkat,id'],
            'tanggal_pinjam' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali_rencana' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
        ];
    }

    /**
     * Validasi bisnis: perangkat harus berstatus tersedia saat diajukan.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Lewati cek bisnis bila aturan dasar sudah gagal (data belum lengkap/valid)
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $perangkat = Perangkat::find($this->input('perangkat_id'));
            if ($perangkat && $perangkat->status !== 'tersedia') {
                $validator->errors()->add('perangkat_id', 'Perangkat tidak tersedia (status: '.$perangkat->status.').');
            }
        });
    }
}
