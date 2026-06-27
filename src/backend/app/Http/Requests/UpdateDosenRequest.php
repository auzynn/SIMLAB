<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi update profil dosen (3_SDD.md 5.3). Otorisasi (pemilik atau
 * Admin/Supervisor) lewat DosenPolicy. Semua field opsional (`sometimes`).
 *
 * `name` & `no_telp` adalah kolom `users` (akun pemilik); sisanya kolom `dosen`.
 */
class UpdateDosenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('dosen')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Kolom akun (users)
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'no_telp' => ['sometimes', 'nullable', 'string', 'max:32'],

            // Kolom profil dosen
            'nidn' => ['sometimes', 'nullable', 'string', 'max:32'],
            'jenis_kelamin' => ['sometimes', 'nullable', 'in:Laki-laki,Perempuan'],
            'jabatan_fungsional' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tempat_lahir' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['sometimes', 'nullable', 'date'],
            'biografi' => ['sometimes', 'nullable', 'string'],
            'roadmap_riset' => ['sometimes', 'nullable', 'string'],
            'publikasi' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
