<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use App\Models\PeminjamanPerangkat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Data master perangkat lab (3_SDD.md 3.9, 5.9).
 */
class PerangkatTest extends TestCase
{
    use RefreshDatabase;

    private function perangkat(string $status = 'tersedia'): Perangkat
    {
        return Perangkat::create([
            'nama_perangkat' => 'Router Mikrotik',
            'nomor_seri' => 'SN-'.uniqid(),
            'kategori' => 'Router',
            'status' => $status,
        ]);
    }

    public function test_semua_role_login_dapat_melihat_daftar_perangkat(): void
    {
        $this->perangkat();
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->getJson('/api/perangkat')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_supervisor_dapat_menambah_perangkat(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));

        $this->postJson('/api/perangkat', [
            'nama_perangkat' => 'IoT Kit A',
            'nomor_seri' => 'IOT-001',
            'kategori' => 'IoT Kit',
            'status' => 'tersedia',
        ])->assertCreated();

        $this->assertDatabaseHas('perangkat', ['nomor_seri' => 'IOT-001']);
    }

    public function test_mahasiswa_tidak_dapat_menambah_perangkat(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/perangkat', [
            'nama_perangkat' => 'IoT Kit A',
            'nomor_seri' => 'IOT-002',
            'status' => 'tersedia',
        ])->assertForbidden();

        $this->assertDatabaseCount('perangkat', 0);
    }

    public function test_nomor_seri_wajib_unik(): void
    {
        $this->perangkat();
        $existing = Perangkat::first();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/perangkat', [
            'nama_perangkat' => 'Duplikat',
            'nomor_seri' => $existing->nomor_seri,
            'status' => 'tersedia',
        ])->assertStatus(422);
    }

    public function test_perangkat_berstatus_dipinjam_tidak_dapat_dihapus(): void
    {
        $perangkat = $this->perangkat('dipinjam');
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));

        $this->deleteJson("/api/perangkat/{$perangkat->id}")->assertStatus(422);
        $this->assertDatabaseHas('perangkat', ['id' => $perangkat->id]);
    }

    public function test_perangkat_dengan_peminjaman_aktif_tidak_dapat_dihapus(): void
    {
        $perangkat = $this->perangkat();
        PeminjamanPerangkat::create([
            'perangkat_id' => $perangkat->id,
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'tanggal_pinjam' => Carbon::today()->toDateString(),
            'tanggal_kembali_rencana' => Carbon::today()->addDays(3)->toDateString(),
            'status' => 'menunggu',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->deleteJson("/api/perangkat/{$perangkat->id}")->assertStatus(422);
    }

    public function test_perangkat_tersedia_tanpa_peminjaman_dapat_dihapus(): void
    {
        $perangkat = $this->perangkat();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->deleteJson("/api/perangkat/{$perangkat->id}")->assertOk();
        $this->assertDatabaseMissing('perangkat', ['id' => $perangkat->id]);
    }
}
