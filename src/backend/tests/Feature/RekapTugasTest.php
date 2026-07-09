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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Rekap kepatuhan pengumpulan tugas per kelas (Opsi B: "Perlu perhatian / Beres").
 */
class RekapTugasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 8, 10, 8, 0, 0, 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function kelas(?User $dosenUser = null): KelasLab
    {
        $dosenUser ??= User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::firstOrCreate(['user_id' => $dosenUser->id]);
        $mk = MataKuliah::create(['nama_mk' => 'Praktikum IoT '.uniqid()]);
        $ruangan = Ruangan::create(['nama_ruangan' => 'Lab '.uniqid()]);

        return KelasLab::create([
            'mata_kuliah_id' => $mk->id, 'dosen_id' => $dosen->id, 'ruangan_id' => $ruangan->id,
            'dibuat_oleh' => $dosenUser->id, 'nama_sesi' => 'Kelas A', 'hari' => 'selasa',
            'jam_mulai' => '10:00:00', 'jam_selesai' => '12:00:00',
            'tanggal_mulai_semester' => '2026-08-01', 'tanggal_selesai_semester' => '2026-12-31', 'kuota' => 30,
        ]);
    }

    private function peserta(KelasLab $kelas, string $status = 'disetujui'): Mahasiswa
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::create(['user_id' => $user->id, 'npm' => (string) random_int(1000000, 9999999), 'angkatan' => '2022']);
        KelasLabPeserta::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $mhs->id, 'status' => $status]);

        return $mhs;
    }

    private function deadline(KelasLab $kelas, int $pertemuan, string $deadline): void
    {
        DeadlinePertemuan::create(['kelas_lab_id' => $kelas->id, 'pertemuan' => $pertemuan, 'deadline' => $deadline]);
    }

    private function kirim(KelasLab $kelas, Mahasiswa $mhs, int $pertemuan): void
    {
        Tugas::create(['kelas_lab_id' => $kelas->id, 'pertemuan' => $pertemuan, 'mahasiswa_id' => $mhs->id, 'judul' => 'A', 'tautan' => 'https://a.test']);
    }

    public function test_perlu_perhatian_bila_deadline_lewat_dan_ada_yang_belum_kumpul(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->kelas($dosenUser);
        $a = $this->peserta($kelas);
        $this->peserta($kelas); // b — tidak mengumpulkan
        $this->deadline($kelas, 1, '2026-08-09 12:00:00'); // lewat
        $this->kirim($kelas, $a, 1);

        Sanctum::actingAs($dosenUser);
        $res = $this->getJson('/api/kelas-lab/rekap-tugas')->assertOk()->json('data');

        $this->assertCount(1, $res);
        $this->assertTrue($res[0]['perlu_perhatian']);
        $this->assertSame('perhatian', $res[0]['status']);
        $this->assertSame(1, $res[0]['tunggakan']); // 2 peserta - 1 pengumpul
    }

    public function test_beres_bila_semua_sudah_kumpul(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->kelas($dosenUser);
        $a = $this->peserta($kelas);
        $b = $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-09 12:00:00');
        $this->kirim($kelas, $a, 1);
        $this->kirim($kelas, $b, 1);

        Sanctum::actingAs($dosenUser);
        $res = $this->getJson('/api/kelas-lab/rekap-tugas')->assertOk()->json('data');

        $this->assertFalse($res[0]['perlu_perhatian']);
        $this->assertSame('beres', $res[0]['status']); // semua deadline lewat & tuntas
        $this->assertSame(0, $res[0]['tunggakan']);
        $this->assertSame(1, $res[0]['total_tugas']);
    }

    public function test_deadline_belum_lewat_berstatus_berjalan(): void
    {
        // Deadline masih di masa depan & belum ada yang kumpul → bukan "beres",
        // melainkan "berjalan" (tak ada tunggakan jatuh tempo, tapi belum tuntas).
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->kelas($dosenUser);
        $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-20 12:00:00'); // akan datang

        Sanctum::actingAs($dosenUser);
        $res = $this->getJson('/api/kelas-lab/rekap-tugas')->assertOk()->json('data');

        $this->assertFalse($res[0]['perlu_perhatian']);
        $this->assertSame('berjalan', $res[0]['status']);
        $this->assertSame(0, $res[0]['tunggakan']);
    }

    public function test_progres_pertemuan_bertugas_dan_berjalan(): void
    {
        // now = 10 Agu 2026; kelas mulai 1 Agu → pertemuan berjalan ≈ minggu ke-2.
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->kelas($dosenUser);
        $this->deadline($kelas, 1, '2026-08-05 12:00:00');
        $this->deadline($kelas, 2, '2026-08-12 12:00:00');
        // Materi tanpa deadline → tidak dihitung "bertugas".
        DeadlinePertemuan::create(['kelas_lab_id' => $kelas->id, 'pertemuan' => 3, 'materi' => 'Silabus', 'deadline' => null]);

        Sanctum::actingAs($dosenUser);
        $res = $this->getJson('/api/kelas-lab/rekap-tugas')->assertOk()->json('data');

        $this->assertSame(2, $res[0]['pertemuan_bertugas']);
        $this->assertGreaterThanOrEqual(1, $res[0]['pertemuan_berjalan']);
        $this->assertLessThanOrEqual(16, $res[0]['pertemuan_berjalan']);
    }

    public function test_materi_tanpa_deadline_tidak_terhitung_tunggakan(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->kelas($dosenUser);
        $this->peserta($kelas);
        DeadlinePertemuan::create(['kelas_lab_id' => $kelas->id, 'pertemuan' => 1, 'materi' => 'Materi saja', 'deadline' => null]);

        Sanctum::actingAs($dosenUser);
        $res = $this->getJson('/api/kelas-lab/rekap-tugas')->assertOk()->json('data');

        $this->assertFalse($res[0]['perlu_perhatian']);
        $this->assertSame(0, $res[0]['tunggakan']);
        $this->assertSame(0, $res[0]['pertemuan_bertugas']);
    }

    public function test_dosen_hanya_melihat_rekap_kelasnya(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $this->kelas($dosenUser);
        $this->kelas(); // kelas dosen lain

        Sanctum::actingAs($dosenUser);
        $this->getJson('/api/kelas-lab/rekap-tugas')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_supervisor_melihat_semua_kelas(): void
    {
        $this->kelas();
        $this->kelas();

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->getJson('/api/kelas-lab/rekap-tugas')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_mahasiswa_mendapat_rekap_kosong(): void
    {
        $this->kelas();

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->getJson('/api/kelas-lab/rekap-tugas')->assertOk()->assertJsonCount(0, 'data');
    }

    // ================= Laporan Rekap Tugas (endpoint /rekap-tugas — PDF/Excel) =================

    private function kirimPada(KelasLab $kelas, Mahasiswa $mhs, int $pertemuan, string $waktu): void
    {
        $tugas = Tugas::create([
            'kelas_lab_id' => $kelas->id, 'pertemuan' => $pertemuan, 'mahasiswa_id' => $mhs->id,
            'judul' => 'Tugas '.$pertemuan, 'tautan' => 'https://a.test',
        ]);
        // created_at bukan fillable → set eksplisit agar bisa menguji tepat/telat vs deadline.
        $tugas->forceFill(['created_at' => $waktu])->save();
    }

    public function test_laporan_struktur_json_ringkasan_dan_detail(): void
    {
        $this->kelas(); // pastikan ada minimal satu kelas pada rekap

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/rekap-tugas')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'generated_at',
                    'ringkasan' => [['kelas_lab_id', 'mata_kuliah', 'status', 'tunggakan', 'pertemuan_bertugas']],
                    'detail' => [['kelas_lab_id', 'pertemuan', 'peserta']],
                ],
            ]);
    }

    public function test_laporan_matriks_menandai_tepat_telat_belum(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->kelas($dosenUser);
        $tepat = $this->peserta($kelas);
        $telat = $this->peserta($kelas);
        $this->peserta($kelas); // belum kumpul
        $this->deadline($kelas, 1, '2026-08-09 12:00:00'); // sudah lewat

        $this->kirimPada($kelas, $tepat, 1, '2026-08-09 10:00:00'); // sebelum deadline → tepat
        $this->kirimPada($kelas, $telat, 1, '2026-08-09 15:00:00'); // sesudah deadline → telat

        Sanctum::actingAs($dosenUser);
        $detail = $this->getJson('/api/rekap-tugas')->assertOk()->json('data.detail');

        $this->assertCount(1, $detail);
        $peserta = collect($detail[0]['peserta'])->keyBy('npm');
        $this->assertSame('tepat', $peserta[$tepat->npm]['sel']['1']['status']);
        $this->assertSame('telat', $peserta[$telat->npm]['sel']['1']['status']);
        // Peserta ketiga: belum kumpul.
        $belum = collect($detail[0]['peserta'])->firstWhere('total_kumpul', 0);
        $this->assertSame('belum', $belum['sel']['1']['status']);
    }

    public function test_laporan_dosen_hanya_kelasnya(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $this->kelas($dosenUser);
        $this->kelas(); // kelas dosen lain

        Sanctum::actingAs($dosenUser);
        $this->getJson('/api/rekap-tugas')->assertOk()->assertJsonCount(1, 'data.detail');
    }

    public function test_laporan_mahasiswa_ditolak(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->getJson('/api/rekap-tugas')->assertForbidden();
    }

    public function test_laporan_guest_ditolak(): void
    {
        $this->getJson('/api/rekap-tugas')->assertUnauthorized();
    }

    public function test_laporan_pdf_mengembalikan_file_pdf(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/api/rekap-tugas/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_laporan_excel_mengembalikan_file_xlsx(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->kelas($dosenUser);
        $mhs = $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-09 12:00:00');
        $this->kirim($kelas, $mhs, 1);

        Sanctum::actingAs($dosenUser);

        $res = $this->get('/api/rekap-tugas/excel')->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            $res->headers->get('content-type').$res->headers->get('content-disposition'),
        );
    }

    public function test_laporan_excel_berisi_sheet_indeks_ringkasan_dan_kelas(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->kelas($dosenUser);
        $mhs = $this->peserta($kelas);
        $this->deadline($kelas, 1, '2026-08-09 12:00:00');
        $this->kirim($kelas, $mhs, 1);

        Sanctum::actingAs($dosenUser);
        $konten = $this->get('/api/rekap-tugas/excel')->assertOk()->streamedContent();

        // Muat workbook hasil untuk memverifikasi struktur sheet (regresi format branded).
        $tmp = tempnam(sys_get_temp_dir(), 'rekap').'.xlsx';
        file_put_contents($tmp, $konten);
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        $names = $book->getSheetNames();
        $book->disconnectWorksheets();
        @unlink($tmp);

        $this->assertContains('Indeks', $names);
        $this->assertContains('Ringkasan', $names);
        // Minimal satu sheet kelas di luar Indeks/Ringkasan.
        $this->assertGreaterThanOrEqual(3, count($names));
    }
}
