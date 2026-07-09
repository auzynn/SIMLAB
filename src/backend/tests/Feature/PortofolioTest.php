<?php

namespace Tests\Feature;

use App\Models\Portofolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Portofolio Mahasiswa (FASE 7, PRD 3.7, 3_SDD.md 3.14). Read terbuka untuk semua role login;
 * CUD hanya pemilik (Mahasiswa) — Store/UpdatePortofolioRequest + cek kepemilikan di destroy.
 */
class PortofolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_role_login_dapat_melihat_portofolio(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        Portofolio::create(['user_id' => $mhs->id, 'judul' => 'Proyek IDS']);

        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));

        $this->getJson('/api/portofolio')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_ditolak_mengakses_portofolio(): void
    {
        $this->getJson('/api/portofolio')->assertUnauthorized();
    }

    public function test_mahasiswa_dapat_menambah_portofolio_miliknya(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        Sanctum::actingAs($mhs);

        $this->postJson('/api/portofolio', [
            'judul' => 'Sistem Deteksi Intrusi',
            'deskripsi' => 'Riset ML untuk IDS',
            'tautan' => 'https://github.com/contoh/ids',
            'tanggal' => '2026-05-01',
        ])->assertCreated();

        // user_id di-set dari user login, bukan input.
        $this->assertDatabaseHas('portofolio', [
            'judul' => 'Sistem Deteksi Intrusi',
            'user_id' => $mhs->id,
        ]);
    }

    public function test_dosen_tidak_dapat_menambah_portofolio(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));

        $this->postJson('/api/portofolio', ['judul' => 'Milik Dosen'])->assertForbidden();
        $this->assertDatabaseMissing('portofolio', ['judul' => 'Milik Dosen']);
    }

    public function test_judul_wajib_diisi(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/portofolio', ['deskripsi' => 'tanpa judul'])->assertStatus(422);
    }

    public function test_pemilik_dapat_mengubah_dan_menghapus_portofolionya(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $p = Portofolio::create(['user_id' => $mhs->id, 'judul' => 'Judul Awal']);
        Sanctum::actingAs($mhs);

        $this->patchJson("/api/portofolio/{$p->id}", ['judul' => 'Judul Baru'])->assertOk();
        $this->assertDatabaseHas('portofolio', ['id' => $p->id, 'judul' => 'Judul Baru']);

        $this->deleteJson("/api/portofolio/{$p->id}")->assertOk();
        $this->assertDatabaseMissing('portofolio', ['id' => $p->id]);
    }

    public function test_mahasiswa_tidak_dapat_mengubah_portofolio_milik_mahasiswa_lain(): void
    {
        $pemilik = User::factory()->create(['role' => 'mahasiswa']);
        $penyusup = User::factory()->create(['role' => 'mahasiswa']);
        $p = Portofolio::create(['user_id' => $pemilik->id, 'judul' => 'Milik Pemilik']);

        Sanctum::actingAs($penyusup);

        $this->patchJson("/api/portofolio/{$p->id}", ['judul' => 'Dibajak'])->assertForbidden();
        $this->deleteJson("/api/portofolio/{$p->id}")->assertForbidden();

        $this->assertDatabaseHas('portofolio', ['id' => $p->id, 'judul' => 'Milik Pemilik']);
    }
}
