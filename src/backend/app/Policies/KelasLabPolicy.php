<?php

namespace App\Policies;

use App\Models\KelasLab;
use App\Models\User;

/**
 * Otorisasi Kelas Lab/Praktikum (2_SRS.md Bagian 1, UC-02a; 3_SDD.md 5.7).
 *
 * - Membuka kelas: Dosen (untuk dirinya) atau Supervisor (atas permintaan Dosen).
 *   Admin **tidak** berwenang membuka kelas; Mahasiswa hanya mendaftar (lihat Gate
 *   `daftar-kelas-lab`).
 * - Ubah/hapus: pemilik (`dosen_id`) atau Supervisor.
 */
class KelasLabPolicy
{
    /**
     * Buka kelas baru — Dosen atau Supervisor (Admin & Mahasiswa ditolak → 403).
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['dosen', 'supervisor'], true);
    }

    /**
     * Ubah jadwal/kuota — pemilik (dosen pengampu) atau Supervisor.
     */
    public function update(User $user, KelasLab $kelasLab): bool
    {
        return $user->role === 'supervisor'
            || ($user->role === 'dosen' && $user->dosen?->id === $kelasLab->dosen_id);
    }

    /**
     * Hapus kelas — pemilik (dosen pengampu) atau Supervisor.
     */
    public function delete(User $user, KelasLab $kelasLab): bool
    {
        return $this->update($user, $kelasLab);
    }

    /**
     * Lihat peserta — pemilik kelas, Supervisor, atau Admin (3_SDD.md 5.7).
     */
    public function viewPeserta(User $user, KelasLab $kelasLab): bool
    {
        return in_array($user->role, ['admin', 'supervisor'], true)
            || ($user->role === 'dosen' && $user->dosen?->id === $kelasLab->dosen_id);
    }
}
