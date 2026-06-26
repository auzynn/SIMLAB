<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login manual (email + password). Mengembalikan Sanctum token + data user.
     * Lihat alur di 3_SDD.md Bagian 2.1.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Email tidak ditemukan → pesan umum (tidak membocorkan keberadaan email)
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Akun belum pernah set password (lahir lewat Google OAuth) → tolak eksplisit
        if (is_null($user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini belum mengaktifkan login manual. Silakan login dengan Google UNSIL, lalu atur password di halaman Profil.'],
            ]);
        }

        // Password tidak cocok → pesan umum
        if (! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Data user yang sedang login (berdasarkan Bearer token aktif).
     */
    public function me(Request $request): JsonResponse
    {
        // Muat profil sesuai role agar halaman Profil bisa menampilkan data diri.
        // Dosen: ikut sertakan relasi bidangRiset agar Edit Profil bisa pre-fill pilihan.
        return response()->json(
            $request->user()->load(['dosen.bidangRiset', 'mahasiswa']),
        );
    }

    /**
     * Logout: hapus token Sanctum yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * Atur password pertama kali (akun lahir lewat Google OAuth, password masih NULL).
     * Tidak butuh password lama. Lihat 3_SDD.md 2.1 (alur set password pertama kali).
     */
    public function setPassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Sudah punya password → arahkan ke ubah password, bukan set ulang
        if (! is_null($user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password sudah pernah diatur. Gunakan menu Ubah Password.'],
            ]);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Cast 'hashed' pada model meng-hash otomatis saat disimpan.
        $user->update(['password' => $validated['password']]);

        return response()->json([
            'message' => 'Password berhasil diatur. Anda kini bisa login manual dengan email & password.',
        ]);
    }

    /**
     * Ubah password yang sudah ada — wajib menyertakan password lama yang cocok.
     * Lihat 3_SDD.md 2.1 (alur ganti password).
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Belum punya password → harus set dulu lewat set-password
        if (is_null($user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Akun belum memiliki password. Gunakan menu Atur Password terlebih dahulu.'],
            ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak cocok.'],
            ]);
        }

        $user->update(['password' => $validated['password']]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    /**
     * Unggah/ganti foto avatar milik akun sendiri. Disimpan di disk publik
     * (storage/app/public/avatars), kolom `avatar` menyimpan URL absolut.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            // maks 2MB, tipe gambar umum
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Hapus avatar lama HANYA jika file lokal kita (avatar Google berupa URL eksternal).
        $this->deleteLocalAvatar($user->avatar);

        // Nama file acak agar tak bentrok & tak bocorkan info
        $ext = $request->file('avatar')->extension();
        $path = $request->file('avatar')->storeAs('avatars', Str::uuid().'.'.$ext, 'public');

        // Simpan URL absolut (disk publik sudah mengembalikan APP_URL/storage/...),
        // mengikuti pola avatar Google yang juga berupa URL penuh.
        $user->update(['avatar' => Storage::disk('public')->url($path)]);

        return response()->json([
            'avatar' => $user->avatar,
            'message' => 'Foto profil berhasil diperbarui.',
        ]);
    }

    /**
     * Edit profil akun sendiri. Email & role tidak boleh diubah (aturan SDD 3.1):
     * - Email immutable (acuan identitas + alur Google OAuth)
     * - Role hanya diubah Admin lewat Kelola User
     *
     * Field umum (semua role): name, no_telp.
     * Khusus dosen: nidn, bidang_riset_ids[] (banyak-banyak via dosen_bidang_riset).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'no_telp' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];

        if ($user->role === 'dosen') {
            $rules['nidn'] = ['sometimes', 'nullable', 'string', 'max:32'];
            $rules['bidang_riset_ids'] = ['sometimes', 'array'];
            $rules['bidang_riset_ids.*'] = ['integer', 'exists:bidang_riset,id'];
        }

        if ($user->role === 'mahasiswa') {
            // Prodi tunggal "Informatika" (whitelist) — NPM & angkatan immutable (SDD 3.3)
            $rules['prodi'] = ['sometimes', 'nullable', 'in:Informatika'];
        }

        $data = $request->validate($rules);

        // Update kolom users (name, no_telp)
        $userFields = array_intersect_key($data, array_flip(['name', 'no_telp']));
        if (! empty($userFields)) {
            $user->update($userFields);
        }

        // Update kolom dosen + sinkronisasi relasi bidang_riset
        if ($user->role === 'dosen') {
            $dosen = $user->dosen()->firstOrCreate([]);

            if (array_key_exists('nidn', $data)) {
                $dosen->update(['nidn' => $data['nidn']]);
            }
            if (array_key_exists('bidang_riset_ids', $data)) {
                $dosen->bidangRiset()->sync($data['bidang_riset_ids']);
            }
        }

        // Update prodi mahasiswa (NPM & angkatan tetap immutable; tak diterima di rules).
        if ($user->role === 'mahasiswa' && array_key_exists('prodi', $data) && $user->mahasiswa) {
            $user->mahasiswa->update(['prodi' => $data['prodi']]);
        }

        return response()->json([
            'data' => $user->fresh()->load(['dosen.bidangRiset', 'mahasiswa']),
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }

    /**
     * Hapus file avatar lama dari disk publik bila URL menunjuk ke storage kita.
     * Avatar eksternal (mis. Google `lh3.googleusercontent.com`) dibiarkan.
     */
    private function deleteLocalAvatar(?string $avatarUrl): void
    {
        if (! $avatarUrl) {
            return;
        }

        $marker = '/storage/avatars/';
        $pos = strpos($avatarUrl, $marker);
        if ($pos === false) {
            return;
        }

        $relative = 'avatars/'.basename($avatarUrl);
        Storage::disk('public')->delete($relative);
    }
}
