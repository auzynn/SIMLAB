<?php

namespace Tests\Feature;

use App\Models\InfoLab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Konten info lab: baca publik, kelola konten Admin & Supervisor
 * (Gate manage-info-lab, 2_SRS.md Bagian 1 revisi, 3_SDD.md 5.12).
 */
class InfoLabTest extends TestCase
{
    use RefreshDatabase;

    public function test_konten_dapat_dibaca_publik_tanpa_login(): void
    {
        InfoLab::create(['tipe' => 'beranda', 'judul' => 'Beranda', 'konten' => 'Halo']);

        $this->getJson('/api/info-lab/beranda')
            ->assertOk()
            ->assertJsonPath('data.konten', 'Halo');
    }

    public function test_admin_dapat_memperbarui_konten(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->patchJson('/api/info-lab/visi_misi', ['konten' => 'Visi baru'])
            ->assertOk();

        $this->assertDatabaseHas('info_lab', [
            'tipe' => 'visi_misi',
            'konten' => 'Visi baru',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_supervisor_dapat_memperbarui_konten(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        Sanctum::actingAs($supervisor);

        $this->patchJson('/api/info-lab/beranda', ['konten' => 'Konten oleh Aslab'])
            ->assertOk();

        $this->assertDatabaseHas('info_lab', [
            'tipe' => 'beranda',
            'konten' => 'Konten oleh Aslab',
            'updated_by' => $supervisor->id,
        ]);
    }

    public function test_dosen_dan_mahasiswa_ditolak_memperbarui_konten(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));
        $this->patchJson('/api/info-lab/beranda', ['konten' => 'x'])->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->patchJson('/api/info-lab/beranda', ['konten' => 'x'])->assertForbidden();
    }

    public function test_tipe_tidak_valid_ditolak(): void
    {
        $this->getJson('/api/info-lab/tidakvalid')->assertNotFound();
    }
}
