<?php

namespace Tests\Feature;

use App\Models\PeminjamanPerangkat;
use App\Models\Perangkat;
use App\Models\User;
use App\Services\PengingatPengembalianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pengingat pengembalian perangkat saat jatuh tempo (SRS UC-03/UC-07).
 */
class PengingatPengembalianTest extends TestCase
{
    use RefreshDatabase;

    private function perangkat(): Perangkat
    {
        return Perangkat::create([
            'nama_perangkat' => 'Router Mikrotik RB941',
            'nomor_seri' => 'SN-'.uniqid(),
            'kategori' => 'Router',
            'status' => 'dipinjam',
        ]);
    }

    private function pinjaman(User $user, string $rencanaKembali, string $status = 'disetujui', ?string $aktual = null): PeminjamanPerangkat
    {
        return PeminjamanPerangkat::create([
            'perangkat_id' => $this->perangkat()->id,
            'user_id' => $user->id,
            'tanggal_pinjam' => Carbon::today()->subDays(3)->toDateString(),
            'tanggal_kembali_rencana' => $rencanaKembali,
            'tanggal_kembali_aktual' => $aktual,
            'status' => $status,
        ]);
    }

    private function service(): PengingatPengembalianService
    {
        return app(PengingatPengembalianService::class);
    }

    public function test_pinjaman_jatuh_tempo_hari_ini_membuat_pengingat(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $pinjaman = $this->pinjaman($mhs, Carbon::today()->toDateString());

        $dibuat = $this->service()->generate();

        $this->assertSame(1, $dibuat);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $mhs->id,
            'tipe' => 'pengingat',
            'referensi_id' => $pinjaman->id,
        ]);
        $this->assertStringContainsString(
            'pukul 17.00',
            (string) \App\Models\Notifikasi::where('referensi_id', $pinjaman->id)->value('pesan'),
        );
    }

    public function test_pinjaman_terlambat_juga_membuat_pengingat(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $this->pinjaman($mhs, Carbon::today()->subDay()->toDateString());

        $this->assertSame(1, $this->service()->generate());
    }

    public function test_idempotent_tidak_menduplikasi_dalam_sehari(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $this->pinjaman($mhs, Carbon::today()->toDateString());

        $this->service()->generate();
        $this->service()->generate();

        $this->assertDatabaseCount('notifikasi', 1);
    }

    public function test_belum_jatuh_tempo_tidak_membuat_pengingat(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $this->pinjaman($mhs, Carbon::today()->addDay()->toDateString());

        $this->assertSame(0, $this->service()->generate());
        $this->assertDatabaseCount('notifikasi', 0);
    }

    public function test_sudah_dikembalikan_tidak_membuat_pengingat(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        // Jatuh tempo hari ini tapi sudah dikembalikan (ada tanggal aktual, status dikembalikan).
        $this->pinjaman($mhs, Carbon::today()->toDateString(), 'dikembalikan', Carbon::today()->toDateString());

        $this->assertSame(0, $this->service()->generate());
        $this->assertDatabaseCount('notifikasi', 0);
    }

    public function test_membuka_notifikasi_memicu_pengingat_lazy(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $pinjaman = $this->pinjaman($mhs, Carbon::today()->toDateString());

        Sanctum::actingAs($mhs);
        $res = $this->getJson('/api/notifikasi')->assertOk();

        $res->assertJsonPath('unread_count', 1);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $mhs->id,
            'tipe' => 'pengingat',
            'referensi_id' => $pinjaman->id,
        ]);
    }
}
