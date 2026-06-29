<?php

namespace Tests\Feature;

use App\Models\MataKuliah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Data master mata kuliah — read terbuka untuk semua role login (dipakai Dosen saat
 * membuka Kelas Lab), CUD hanya Admin/Supervisor (Gate manage-master-data; 3_SDD.md 3.6, 5.6).
 */
class MataKuliahTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_dapat_melihat_daftar_mata_kuliah(): void
    {
        MataKuliah::create(['kode_mk' => 'JKF301', 'nama_mk' => 'Praktikum Jaringan', 'sks' => 3]);
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));

        $this->getJson('/api/mata-kuliah')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_ditolak_mengakses_mata_kuliah(): void
    {
        $this->getJson('/api/mata-kuliah')->assertUnauthorized();
    }

    public function test_admin_dapat_menambah_mata_kuliah(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/mata-kuliah', [
            'kode_mk' => 'JKF301',
            'nama_mk' => 'Praktikum Jaringan Komputer',
            'sks' => 3,
        ])->assertCreated();

        $this->assertDatabaseHas('mata_kuliah', ['kode_mk' => 'JKF301', 'nama_mk' => 'Praktikum Jaringan Komputer']);
    }

    public function test_dosen_tidak_dapat_menambah_mata_kuliah(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));

        $this->postJson('/api/mata-kuliah', [
            'nama_mk' => 'Praktikum Ilegal',
        ])->assertForbidden();

        $this->assertDatabaseMissing('mata_kuliah', ['nama_mk' => 'Praktikum Ilegal']);
    }

    public function test_kode_mk_wajib_unik(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        MataKuliah::create(['kode_mk' => 'JKF301', 'nama_mk' => 'Praktikum Jaringan']);

        $this->postJson('/api/mata-kuliah', [
            'kode_mk' => 'JKF301',
            'nama_mk' => 'Mata Kuliah Lain',
        ])->assertStatus(422);
    }

    public function test_nama_mk_wajib_diisi(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/mata-kuliah', [
            'kode_mk' => 'JKF999',
        ])->assertStatus(422);
    }

    public function test_supervisor_dapat_memperbarui_dan_menghapus_mata_kuliah(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $mk = MataKuliah::create(['kode_mk' => 'JKF301', 'nama_mk' => 'Praktikum Jaringan', 'sks' => 3]);

        $this->patchJson("/api/mata-kuliah/{$mk->id}", [
            'kode_mk' => 'JKF301',
            'nama_mk' => 'Praktikum Jaringan Komputer',
            'sks' => 4,
        ])->assertOk();

        $this->assertDatabaseHas('mata_kuliah', ['id' => $mk->id, 'sks' => 4]);

        $this->deleteJson("/api/mata-kuliah/{$mk->id}")->assertOk();
        $this->assertDatabaseMissing('mata_kuliah', ['id' => $mk->id]);
    }
}
