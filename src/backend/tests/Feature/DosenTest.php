<?php

namespace Tests\Feature;

use App\Models\BidangMinat;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Endpoint profil dosen — baca publik, update terbatas pemilik/Admin/Supervisor
 * (3_SDD.md 5.3, matriks RBAC 2_SRS.md Bagian 1). Mencakup T2.5, T2.6, T2.13.
 */
class DosenTest extends TestCase
{
    use RefreshDatabase;

    /** Buat akun dosen + profilnya, kembalikan [User, Dosen]. */
    private function buatDosen(array $dosenAttr = []): array
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::create(['user_id' => $user->id] + $dosenAttr);

        return [$user, $dosen];
    }

    public function test_daftar_dosen_dapat_diakses_publik_dengan_relasi_user(): void
    {
        $this->buatDosen(['nidn' => '111']);
        $this->buatDosen(['nidn' => '222']);

        $this->getJson('/api/dosen')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'nidn', 'user' => ['id', 'name', 'email']]]]);
    }

    public function test_detail_dosen_dapat_diakses_publik(): void
    {
        [, $dosen] = $this->buatDosen(['nidn' => '310127203', 'jabatan_fungsional' => 'Lektor']);

        $this->getJson("/api/dosen/{$dosen->id}")
            ->assertOk()
            ->assertJsonPath('data.nidn', '310127203')
            ->assertJsonPath('data.jabatan_fungsional', 'Lektor');
    }

    public function test_bidang_minat_dikembalikan_sebagai_relasi_master(): void
    {
        // Bidang Minat = relasi master many-to-many (bukan kolom free-text).
        // Endpoint dosen publik eager-load relasi → tampil sebagai array objek.
        [, $dosen] = $this->buatDosen();
        $bm = BidangMinat::create(['nama' => 'Digital Forensik']);
        $dosen->bidangMinat()->attach($bm->id);

        $this->getJson("/api/dosen/{$dosen->id}")
            ->assertOk()
            ->assertJsonPath('data.bidang_minat.0.nama', 'Digital Forensik');
    }

    public function test_dosen_dapat_memperbarui_profilnya_sendiri(): void
    {
        [$user, $dosen] = $this->buatDosen();
        Sanctum::actingAs($user);

        $this->patchJson("/api/dosen/{$dosen->id}", [
            'jabatan_fungsional' => 'Lektor Kepala',
            'biografi' => 'Biografi baru.',
        ])->assertOk();

        $this->assertDatabaseHas('dosen', [
            'id' => $dosen->id,
            'jabatan_fungsional' => 'Lektor Kepala',
            'biografi' => 'Biografi baru.',
        ]);
    }

    public function test_admin_dapat_memperbarui_profil_dosen_lain(): void
    {
        [, $dosen] = $this->buatDosen();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->patchJson("/api/dosen/{$dosen->id}", ['nidn' => '999'])->assertOk();

        $this->assertDatabaseHas('dosen', ['id' => $dosen->id, 'nidn' => '999']);
    }

    public function test_update_name_menulis_ke_akun_user_pemilik(): void
    {
        [$user, $dosen] = $this->buatDosen();
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));

        $this->patchJson("/api/dosen/{$dosen->id}", ['name' => 'Nama Terkoreksi'])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Terkoreksi']);
    }

    public function test_dosen_dapat_mengedit_jabatan_dan_ttl_lewat_profil(): void
    {
        [$user, $dosen] = $this->buatDosen();
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/profile', [
            'jabatan_fungsional' => 'Lektor Kepala',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1980-05-17',
        ])->assertOk();

        $this->assertDatabaseHas('dosen', [
            'id' => $dosen->id,
            'jabatan_fungsional' => 'Lektor Kepala',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1980-05-17',
        ]);
    }

    public function test_dosen_lain_tidak_dapat_memperbarui_profil_bukan_miliknya(): void
    {
        [, $dosen] = $this->buatDosen();
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));

        $this->patchJson("/api/dosen/{$dosen->id}", ['nidn' => '000'])->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_memperbarui_profil_dosen(): void
    {
        [, $dosen] = $this->buatDosen();
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->patchJson("/api/dosen/{$dosen->id}", ['nidn' => '000'])->assertForbidden();
    }

    public function test_update_profil_dosen_butuh_login(): void
    {
        [, $dosen] = $this->buatDosen();

        $this->patchJson("/api/dosen/{$dosen->id}", ['nidn' => '000'])->assertUnauthorized();
    }
}
