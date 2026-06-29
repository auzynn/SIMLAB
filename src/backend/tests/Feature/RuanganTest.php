<?php

namespace Tests\Feature;

use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Data master ruangan — read terbuka untuk semua role login,
 * CUD hanya Admin/Supervisor (Gate manage-master-data; 3_SDD.md 3.4, 5.5).
 */
class RuanganTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_dapat_melihat_daftar_ruangan(): void
    {
        Ruangan::create(['nama_ruangan' => 'Lab A', 'kapasitas' => 30, 'status' => 'tersedia']);
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->getJson('/api/ruangan')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_ditolak_mengakses_ruangan(): void
    {
        $this->getJson('/api/ruangan')->assertUnauthorized();
    }

    public function test_admin_dapat_menambah_ruangan(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/ruangan', [
            'nama_ruangan' => 'Lab Jaringan',
            'kapasitas' => 40,
            'status' => 'tersedia',
        ])->assertCreated();

        $this->assertDatabaseHas('ruangan', ['nama_ruangan' => 'Lab Jaringan', 'status' => 'tersedia']);
    }

    public function test_supervisor_dapat_menambah_ruangan(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));

        $this->postJson('/api/ruangan', [
            'nama_ruangan' => 'Lab Forensik',
            'status' => 'tersedia',
        ])->assertCreated();
    }

    public function test_mahasiswa_tidak_dapat_menambah_ruangan(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/ruangan', [
            'nama_ruangan' => 'Lab Ilegal',
            'status' => 'tersedia',
        ])->assertForbidden();

        $this->assertDatabaseMissing('ruangan', ['nama_ruangan' => 'Lab Ilegal']);
    }

    public function test_status_diluar_enum_ditolak(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/ruangan', [
            'nama_ruangan' => 'Lab Salah',
            'status' => 'rusak',
        ])->assertStatus(422);
    }

    public function test_admin_dapat_mengubah_status_ruangan(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $ruangan = Ruangan::create(['nama_ruangan' => 'Lab A', 'status' => 'tersedia']);

        $this->patchJson("/api/ruangan/{$ruangan->id}", [
            'nama_ruangan' => 'Lab A',
            'status' => 'perbaikan',
        ])->assertOk();

        $this->assertDatabaseHas('ruangan', ['id' => $ruangan->id, 'status' => 'perbaikan']);
    }

    public function test_supervisor_dapat_menghapus_ruangan(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $ruangan = Ruangan::create(['nama_ruangan' => 'Lab A', 'status' => 'tersedia']);

        $this->deleteJson("/api/ruangan/{$ruangan->id}")->assertOk();

        $this->assertDatabaseMissing('ruangan', ['id' => $ruangan->id]);
    }
}
