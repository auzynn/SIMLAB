<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KelasLab;
use App\Models\KelasLabPeserta;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pengumpulan Tugas (menggantikan Presensi). Mahasiswa kirim tautan tugas untuk Kelas Lab
 * yang diikutinya (peserta disetujui); Dosen kelas / Admin / Supervisor melihat & membuka.
 */
class TugasTest extends TestCase
{
    use RefreshDatabase;

    private function mahasiswa(): User
    {
        return User::factory()->create(['role' => 'mahasiswa']);
    }

    private function profilMahasiswa(User $user): Mahasiswa
    {
        return Mahasiswa::create([
            'user_id' => $user->id,
            'npm' => (string) random_int(1000000, 9999999),
            'angkatan' => '2022',
        ]);
    }

    /** Buat sesi Kelas Lab beserta dosen pengampunya. */
    private function buatKelas(?User $dosenUser = null): KelasLab
    {
        $dosenUser ??= User::factory()->create(['role' => 'dosen']);
        // firstOrCreate agar tak menyentuh cache relasi $dosenUser->dosen (yang dipakai controller saat actingAs).
        $dosen = Dosen::firstOrCreate(['user_id' => $dosenUser->id]);
        $mk = MataKuliah::create(['nama_mk' => 'Praktikum Jaringan Komputer']);
        $ruangan = Ruangan::create(['nama_ruangan' => 'Lab. Jaringan Komputer']);

        return KelasLab::create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $ruangan->id,
            'dibuat_oleh' => $dosenUser->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tanggal_mulai_semester' => '2026-06-01',
            'tanggal_selesai_semester' => '2026-12-31',
            'kuota' => 30,
        ]);
    }

    private function daftarkan(KelasLab $kelas, Mahasiswa $mahasiswa, string $status = 'disetujui'): KelasLabPeserta
    {
        return KelasLabPeserta::create([
            'kelas_lab_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status,
        ]);
    }

    private function payload(KelasLab $kelas, int $pertemuan = 1): array
    {
        return [
            'kelas_lab_id' => $kelas->id,
            'pertemuan' => $pertemuan,
            'judul' => 'Tugas 1 — Analisis Jaringan',
            'tautan' => 'https://drive.google.com/file/abc',
        ];
    }

    public function test_mahasiswa_peserta_disetujui_dapat_kirim_tugas(): void
    {
        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();
        $this->daftarkan($kelas, $mahasiswa);

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas))->assertCreated();

        $this->assertDatabaseHas('tugas', [
            'kelas_lab_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'pertemuan' => 1,
            'judul' => 'Tugas 1 — Analisis Jaringan',
        ]);
    }

    public function test_pertemuan_tersimpan_sesuai_input(): void
    {
        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();
        $this->daftarkan($kelas, $mahasiswa);

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas, 7))->assertCreated();

        $this->assertDatabaseHas('tugas', ['kelas_lab_id' => $kelas->id, 'pertemuan' => 7]);
    }

    public function test_pertemuan_di_luar_rentang_ditolak(): void
    {
        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();
        $this->daftarkan($kelas, $mahasiswa);

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas, 17))->assertStatus(422);
        $this->postJson('/api/tugas', $this->payload($kelas, 0))->assertStatus(422);

        $this->assertDatabaseCount('tugas', 0);
    }

    public function test_dua_tugas_pertemuan_sama_ditolak(): void
    {
        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();
        $this->daftarkan($kelas, $mahasiswa);

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas, 3))->assertCreated();
        $this->postJson('/api/tugas', $this->payload($kelas, 3))->assertStatus(422);

        $this->assertDatabaseCount('tugas', 1);
    }

    public function test_pertemuan_berbeda_pada_kelas_sama_diterima(): void
    {
        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();
        $this->daftarkan($kelas, $mahasiswa);

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas, 3))->assertCreated();
        $this->postJson('/api/tugas', $this->payload($kelas, 4))->assertCreated();

        $this->assertDatabaseCount('tugas', 2);
    }

    public function test_kirim_tugas_memberi_notifikasi_ke_dosen_pengampu(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelas = $this->buatKelas($dosenUser);

        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $this->daftarkan($kelas, $mahasiswa);

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas))->assertCreated();

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $dosenUser->id,
            'tipe' => 'pengajuan_masuk',
        ]);
    }

    public function test_kirim_tugas_memberi_notifikasi_ke_supervisor(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $kelas = $this->buatKelas();

        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $this->daftarkan($kelas, $mahasiswa);

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas))->assertCreated();

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $supervisor->id,
            'tipe' => 'pengajuan_masuk',
        ]);
    }

    public function test_bukan_peserta_ditolak(): void
    {
        $user = $this->mahasiswa();
        $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas))->assertStatus(422);

        $this->assertDatabaseCount('tugas', 0);
    }

    public function test_peserta_menunggu_ditolak(): void
    {
        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();
        $this->daftarkan($kelas, $mahasiswa, 'menunggu');

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', $this->payload($kelas))->assertStatus(422);
    }

    public function test_tautan_bukan_url_ditolak(): void
    {
        $user = $this->mahasiswa();
        $mahasiswa = $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();
        $this->daftarkan($kelas, $mahasiswa);

        Sanctum::actingAs($user);
        $this->postJson('/api/tugas', ['kelas_lab_id' => $kelas->id, 'judul' => 'X', 'tautan' => 'bukan-url'])
            ->assertStatus(422);
    }

    public function test_non_mahasiswa_tidak_dapat_kirim_tugas(): void
    {
        $kelas = $this->buatKelas();

        Sanctum::actingAs(User::factory()->create(['role' => 'dosen']));
        $this->postJson('/api/tugas', $this->payload($kelas))->assertForbidden();
    }

    public function test_mahasiswa_hanya_melihat_tugas_sendiri(): void
    {
        $kelas = $this->buatKelas();

        $saya = $this->mahasiswa();
        $mSaya = $this->profilMahasiswa($saya);
        $this->daftarkan($kelas, $mSaya);
        Tugas::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $mSaya->id, 'judul' => 'A', 'tautan' => 'https://a.test']);

        $lain = $this->mahasiswa();
        $mLain = $this->profilMahasiswa($lain);
        Tugas::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $mLain->id, 'judul' => 'B', 'tautan' => 'https://b.test']);

        Sanctum::actingAs($saya);
        $this->getJson('/api/tugas')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_dosen_hanya_melihat_tugas_kelas_yang_diampu(): void
    {
        $dosenUser = User::factory()->create(['role' => 'dosen']);
        $kelasDia = $this->buatKelas($dosenUser);
        $kelasLain = $this->buatKelas();

        $mhs = $this->mahasiswa();
        $m = $this->profilMahasiswa($mhs);
        Tugas::create(['kelas_lab_id' => $kelasDia->id, 'mahasiswa_id' => $m->id, 'judul' => 'A', 'tautan' => 'https://a.test']);
        Tugas::create(['kelas_lab_id' => $kelasLain->id, 'mahasiswa_id' => $m->id, 'judul' => 'B', 'tautan' => 'https://b.test']);

        Sanctum::actingAs($dosenUser);
        $this->getJson('/api/tugas')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_supervisor_melihat_semua_tugas(): void
    {
        $kelas = $this->buatKelas();
        $m = $this->profilMahasiswa($this->mahasiswa());
        Tugas::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $m->id, 'judul' => 'A', 'tautan' => 'https://a.test']);
        Tugas::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $m->id, 'judul' => 'B', 'tautan' => 'https://b.test']);

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->getJson('/api/tugas')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_pemilik_dapat_menghapus_tugasnya(): void
    {
        $user = $this->mahasiswa();
        $m = $this->profilMahasiswa($user);
        $kelas = $this->buatKelas();
        $tugas = Tugas::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $m->id, 'judul' => 'A', 'tautan' => 'https://a.test']);

        Sanctum::actingAs($user);
        $this->deleteJson("/api/tugas/{$tugas->id}")->assertOk();

        $this->assertDatabaseMissing('tugas', ['id' => $tugas->id]);
    }

    public function test_mahasiswa_lain_tidak_dapat_menghapus_tugas(): void
    {
        $kelas = $this->buatKelas();
        $pemilik = $this->profilMahasiswa($this->mahasiswa());
        $tugas = Tugas::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $pemilik->id, 'judul' => 'A', 'tautan' => 'https://a.test']);

        $lain = $this->mahasiswa();
        $this->profilMahasiswa($lain);

        Sanctum::actingAs($lain);
        $this->deleteJson("/api/tugas/{$tugas->id}")->assertForbidden();

        $this->assertDatabaseHas('tugas', ['id' => $tugas->id]);
    }

    public function test_supervisor_dapat_menghapus_tugas(): void
    {
        $kelas = $this->buatKelas();
        $m = $this->profilMahasiswa($this->mahasiswa());
        $tugas = Tugas::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $m->id, 'judul' => 'A', 'tautan' => 'https://a.test']);

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->deleteJson("/api/tugas/{$tugas->id}")->assertOk();

        $this->assertDatabaseMissing('tugas', ['id' => $tugas->id]);
    }
}
