<?php

namespace App\Http\Requests;

use App\Models\Portofolio;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi ubah portofolio — hanya pemilik (Mahasiswa) yang boleh mengubah miliknya
 * (2_SRS.md Bagian 1: "milik sendiri"; 3_SDD.md 5.14).
 */
class UpdatePortofolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $portofolio = $this->route('portofolio');

        return $portofolio instanceof Portofolio
            && $this->user()?->id === $portofolio->user_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tautan' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
        ];
    }
}
