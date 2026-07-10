<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KelasLab;
use App\Models\KelasLabPeserta;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Deadline pengumpulan tugas per pertemuan Kelas Lab — diatur Dosen pengampu/Supervisor/Admin.
 */
class DeadlinePertemuanTest extends TestCase
{
    use RefreshDatabase;

    private function buatKelas(?User $dosenUser = null): KelasLab
    {
        $dosenUser ??= User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::firstOrCreate(['user_id' => $dosenUser->id]);
        $mk = MataKuliah::create(['nama_mk' => 'Praktikum IoT']);
        $ruangan = Ruangan::create(['nama_ruangan' => 'Lab. Programming']);

        return KelasLab::create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $ruangan->id,
            'dibuat_oleh' => $dosenUser->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'selasa',
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '12:00:00',
            'tanggal_mulai_semester' => '2026-08-01',
            'tanggal_selesai_semester' => '2026-12-31',
            'kuota' => 30,
        ]);
    }

    public function test_dosen_pengampu_dapat_menetapkan_deadline(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/3", ['deadline' => '2026-09-01 12:00:00'])
            ->assertOk();

        $this->assertDatabaseHas('deadline_pertemuan', [
            'kelas_lab_id' => $kelas->id,
            'pertemuan' => 3,
            'deadline' => '2026-09-01 12:00:00',
        ]);
    }

    public function test_menetapkan_ulang_menimpa_deadline_lama(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/3", ['deadline' => '2026-09-01 12:00:00'])->assertOk();
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/3", ['deadline' => '2026-09-05 10:00:00'])->assertOk();

        $this->assertDatabaseCount('deadline_pertemuan', 1);
        $this->assertDatabaseHas('deadline_pertemuan', ['pertemuan' => 3, 'deadline' => '2026-09-05 10:00:00']);
    }

    public function test_supervisor_dapat_menetapkan_deadline(): void
    {
        $kelas = $this->buatKelas();

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/1", ['deadline' => '2026-09-01 12:00:00'])->assertOk();

        $this->assertDatabaseHas('deadline_pertemuan', ['kelas_lab_id' => $kelas->id, 'pertemuan' => 1]);
    }

    public function test_dosen_lain_tidak_dapat_menetapkan_deadline(): void
    {
        $kelas = $this->buatKelas();

        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/1", ['deadline' => '2026-09-01 12:00:00'])
            ->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_menetapkan_deadline(): void
    {
        $kelas = $this->buatKelas();

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/1", ['deadline' => '2026-09-01 12:00:00'])
            ->assertForbidden();
    }

    public function test_pertemuan_di_luar_rentang_ditolak(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/17", ['deadline' => '2026-09-01 12:00:00'])
            ->assertStatus(422);
    }

    public function test_mahasiswa_disetujui_dapat_melihat_deadline(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/2", ['deadline' => '2026-09-01 12:00:00'])->assertOk();

        // Mahasiswa peserta yang sudah DISETUJUI berhak melihat materi/deadline.
        $mhsUser = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::create(['user_id' => $mhsUser->id, 'npm' => '220900', 'angkatan' => '2022']);
        KelasLabPeserta::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $mhs->id, 'status' => 'disetujui']);

        Sanctum::actingAs($mhsUser);
        $this->getJson("/api/kelas-lab/{$kelas->id}/deadline")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['pertemuan' => 2]);
    }

    public function test_mahasiswa_belum_disetujui_tidak_dapat_melihat_deadline(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/2", ['deadline' => '2026-09-01 12:00:00'])->assertOk();

        // Mahasiswa yang masih menunggu persetujuan tidak boleh mengintip materi/deadline.
        $mhsUser = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::create(['user_id' => $mhsUser->id, 'npm' => '220901', 'angkatan' => '2022']);
        KelasLabPeserta::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $mhs->id, 'status' => 'menunggu']);

        Sanctum::actingAs($mhsUser);
        $this->getJson("/api/kelas-lab/{$kelas->id}/deadline")->assertForbidden();
    }

    public function test_materi_dapat_diisi_tanpa_deadline(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/2", ['materi' => 'Pengenalan Mikrotik'])
            ->assertOk();

        $this->assertDatabaseHas('deadline_pertemuan', [
            'kelas_lab_id' => $kelas->id,
            'pertemuan' => 2,
            'materi' => 'Pengenalan Mikrotik',
            'deadline' => null,
        ]);
    }

    public function test_materi_dan_deadline_bisa_bersamaan(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/1", [
            'materi' => 'Analisis Jaringan',
            'deadline' => '2026-09-01 12:00:00',
        ])->assertOk();

        $this->assertDatabaseHas('deadline_pertemuan', [
            'pertemuan' => 1,
            'materi' => 'Analisis Jaringan',
            'deadline' => '2026-09-01 12:00:00',
        ]);
    }

    public function test_mengosongkan_materi_dan_deadline_menghapus_record(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/1", ['materi' => 'X'])->assertOk();
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/1", [])->assertOk();

        $this->assertDatabaseMissing('deadline_pertemuan', ['kelas_lab_id' => $kelas->id, 'pertemuan' => 1]);
    }

    public function test_dosen_dapat_menghapus_deadline(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        Sanctum::actingAs($dosenUser);
        $this->putJson("/api/kelas-lab/{$kelas->id}/deadline/4", ['deadline' => '2026-09-01 12:00:00'])->assertOk();
        $this->deleteJson("/api/kelas-lab/{$kelas->id}/deadline/4")->assertOk();

        $this->assertDatabaseMissing('deadline_pertemuan', ['kelas_lab_id' => $kelas->id, 'pertemuan' => 4]);
    }
}
