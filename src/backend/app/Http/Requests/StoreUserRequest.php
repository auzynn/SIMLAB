<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validasi pembuatan user manual oleh Admin (3_SDD.md 5.2).
 */
class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // Mahasiswa lahir otomatis lewat Google OAuth, jadi pembuatan manual hanya 3 role ini (3_SDD.md 5.2)
            'role' => ['required', 'in:admin,supervisor,dosen'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
