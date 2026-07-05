<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use App\Models\PeminjamanPerangkat;
use App\Models\PerpanjanganPeminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Perpanjangan waktu pinjam perangkat (SRS UC-03, 3_SDD.md 3.11).
 */
class PerpanjanganTest extends TestCase
{
    use RefreshDatabase;

    private function peminjaman(User $user, string $rencana): PeminjamanPerangkat
    {
        $perangkat = Perangkat::create([
            'nama_perangkat' => 'PC Lab',
            'nomor_seri' => 'SN-'.uniqid(),
            'status' => 'dipinjam',
        ]);

        return PeminjamanPerangkat::create([
            'perangkat_id' => $perangkat->id,
            'user_id' => $user->id,
            'tanggal_pinjam' => Carbon::today()->subDays(5)->toDateString(),
            'tanggal_kembali_rencana' => $rencana,
            'status' => 'disetujui',
        ]);
    }

    public function test_mahasiswa_dapat_mengajukan_perpanjangan_sebelum_jatuh_tempo(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $peminjaman = $this->peminjaman($mahasiswa, Carbon::today()->addDays(2)->toDateString());

        Sanctum::actingAs($mahasiswa);
        $this->postJson("/api/peminjaman-perangkat/{$peminjaman->id}/perpanjangan", [
            'tanggal_kembali_baru' => Carbon::today()->addDays(7)->toDateString(),
        ])->assertCreated();

        $this->assertDatabaseHas('perpanjangan_peminjaman', [
            'peminjaman_perangkat_id' => $peminjaman->id,
            'status' => 'menunggu',
        ]);
    }

    public function test_perpanjangan_ditolak_bila_rencana_kembali_sudah_lewat(): void
    {
        // T4.15: aturan kunci UC-03.
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $peminjaman = $this->peminjaman($mahasiswa, Carbon::today()->subDay()->toDateString());

        Sanctum::actingAs($mahasiswa);
        $this->postJson("/api/peminjaman-perangkat/{$peminjaman->id}/perpanjangan", [
            'tanggal_kembali_baru' => Carbon::today()->addDays(7)->toDateString(),
        ])->assertStatus(422);

        $this->assertDatabaseCount('perpanjangan_peminjaman', 0);
    }

    public function test_mahasiswa_lain_tidak_dapat_mengajukan_perpanjangan(): void
    {
        $pemilik = User::factory()->create(['role' => 'mahasiswa']);
        $peminjaman = $this->peminjaman($pemilik, Carbon::today()->addDays(2)->toDateString());

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->postJson("/api/peminjaman-perangkat/{$peminjaman->id}/perpanjangan", [
            'tanggal_kembali_baru' => Carbon::today()->addDays(7)->toDateString(),
        ])->assertForbidden();
    }

    public function test_approve_perpanjangan_memperbarui_tanggal_kembali_rencana(): void
    {
        // T4.9: saat disetujui, tanggal_kembali_rencana induk otomatis diperbarui.
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $peminjaman = $this->peminjaman($mahasiswa, Carbon::today()->addDays(2)->toDateString());
        $tanggalBaru = Carbon::today()->addDays(10)->toDateString();

        $perpanjangan = PerpanjanganPeminjaman::create([
            'peminjaman_perangkat_id' => $peminjaman->id,
            'tanggal_kembali_baru' => $tanggalBaru,
            'status' => 'menunggu',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->patchJson("/api/perpanjangan/{$perpanjangan->id}/approve")->assertOk();

        $this->assertDatabaseHas('perpanjangan_peminjaman', ['id' => $perpanjangan->id, 'status' => 'disetujui']);
        $this->assertDatabaseHas('peminjaman_perangkat', [
            'id' => $peminjaman->id,
            'tanggal_kembali_rencana' => $tanggalBaru,
        ]);
    }

    public function test_reject_perpanjangan_tidak_mengubah_tanggal_induk(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $rencanaAwal = Carbon::today()->addDays(2)->toDateString();
        $peminjaman = $this->peminjaman($mahasiswa, $rencanaAwal);

        $perpanjangan = PerpanjanganPeminjaman::create([
            'peminjaman_perangkat_id' => $peminjaman->id,
            'tanggal_kembali_baru' => Carbon::today()->addDays(10)->toDateString(),
            'status' => 'menunggu',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->patchJson("/api/perpanjangan/{$perpanjangan->id}/reject")->assertOk();

        $this->assertDatabaseHas('perpanjangan_peminjaman', ['id' => $perpanjangan->id, 'status' => 'ditolak']);
        $this->assertDatabaseHas('peminjaman_perangkat', [
            'id' => $peminjaman->id,
            'tanggal_kembali_rencana' => $rencanaAwal,
        ]);
    }
}
