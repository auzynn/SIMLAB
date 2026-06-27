<?php

namespace App\Policies;

use App\Models\Dosen;
use App\Models\User;

/**
 * Otorisasi aksi terhadap profil dosen (3_SDD.md 5.3, matriks RBAC 2_SRS.md Bagian 1).
 */
class DosenPolicy
{
    /**
     * Update profil dosen: hanya pemilik (Dosen ybs.) atau Admin/Supervisor.
     */
    public function update(User $user, Dosen $dosen): bool
    {
        return $user->id === $dosen->user_id
            || in_array($user->role, ['admin', 'supervisor'], true);
    }
}
