<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi tambah portofolio — hanya Mahasiswa yang boleh membuat (2_SRS.md Bagian 1, 3_SDD.md 5.14).
 */
class StorePortofolioRequest extends FormRequest
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
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tautan' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
        ];
    }
}
