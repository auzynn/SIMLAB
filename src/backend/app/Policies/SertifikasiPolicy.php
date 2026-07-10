<?php

namespace App\Policies;

use App\Models\Sertifikasi;
use App\Models\User;

/**
 * Otorisasi Katalog Sertifikasi (2_SRS.md Bagian 1 revisi, UC-05; 3_SDD.md 5.13).
 *
 * - Read: terbuka semua role login (tidak lewat policy).
 * - Create: Admin, Supervisor, atau Dosen (dosen menambah referensi sendiri).
 * - Update/Delete: Admin & Supervisor (semua entri) atau Dosen pemilik (`created_by`).
 */
class SertifikasiPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'supervisor', 'dosen'], true);
    }

    public function update(User $user, Sertifikasi $sertifikasi): bool
    {
        return in_array($user->role, ['admin', 'supervisor'], true)
            || ($user->role === 'dosen' && $user->id === $sertifikasi->created_by);
    }

    public function delete(User $user, Sertifikasi $sertifikasi): bool
    {
        return $this->update($user, $sertifikasi);
    }
}
