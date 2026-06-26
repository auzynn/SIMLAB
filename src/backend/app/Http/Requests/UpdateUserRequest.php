<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Validasi update data/role user oleh Admin (3_SDD.md 5.2).
 * Semua field opsional (`sometimes`) agar bisa update sebagian.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-users');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['sometimes', 'required', 'in:admin,supervisor,dosen,mahasiswa'],
            // Password opsional saat update; kosong = tidak diubah (ditangani controller)
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
