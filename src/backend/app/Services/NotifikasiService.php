<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;

/**
 * Pembuatan notifikasi in-app (SRS UC-07, 3_SDD.md 3.16).
 * Dipanggil DARI DALAM transaksi DB aksi pemicu — jika transaksi rollback,
 * insert notifikasi ikut batal (tidak ada notifikasi orphan).
 */
class NotifikasiService
{
    /**
     * Kirim satu notifikasi ke seorang user.
     */
    public function kirim(int $userId, string $judul, string $pesan, string $tipe, ?int $referensiId = null): void
    {
        Notifikasi::create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'referensi_id' => $referensiId,
        ]);
    }

    /**
     * Kirim ke semua Admin & Supervisor (dipakai saat ada pengajuan baru yang menunggu).
     */
    public function kirimKeApprover(string $judul, string $pesan, string $tipe, ?int $referensiId = null): void
    {
        $this->kirimKeRole(['admin', 'supervisor'], $judul, $pesan, $tipe, $referensiId);
    }

    /**
     * Kirim ke semua user dengan salah satu role tertentu.
     *
     * @param  array<int, string>  $roles
     */
    public function kirimKeRole(array $roles, string $judul, string $pesan, string $tipe, ?int $referensiId = null): void
    {
        User::whereIn('role', $roles)
            ->pluck('id')
            ->each(fn (int $id) => $this->kirim($id, $judul, $pesan, $tipe, $referensiId));
    }
}
