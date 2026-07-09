<?php

namespace Tests\Feature;

use App\Models\PeminjamanRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Laporan/Report (FASE 8, SRS UC-06, 3_SDD.md 5.13). Akses hanya Admin/Supervisor.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_mengakses_report(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/report')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'periode' => ['dari', 'sampai'],
                    'peminjaman_ruangan' => ['total_pengajuan', 'total_disetujui', 'total_ditolak', 'total_menunggu'],
                    'peminjaman_perangkat' => ['total_pengajuan', 'total_disetujui', 'total_ditolak', 'total_dikembalikan'],
                    'tugas' => ['total_terkumpul', 'total_mahasiswa_unik', 'total_kelas'],
                ],
            ]);
    }

    public function test_supervisor_dapat_mengakses_report(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->getJson('/api/report')->assertOk();
    }

    public function test_dosen_tidak_dapat_mengakses_report(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));
        $this->getJson('/api/report')->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_mengakses_report(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->getJson('/api/report')->assertForbidden();
    }

    public function test_guest_ditolak(): void
    {
        $this->getJson('/api/report')->assertUnauthorized();
    }

    public function test_agregasi_menghitung_status_peminjaman_ruangan(): void
    {
        $ruangan = Ruangan::create(['nama_ruangan' => 'Lab A', 'kapasitas' => 30, 'status' => 'tersedia']);
        $pengaju = User::factory()->create(['role' => 'mahasiswa']);

        foreach (['disetujui', 'disetujui', 'ditolak', 'menunggu'] as $i => $status) {
            PeminjamanRuangan::create([
                'ruangan_id' => $ruangan->id,
                'user_id' => $pengaju->id,
                'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '10:00:00',
                'keperluan' => 'x'.$i,
                'status' => $status,
            ]);
        }

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/report')
            ->assertOk()
            ->assertJsonPath('data.peminjaman_ruangan.total_pengajuan', 4)
            ->assertJsonPath('data.peminjaman_ruangan.total_disetujui', 2)
            ->assertJsonPath('data.peminjaman_ruangan.total_ditolak', 1)
            ->assertJsonPath('data.peminjaman_ruangan.total_menunggu', 1);
    }

    public function test_pdf_mengembalikan_file_pdf(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/api/report/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
