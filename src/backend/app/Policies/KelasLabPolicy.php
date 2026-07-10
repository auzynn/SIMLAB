<?php

namespace App\Policies;

use App\Models\KelasLab;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Otorisasi Kelas Lab/Praktikum (2_SRS.md Bagian 1, UC-02a; 3_SDD.md 5.7).
 *
 * - Membuka kelas: Dosen (untuk dirinya), Supervisor (atas permintaan Dosen), atau Admin
 *   (hak akses penuh — menunjuk dosen pengampu). Mahasiswa hanya mendaftar (lihat Gate
 *   `daftar-kelas-lab`).
 * - Ubah/hapus: Admin & Supervisor (semua kelas) atau pemilik (`dosen_id`).
 */
class KelasLabPolicy
{
    /**
     * Buka kelas baru — Admin, Dosen, atau Supervisor (Mahasiswa ditolak → 403).
     * Admin/Supervisor wajib menunjuk dosen pengampu; Dosen memakai dirinya sendiri.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'dosen', 'supervisor'], true);
    }

    /**
     * Ubah jadwal/kuota — Admin & Supervisor (semua kelas) atau pemilik (dosen pengampu).
     */
    public function update(User $user, KelasLab $kelasLab): bool
    {
        return in_array($user->role, ['admin', 'supervisor'], true)
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
     * Buka detail sesi — staf (Admin/Supervisor/Dosen) bebas; Mahasiswa hanya bila
     * pendaftarannya sudah DISETUJUI (yang masih menunggu/ditolak/belum daftar → 403).
     */
    public function view(User $user, KelasLab $kelasLab): Response
    {
        if ($user->role !== 'mahasiswa') {
            return Response::allow();
        }

        $disetujui = $kelasLab->peserta()
            ->where('mahasiswa_id', $user->mahasiswa?->id)
            ->where('status', 'disetujui')
            ->exists();

        return $disetujui
            ? Response::allow()
            : Response::deny('Anda hanya dapat membuka detail kelas setelah pendaftaran disetujui.');
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
