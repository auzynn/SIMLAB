<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validasi & otorisasi akses Laporan (SRS UC-06, 3_SDD.md 5.13).
 * Akses: Admin/Supervisor (Gate view-report). Parameter from/to opsional (default 30 hari).
 */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('view-report');
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
