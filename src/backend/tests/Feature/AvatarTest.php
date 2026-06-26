<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Unggah avatar oleh akun sendiri (Profil Saya) — POST /api/auth/avatar.
 */
class AvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dapat_mengunggah_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar' => null]);
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/auth/avatar', [
            'avatar' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ]);

        $res->assertOk()->assertJsonStructure(['avatar', 'message']);

        // Tepat satu file tersimpan di folder avatars
        $this->assertCount(1, Storage::disk('public')->files('avatars'));
        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_file_non_gambar_ditolak(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/auth/avatar', [
            'avatar' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_unggah_avatar_butuh_login(): void
    {
        $this->postJson('/api/auth/avatar', [])->assertStatus(401);
    }
}
