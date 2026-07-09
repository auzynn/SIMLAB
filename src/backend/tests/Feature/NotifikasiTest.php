<?php

namespace Tests\Feature;

use App\Models\Notifikasi;
use App\Models\PeminjamanRuangan;
use App\Models\Ruangan;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Notifikasi in-app (FASE 9, SRS UC-07, 3_SDD.md 3.16, 5.14).
 * Notifikasi dibuat otomatis sebagai efek samping aksi lain, dalam transaksi yang sama.
 */
class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function ruangan(): Ruangan
    {
        return Ruangan::create(['nama_ruangan' => 'Lab A', 'kapasitas' => 30, 'status' => 'tersedia']);
    }

    private function peminjamanMenunggu(int $userId, Ruangan $ruangan): PeminjamanRuangan
    {
        return PeminjamanRuangan::create([
            'ruangan_id' => $ruangan->id,
            'user_id' => $userId,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan' => 'x',
            'status' => 'menunggu',
        ]);
    }

    public function test_pengajuan_baru_membuat_notifikasi_untuk_semua_approver_bukan_pengaju(): void
    {
        $ruangan = $this->ruangan();
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $pengaju = User::factory()->create(['role' => 'mahasiswa']);

        Sanctum::actingAs($pengaju);
        $this->postJson('/api/peminjaman-ruangan', [
            'ruangan_id' => $ruangan->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'keperluan' => 'Riset',
        ])->assertCreated();

        // Notifikasi masuk ke admin & supervisor, bukan ke pengaju (T9.18).
        $this->assertDatabaseHas('notifikasi', ['user_id' => $admin->id, 'tipe' => 'pengajuan_masuk']);
        $this->assertDatabaseHas('notifikasi', ['user_id' => $supervisor->id, 'tipe' => 'pengajuan_masuk']);
        $this->assertDatabaseMissing('notifikasi', ['user_id' => $pengaju->id]);
        $this->assertDatabaseCount('notifikasi', 2);
    }

    public function test_approve_membuat_notifikasi_untuk_pengaju_saja(): void
    {
        $ruangan = $this->ruangan();
        $pengaju = User::factory()->create(['role' => 'mahasiswa']);
        $peminjaman = $this->peminjamanMenunggu($pengaju->id, $ruangan);

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->patchJson("/api/peminjaman-ruangan/{$peminjaman->id}/approve")->assertOk();

        // Notifikasi status hanya untuk pengaju (T9.17).
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $pengaju->id,
            'tipe' => 'status_pengajuan',
            'referensi_id' => $peminjaman->id,
        ]);
        $this->assertDatabaseCount('notifikasi', 1);
    }

    public function test_me_mengembalikan_unread_notifications_count(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        Notifikasi::create(['user_id' => $user->id, 'judul' => 'a', 'pesan' => 'a', 'tipe' => 'pendaftaran']);
        Notifikasi::create(['user_id' => $user->id, 'judul' => 'b', 'pesan' => 'b', 'tipe' => 'pendaftaran', 'is_read' => true]);

        Sanctum::actingAs($user);
        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('unread_notifications_count', 1);
    }

    public function test_index_hanya_notifikasi_sendiri_dengan_unread_count(): void
    {
        $saya = User::factory()->create(['role' => 'mahasiswa']);
        $lain = User::factory()->create(['role' => 'mahasiswa']);
        Notifikasi::create(['user_id' => $saya->id, 'judul' => 'a', 'pesan' => 'a', 'tipe' => 'pendaftaran']);
        Notifikasi::create(['user_id' => $lain->id, 'judul' => 'b', 'pesan' => 'b', 'tipe' => 'pendaftaran']);

        Sanctum::actingAs($saya);
        $this->getJson('/api/notifikasi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('unread_count', 1);
    }

    public function test_read_notifikasi_bukan_milik_sendiri_ditolak(): void
    {
        $pemilik = User::factory()->create(['role' => 'mahasiswa']);
        $notif = Notifikasi::create(['user_id' => $pemilik->id, 'judul' => 'a', 'pesan' => 'a', 'tipe' => 'pendaftaran']);

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->patchJson("/api/notifikasi/{$notif->id}/read")->assertForbidden();
        $this->assertDatabaseHas('notifikasi', ['id' => $notif->id, 'is_read' => false]);
    }

    public function test_read_all_menandai_semua_dibaca(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        Notifikasi::create(['user_id' => $user->id, 'judul' => 'a', 'pesan' => 'a', 'tipe' => 'pendaftaran']);
        Notifikasi::create(['user_id' => $user->id, 'judul' => 'b', 'pesan' => 'b', 'tipe' => 'pendaftaran']);

        Sanctum::actingAs($user);
        $this->patchJson('/api/notifikasi/read-all')->assertOk();
        $this->assertDatabaseCount('notifikasi', 2);
        $this->assertSame(0, $user->notifikasi()->where('is_read', false)->count());
    }

    public function test_pemilik_dapat_menghapus_notifikasinya(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $notif = Notifikasi::create(['user_id' => $user->id, 'judul' => 'a', 'pesan' => 'a', 'tipe' => 'pendaftaran']);

        Sanctum::actingAs($user);
        $this->deleteJson("/api/notifikasi/{$notif->id}")->assertOk();
        $this->assertDatabaseMissing('notifikasi', ['id' => $notif->id]);
    }

    public function test_insert_notifikasi_ikut_rollback_saat_transaksi_gagal(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $service = app(NotifikasiService::class);

        // Simulasikan transaksi pemicu yang gagal setelah notifikasi dibuat (T9.21).
        try {
            DB::transaction(function () use ($service, $user) {
                $service->kirim($user->id, 'x', 'y', 'status_pengajuan');
                throw new \RuntimeException('aksi pemicu gagal');
            });
        } catch (\RuntimeException) {
            // ditelan sengaja
        }

        // Tidak ada notifikasi orphan — insert ikut rollback.
        $this->assertDatabaseCount('notifikasi', 0);
    }
}
