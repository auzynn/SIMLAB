<?php

namespace Tests\Feature;

use App\Models\Sertifikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Katalog Sertifikasi (FASE 6, SRS UC-05, 3_SDD.md 3.13). Modul informasional:
 * read terbuka untuk semua role login, CUD hanya Admin/Supervisor (Gate manage-master-data).
 */
class SertifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function contoh(array $override = []): array
    {
        return array_merge([
            'nama_sertifikasi' => 'Mikrotik Certified Network Associate',
            'penyelenggara' => 'Mikrotik',
            'jadwal' => 'Batch berkala',
            'persyaratan' => 'Dasar jaringan TCP/IP',
            'tautan_pendaftaran' => 'https://mikrotik.com/training',
        ], $override);
    }

    public function test_mahasiswa_dapat_melihat_katalog_sertifikasi(): void
    {
        Sertifikasi::create($this->contoh());
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->getJson('/api/sertifikasi')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_ditolak_mengakses_katalog(): void
    {
        $this->getJson('/api/sertifikasi')->assertUnauthorized();
    }

    public function test_admin_dapat_menambah_sertifikasi(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/sertifikasi', $this->contoh())->assertCreated();

        $this->assertDatabaseHas('sertifikasi', ['nama_sertifikasi' => 'Mikrotik Certified Network Associate']);
    }

    public function test_supervisor_dapat_memperbarui_dan_menghapus_sertifikasi(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $s = Sertifikasi::create($this->contoh());

        $this->patchJson("/api/sertifikasi/{$s->id}", $this->contoh(['penyelenggara' => 'Mikrotik Indonesia']))
            ->assertOk();
        $this->assertDatabaseHas('sertifikasi', ['id' => $s->id, 'penyelenggara' => 'Mikrotik Indonesia']);

        $this->deleteJson("/api/sertifikasi/{$s->id}")->assertOk();
        $this->assertDatabaseMissing('sertifikasi', ['id' => $s->id]);
    }

    public function test_mahasiswa_tidak_dapat_membuat_mengubah_menghapus(): void
    {
        $s = Sertifikasi::create($this->contoh());
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/sertifikasi', $this->contoh(['nama_sertifikasi' => 'CCNA']))->assertForbidden();
        $this->patchJson("/api/sertifikasi/{$s->id}", $this->contoh(['penyelenggara' => 'X']))->assertForbidden();
        $this->deleteJson("/api/sertifikasi/{$s->id}")->assertForbidden();

        $this->assertDatabaseMissing('sertifikasi', ['nama_sertifikasi' => 'CCNA']);
        $this->assertDatabaseHas('sertifikasi', ['id' => $s->id]);
    }

    public function test_nama_dan_penyelenggara_wajib_diisi(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/sertifikasi', ['jadwal' => 'kapan saja'])->assertStatus(422);
    }
}
