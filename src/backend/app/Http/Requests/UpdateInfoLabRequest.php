<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validasi update konten info lab oleh Admin/Supervisor (Gate manage-info-lab;
 * 3_SDD.md 5.12, 2_SRS.md Bagian 1 revisi).
 */
class UpdateInfoLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-info-lab');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul' => ['nullable', 'string', 'max:255'],
            'konten' => ['required', 'string'],
            'gambar' => ['nullable', 'string', 'max:255'],
            // Khusus tipe kepala_lab: tautan ke entri dosen untuk kartu identitas.
            'dosen_id' => ['nullable', 'integer', 'exists:dosen,id'],
        ];
    }
}
