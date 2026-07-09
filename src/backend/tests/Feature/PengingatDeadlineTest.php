<?php

namespace Tests\Feature;

use App\Models\DeadlinePertemuan;
use App\Models\Dosen;
use App\Models\KelasLab;
use App\Models\KelasLabPeserta;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\Tugas;
use App\Models\User;
use App\Services\PengingatDeadlineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pengingat tenggat tugas terlewati (SRS UC-07): notifikasi ke mahasiswa peserta
 * yang belum mengumpulkan setelah deadline pertemuan lewat.
 */
class PengingatDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // "Sekarang" = 10 Agu 2026 08:00 WIB (deadline uji ada sebelum/ sesudah ini).
        Carbon::setTestNow(Carbon::create(2026, 8, 10, 8, 0, 0, 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function kelas(): KelasLab
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::firstOrCreate(['user_id' => $dosenUser->id]);
        $mk = MataKuliah::create(['nama_mk' => 'Praktikum IoT']);
        $ruangan = Ruangan::create(['nama_ruangan' => 'Lab. Programming']);

        return KelasLab::create([
            'mata_kuliah_id' => $mk->id, 'dosen_id' => $dosen->id, 'ruangan_id' => $ruangan->id,
            'dibuat_oleh' => $dosenUser->id, 'nama_sesi' => 'Kelas A', 'hari' => 'selasa',
            'jam_mulai' => '10:00:00', 'jam_selesai' => '12:00:00',
            'tanggal_mulai_semester' => '2026-08-01', 'tanggal_selesai_semester' => '2026-12-31', 'kuota' => 30,
        ]);
    }

    private function peserta(KelasLab $kelas, string $status = 'disetujui'): array
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::create(['user_id' => $user->id, 'npm' => (string) random_int(1000000, 9999999), 'angkatan' => '2022']);
        KelasLabPeserta::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $mhs->id, 'status' => $status]);

        return [$user, $mhs];
    }

    private function deadline(KelasLab $kelas, int $pertemuan, string $deadline): DeadlinePertemuan
    {
        return DeadlinePertemuan::create(['kelas_lab_id' => $kelas->id, 'pertemuan' => $pertemuan, 'deadline' => $deadline]);
    }

    public function test_notif_dibuat_untuk_peserta_yang_belum_mengumpulkan_setelah_deadline(): void
    {
        $kelas = $this->kelas();
        [$user] = $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-09 12:00:00'); // sudah lewat

        $jumlah = app(PengingatDeadlineService::class)->generate();

        $this->assertSame(1, $jumlah);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $user->id,
            'tipe' => 'pengingat',
            'judul' => 'Tenggat Tugas Terlewati',
        ]);
    }

    public function test_tidak_ada_notif_bila_deadline_belum_lewat(): void
    {
        $kelas = $this->kelas();
        $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-20 12:00:00'); // masih akan datang

        $this->assertSame(0, app(PengingatDeadlineService::class)->generate());
        $this->assertDatabaseCount('notifikasi', 0);
    }

    public function test_peserta_yang_sudah_mengumpulkan_tidak_diberi_notif(): void
    {
        $kelas = $this->kelas();
        [$user, $mhs] = $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-09 12:00:00');
        Tugas::create(['kelas_lab_id' => $kelas->id, 'pertemuan' => 1, 'mahasiswa_id' => $mhs->id, 'judul' => 'A', 'tautan' => 'https://a.test']);

        $this->assertSame(0, app(PengingatDeadlineService::class)->generate());
    }

    public function test_tidak_duplikat_saat_dipanggil_berulang(): void
    {
        $kelas = $this->kelas();
        $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-09 12:00:00');

        $svc = app(PengingatDeadlineService::class);
        $this->assertSame(1, $svc->generate());
        $this->assertSame(0, $svc->generate());
        $this->assertDatabaseCount('notifikasi', 1);
    }

    public function test_peserta_belum_disetujui_diabaikan(): void
    {
        $kelas = $this->kelas();
        $this->peserta($kelas, 'menunggu');
        $this->deadline($kelas, 1, '2026-08-09 12:00:00');

        $this->assertSame(0, app(PengingatDeadlineService::class)->generate());
    }

    public function test_lazy_hanya_untuk_user_tertentu(): void
    {
        $kelas = $this->kelas();
        [$userA] = $this->peserta($kelas);
        [$userB] = $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-09 12:00:00');

        $jumlah = app(PengingatDeadlineService::class)->generate($userA->id);

        $this->assertSame(1, $jumlah);
        $this->assertDatabaseHas('notifikasi', ['user_id' => $userA->id, 'tipe' => 'pengingat']);
        $this->assertDatabaseMissing('notifikasi', ['user_id' => $userB->id]);
    }
}
