<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validasi & otorisasi akses Rekap Tugas Kelas Lab (2_SRS.md UC-06, 3_SDD.md 5.15).
 * Akses: Admin/Supervisor/Dosen (Gate view-rekap-tugas). Dosen di-scope di service.
 */
class RekapTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('view-rekap-tugas');
    }

    public function rules(): array
    {
        return [
            // Filter opsional: batasi ke satu kelas tertentu.
            'kelas_lab_id' => ['nullable', 'integer', 'exists:kelas_lab,id'],
        ];
    }
}
