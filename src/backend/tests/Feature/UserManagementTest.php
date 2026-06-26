<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * RBAC kelola user: hanya Admin (2_SRS.md Bagian 1, 3_SDD.md 5.2).
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_melihat_daftar_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(2)->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_non_admin_ditolak_mengakses_kelola_user(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->getJson('/api/users')->assertForbidden();
    }

    public function test_admin_dapat_mengubah_role_user(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $target = User::factory()->create(['role' => 'mahasiswa']);

        $this->patchJson("/api/users/{$target->id}", ['role' => 'supervisor'])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'supervisor']);
    }

    public function test_admin_tidak_dapat_menghapus_akun_sendiri(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/users/{$admin->id}")->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_membuat_user_dosen_otomatis_membuat_profil_dosen(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/users', [
            'name' => 'Dosen Baru',
            'email' => 'dosen.baru@unsil.ac.id',
            'role' => 'dosen',
            'password' => 'password',
        ])->assertCreated();

        $user = User::where('email', 'dosen.baru@unsil.ac.id')->firstOrFail();
        $this->assertDatabaseHas('dosen', ['user_id' => $user->id]);
    }
}
