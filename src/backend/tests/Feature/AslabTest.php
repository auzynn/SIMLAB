<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Delegasi Aslab — Admin menetapkan mahasiswa jadi Supervisor (Gate manage-users).
 */
class AslabTest extends TestCase
{
    use RefreshDatabase;

    private function mahasiswa(string $npm): User
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        Mahasiswa::create(['user_id' => $user->id, 'npm' => $npm, 'angkatan' => '2022']);

        return $user;
    }

    public function test_admin_dapat_menetapkan_dan_mengembalikan_aslab(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $mhs = $this->mahasiswa('220100');

        $this->postJson("/api/aslab/{$mhs->id}")->assertOk();
        $this->assertDatabaseHas('users', ['id' => $mhs->id, 'role' => 'supervisor']);

        $this->deleteJson("/api/aslab/{$mhs->id}")->assertOk();
        $this->assertDatabaseHas('users', ['id' => $mhs->id, 'role' => 'mahasiswa']);
    }

    public function test_non_admin_ditolak(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));
        $mhs = $this->mahasiswa('220101');

        $this->postJson("/api/aslab/{$mhs->id}")->assertForbidden();
        $this->getJson('/api/aslab')->assertForbidden();
    }

    public function test_hanya_mahasiswa_yang_dapat_dijadikan_aslab(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->postJson("/api/aslab/{$dosen->id}")->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $dosen->id, 'role' => 'dosen']);
    }

    public function test_index_memisahkan_kandidat_dan_aslab(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->mahasiswa('220102');
        $aslab = $this->mahasiswa('220103');
        $aslab->update(['role' => 'supervisor']);

        $this->getJson('/api/aslab')
            ->assertOk()
            ->assertJsonCount(1, 'data.kandidat')
            ->assertJsonCount(1, 'data.aslab');
    }
}
