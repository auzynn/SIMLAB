<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Kelola data user & role — khusus Admin (3_SDD.md 5.2, 2_SRS.md Bagian 1).
 */
class UserController extends Controller
{
    /**
     * List user, opsional filter ?role=.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('manage-users');

        $users = User::query()
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->latest()
            ->get();

        return response()->json([
            'data' => $users,
            'message' => 'Berhasil mengambil data user.',
        ]);
    }

    /**
     * Buat user manual (Admin/Supervisor/Dosen) dengan credential awal.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            // Password di-hash otomatis via cast 'hashed' pada model User
            $user = User::create($request->validated());

            // Jaga invarian 3_SDD.md 3.2: entri dosen selalu lahir bersama akun dosen
            if ($user->role === 'dosen') {
                Dosen::create(['user_id' => $user->id]);
            }

            return $user;
        });

        return response()->json([
            'data' => $user,
            'message' => 'User berhasil dibuat.',
        ], 201);
    }

    /**
     * Update data/role user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        // Field password kosong = tidak diubah; jangan timpa hash lama dengan null
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // ponytail: sinkronisasi profil saat role berubah ke/dari dosen belum ditangani —
        // tambahkan jika modul dosen sudah aktif dan kasus ubah-role lintas profil muncul.
        $user->update($data);

        return response()->json([
            'data' => $user,
            'message' => 'User berhasil diperbarui.',
        ]);
    }

    /**
     * Hapus user. FK cascade ikut menghapus profil dosen/mahasiswa terkait.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        Gate::authorize('manage-users');

        // Cegah admin menghapus akunnya sendiri agar tidak terkunci dari sistem
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }
}
