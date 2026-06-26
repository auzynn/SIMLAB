<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'no_telp', 'google_id', 'avatar', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Atribut turunan yang ikut diserialisasi ke frontend.
     *
     * @var list<string>
     */
    protected $appends = ['has_password'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Penanda apakah user sudah mengatur password (login manual aktif).
     * Kolom `password` di-hidden dari response, jadi frontend memakai flag ini
     * untuk memilih form "Atur Password" vs "Ubah Password" (3_SDD.md 2.1).
     */
    protected function hasPassword(): Attribute
    {
        return Attribute::make(get: fn () => ! is_null($this->password));
    }

    /**
     * Profil dosen (jika role dosen). Diisi otomatis saat registrasi — lihat 3_SDD.md 3.2.
     */
    public function dosen(): HasOne
    {
        return $this->hasOne(Dosen::class);
    }

    /**
     * Profil mahasiswa (jika role mahasiswa). Diisi otomatis saat registrasi — lihat 3_SDD.md 3.3.
     */
    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class);
    }
}
