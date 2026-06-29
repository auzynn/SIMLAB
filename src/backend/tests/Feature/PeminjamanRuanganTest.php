<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KelasLab;
use App\Models\MataKuliah;
use App\Models\PeminjamanRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pengajuan & persetujuan peminjaman ruangan (SRS UC-02, 3_SDD.md 3.5).
 */
class PeminjamanRuanganTest extends TestCase
{
    use RefreshDatabase;

    private function ruangan(string $status = 'tersedia'): Ruangan
    {
        return Ruangan::create(['nama_ruangan' => 'Lab A', 'kapasitas' => 30, 'status' => $status]);
    }

    private function seninDepan(): string
    {
        return Carbon::today()->next(Carbon::MONDAY)->format('Y-m-d');
    }

    public function test_mahasiswa_dapat_mengajukan_peminjaman_slot_kosong(): void
    {
        $ruangan = $this->ruangan();
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/peminjaman-ruangan', [
            'ruangan_id' => $ruangan->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'keperluan' => 'Riset tugas akhir',
        ])->assertCreated();

        $this->assertDatabaseHas('peminjaman_ruangan', [
            'ruangan_id' => $ruangan->id,
            'status' => 'menunggu',
        ]);
    }

    public function test_dosen_tidak_dapat_mengajukan_peminjaman(): void
    {
        $ruangan = $this->ruangan();
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));

        $this->postJson('/api/peminjaman-ruangan', [
            'ruangan_id' => $ruangan->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'keperluan' => 'Dosen tidak meminjam ruangan',
        ])->assertForbidden();

        $this->assertDatabaseCount('peminjaman_ruangan', 0);
    }

    public function test_pengajuan_bentrok_dengan_kelas_lab_ditolak(): void
    {
        $ruangan = $this->ruangan();
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::create(['user_id' => $dosenUser->id]);
        $mk = MataKuliah::create(['nama_mk' => 'Praktikum Jaringan']);

        KelasLab::create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $ruangan->id,
            'dibuat_oleh' => $dosenUser->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/peminjaman-ruangan', [
            'ruangan_id' => $ruangan->id,
            'tanggal' => $this->seninDepan(),
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:00',
            'keperluan' => 'Bentrok dengan kelas',
        ])->assertStatus(422);
    }

    public function test_pengajuan_bentrok_dengan_peminjaman_disetujui_ditolak(): void
    {
        $ruangan = $this->ruangan();
        $tanggal = Carbon::tomorrow()->format('Y-m-d');

        PeminjamanRuangan::create([
            'ruangan_id' => $ruangan->id,
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'tanggal' => $tanggal,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan' => 'Sudah disetujui',
            'status' => 'disetujui',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/peminjaman-ruangan', [
            'ruangan_id' => $ruangan->id,
            'tanggal' => $tanggal,
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:00',
            'keperluan' => 'Bentrok',
        ])->assertStatus(422);
    }

    public function test_pengajuan_ruangan_tidak_tersedia_ditolak(): void
    {
        $ruangan = $this->ruangan('perbaikan');
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->postJson('/api/peminjaman-ruangan', [
            'ruangan_id' => $ruangan->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'keperluan' => 'Ruangan rusak',
        ])->assertStatus(422);
    }

    public function test_mahasiswa_hanya_melihat_pengajuan_sendiri(): void
    {
        $ruangan = $this->ruangan();
        $saya = User::factory()->create(['role' => 'mahasiswa']);
        $lain = User::factory()->create(['role' => 'mahasiswa']);

        foreach ([$saya, $lain] as $u) {
            PeminjamanRuangan::create([
                'ruangan_id' => $ruangan->id,
                'user_id' => $u->id,
                'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '10:00:00',
                'keperluan' => 'x',
                'status' => 'menunggu',
            ]);
        }

        Sanctum::actingAs($saya);

        $this->getJson('/api/peminjaman-ruangan')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_melihat_semua_pengajuan(): void
    {
        $ruangan = $this->ruangan();
        foreach (range(1, 2) as $i) {
            PeminjamanRuangan::create([
                'ruangan_id' => $ruangan->id,
                'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
                'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '10:00:00',
                'keperluan' => 'x',
                'status' => 'menunggu',
            ]);
        }

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/peminjaman-ruangan')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_supervisor_dapat_menyetujui_pengajuan(): void
    {
        $ruangan = $this->ruangan();
        $peminjaman = PeminjamanRuangan::create([
            'ruangan_id' => $ruangan->id,
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan' => 'x',
            'status' => 'menunggu',
        ]);

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        Sanctum::actingAs($supervisor);

        $this->patchJson("/api/peminjaman-ruangan/{$peminjaman->id}/approve")->assertOk();

        $this->assertDatabaseHas('peminjaman_ruangan', [
            'id' => $peminjaman->id,
            'status' => 'disetujui',
            'disetujui_oleh' => $supervisor->id,
        ]);
    }

    public function test_mahasiswa_tidak_dapat_menyetujui_pengajuan(): void
    {
        $ruangan = $this->ruangan();
        $peminjaman = PeminjamanRuangan::create([
            'ruangan_id' => $ruangan->id,
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan' => 'x',
            'status' => 'menunggu',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->patchJson("/api/peminjaman-ruangan/{$peminjaman->id}/approve")->assertForbidden();
    }

    public function test_supervisor_dapat_menghapus_pengajuan(): void
    {
        $ruangan = $this->ruangan();
        $peminjaman = PeminjamanRuangan::create([
            'ruangan_id' => $ruangan->id,
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan' => 'x',
            'status' => 'disetujui',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->deleteJson("/api/peminjaman-ruangan/{$peminjaman->id}")->assertOk();
        $this->assertDatabaseMissing('peminjaman_ruangan', ['id' => $peminjaman->id]);
    }

    public function test_mahasiswa_tidak_dapat_menghapus_pengajuan(): void
    {
        $ruangan = $this->ruangan();
        $peminjaman = PeminjamanRuangan::create([
            'ruangan_id' => $ruangan->id,
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan' => 'x',
            'status' => 'menunggu',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->deleteJson("/api/peminjaman-ruangan/{$peminjaman->id}")->assertForbidden();
    }

    public function test_approve_menjalankan_ulang_validasi_bentrok(): void
    {
        $ruangan = $this->ruangan();
        $tanggal = $this->seninDepan();

        // Pengajuan masih menunggu
        $peminjaman = PeminjamanRuangan::create([
            'ruangan_id' => $ruangan->id,
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'tanggal' => $tanggal,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan' => 'x',
            'status' => 'menunggu',
        ]);

        // Setelah submit, muncul kelas_lab yang membuat slot bentrok
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::create(['user_id' => $dosenUser->id]);
        $mk = MataKuliah::create(['nama_mk' => 'Praktikum X']);
        KelasLab::create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $ruangan->id,
            'dibuat_oleh' => $dosenUser->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));

        $this->patchJson("/api/peminjaman-ruangan/{$peminjaman->id}/approve")->assertStatus(422);
        $this->assertDatabaseHas('peminjaman_ruangan', ['id' => $peminjaman->id, 'status' => 'menunggu']);
    }
}
