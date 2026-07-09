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
 * Pengajuan & persetujuan peminjaman perangkat (SRS UC-03, 3_SDD.md 3.10).
 */
class PeminjamanPerangkatTest extends TestCase
{
    use RefreshDatabase;

    private function perangkat(string $status = 'tersedia'): Perangkat
    {
        return Perangkat::create([
            'nama_perangkat' => 'Switch Cisco',
            'nomor_seri' => 'SN-'.uniqid(),
            'kategori' => 'Switch',
            'status' => $status,
        ]);
    }

    private function peminjaman(Perangkat $perangkat, User $user, string $status = 'menunggu'): PeminjamanPerangkat
    {
        return PeminjamanPerangkat::create([
            'perangkat_id' => $perangkat->id,
            'user_id' => $user->id,
            'tanggal_pinjam' => Carbon::today()->toDateString(),
            'tanggal_kembali_rencana' => Carbon::today()->addDays(3)->toDateString(),
            'status' => $status,
        ]);
    }

    public function test_mahasiswa_dapat_mengajukan_peminjaman(): void
    {
        $perangkat = $this->perangkat();
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/peminjaman-perangkat', [
            'perangkat_id' => $perangkat->id,
            'tanggal_pinjam' => Carbon::today()->toDateString(),
            'tanggal_kembali_rencana' => Carbon::today()->addDays(5)->toDateString(),
        ])->assertCreated();

        $this->assertDatabaseHas('peminjaman_perangkat', [
            'perangkat_id' => $perangkat->id,
            'status' => 'menunggu',
        ]);
    }

    public function test_dosen_tidak_dapat_mengajukan_peminjaman(): void
    {
        $perangkat = $this->perangkat();
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));

        $this->postJson('/api/peminjaman-perangkat', [
            'perangkat_id' => $perangkat->id,
            'tanggal_pinjam' => Carbon::today()->toDateString(),
            'tanggal_kembali_rencana' => Carbon::today()->addDays(5)->toDateString(),
        ])->assertForbidden();

        $this->assertDatabaseCount('peminjaman_perangkat', 0);
    }

    public function test_pengajuan_perangkat_tidak_tersedia_ditolak(): void
    {
        $perangkat = $this->perangkat('perbaikan');
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/peminjaman-perangkat', [
            'perangkat_id' => $perangkat->id,
            'tanggal_pinjam' => Carbon::today()->toDateString(),
            'tanggal_kembali_rencana' => Carbon::today()->addDays(5)->toDateString(),
        ])->assertStatus(422);
    }

    public function test_mahasiswa_hanya_melihat_pengajuan_sendiri(): void
    {
        $perangkat = $this->perangkat();
        $saya = User::factory()->create(['role' => 'mahasiswa']);
        $lain = User::factory()->create(['role' => 'mahasiswa']);
        $this->peminjaman($perangkat, $saya);
        $this->peminjaman($this->perangkat(), $lain);

        Sanctum::actingAs($saya);
        $this->getJson('/api/peminjaman-perangkat')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_supervisor_menyetujui_membuat_perangkat_dipinjam(): void
    {
        $perangkat = $this->perangkat();
        $peminjaman = $this->peminjaman($perangkat, User::factory()->create(['role' => 'mahasiswa']));
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        Sanctum::actingAs($supervisor);
        $this->patchJson("/api/peminjaman-perangkat/{$peminjaman->id}/approve")->assertOk();

        $this->assertDatabaseHas('peminjaman_perangkat', [
            'id' => $peminjaman->id,
            'status' => 'disetujui',
            'disetujui_oleh' => $supervisor->id,
        ]);
        $this->assertDatabaseHas('perangkat', ['id' => $perangkat->id, 'status' => 'dipinjam']);
    }

    public function test_mahasiswa_tidak_dapat_menyetujui(): void
    {
        $perangkat = $this->perangkat();
        $peminjaman = $this->peminjaman($perangkat, User::factory()->create(['role' => 'mahasiswa']));

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->patchJson("/api/peminjaman-perangkat/{$peminjaman->id}/approve")->assertForbidden();
    }

    public function test_approve_ditolak_bila_perangkat_tidak_lagi_tersedia(): void
    {
        $perangkat = $this->perangkat('perbaikan');
        $peminjaman = $this->peminjaman($perangkat, User::factory()->create(['role' => 'mahasiswa']));

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->patchJson("/api/peminjaman-perangkat/{$peminjaman->id}/approve")->assertStatus(422);
        $this->assertDatabaseHas('peminjaman_perangkat', ['id' => $peminjaman->id, 'status' => 'menunggu']);
    }

    public function test_konfirmasi_pengembalian_mengembalikan_perangkat_tersedia(): void
    {
        $perangkat = $this->perangkat('dipinjam');
        $peminjaman = $this->peminjaman($perangkat, User::factory()->create(['role' => 'mahasiswa']), 'disetujui');

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->patchJson("/api/peminjaman-perangkat/{$peminjaman->id}/kembalikan")->assertOk();

        $this->assertDatabaseHas('perangkat', ['id' => $perangkat->id, 'status' => 'tersedia']);
        $peminjaman->refresh();
        $this->assertSame('dikembalikan', $peminjaman->status);
        $this->assertNotNull($peminjaman->tanggal_kembali_aktual);
    }

    public function test_pemilik_dapat_membatalkan_pengajuan_menunggu(): void
    {
        $saya = User::factory()->create(['role' => 'mahasiswa']);
        $peminjaman = $this->peminjaman($this->perangkat(), $saya);

        Sanctum::actingAs($saya);
        $this->deleteJson("/api/peminjaman-perangkat/{$peminjaman->id}")->assertOk();

        $this->assertDatabaseMissing('peminjaman_perangkat', ['id' => $peminjaman->id]);
    }

    public function test_pemilik_tidak_dapat_membatalkan_pengajuan_yang_sudah_disetujui(): void
    {
        $saya = User::factory()->create(['role' => 'mahasiswa']);
        $peminjaman = $this->peminjaman($this->perangkat('dipinjam'), $saya, 'disetujui');

        Sanctum::actingAs($saya);
        $this->deleteJson("/api/peminjaman-perangkat/{$peminjaman->id}")->assertForbidden();

        $this->assertDatabaseHas('peminjaman_perangkat', ['id' => $peminjaman->id]);
    }

    public function test_mahasiswa_lain_tidak_dapat_membatalkan_pengajuan_bukan_miliknya(): void
    {
        $pemilik = User::factory()->create(['role' => 'mahasiswa']);
        $peminjaman = $this->peminjaman($this->perangkat(), $pemilik);

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->deleteJson("/api/peminjaman-perangkat/{$peminjaman->id}")->assertForbidden();

        $this->assertDatabaseHas('peminjaman_perangkat', ['id' => $peminjaman->id]);
    }

    public function test_supervisor_dapat_menghapus_pengajuan_perangkat(): void
    {
        $peminjaman = $this->peminjaman($this->perangkat(), User::factory()->create(['role' => 'mahasiswa']));

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->deleteJson("/api/peminjaman-perangkat/{$peminjaman->id}")->assertOk();

        $this->assertDatabaseMissing('peminjaman_perangkat', ['id' => $peminjaman->id]);
    }

    public function test_supervisor_dapat_menghapus_riwayat_yang_sudah_dikembalikan(): void
    {
        $peminjaman = $this->peminjaman($this->perangkat('tersedia'), User::factory()->create(['role' => 'mahasiswa']), 'dikembalikan');

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->deleteJson("/api/peminjaman-perangkat/{$peminjaman->id}")->assertOk();

        $this->assertDatabaseMissing('peminjaman_perangkat', ['id' => $peminjaman->id]);
    }

    public function test_supervisor_dapat_menghapus_riwayat_yang_ditolak(): void
    {
        $peminjaman = $this->peminjaman($this->perangkat(), User::factory()->create(['role' => 'mahasiswa']), 'ditolak');

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->deleteJson("/api/peminjaman-perangkat/{$peminjaman->id}")->assertOk();

        $this->assertDatabaseMissing('peminjaman_perangkat', ['id' => $peminjaman->id]);
    }

    public function test_peminjaman_berjalan_disetujui_tidak_dapat_dihapus(): void
    {
        $peminjaman = $this->peminjaman($this->perangkat('dipinjam'), User::factory()->create(['role' => 'mahasiswa']), 'disetujui');

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->deleteJson("/api/peminjaman-perangkat/{$peminjaman->id}")->assertStatus(422);

        $this->assertDatabaseHas('peminjaman_perangkat', ['id' => $peminjaman->id]);
    }
}
