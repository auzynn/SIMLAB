<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KelasLab;
use App\Models\KelasLabPeserta;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeminjamanRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Kelas Lab/Praktikum: pembukaan, otorisasi, bentrok, dan pendaftaran peserta (SRS UC-02a, 3_SDD.md 3.7/3.8).
 */
class KelasLabTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Dosen} */
    private function dosen(): array
    {
        $user = User::factory()->create(['role' => 'dosen']);

        return [$user, Dosen::create(['user_id' => $user->id])];
    }

    /** @return array{0: User, 1: Mahasiswa} */
    private function mahasiswa(string $npm): array
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);

        return [$user, Mahasiswa::create(['user_id' => $user->id, 'npm' => $npm, 'angkatan' => '2022'])];
    }

    private function ruangan(): Ruangan
    {
        return Ruangan::create(['nama_ruangan' => 'Lab A', 'status' => 'tersedia']);
    }

    private function mataKuliah(): MataKuliah
    {
        return MataKuliah::create(['kode_mk' => 'JKF301', 'nama_mk' => 'Praktikum Jaringan', 'sks' => 3]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'mata_kuliah_id' => $this->mataKuliah()->id,
            'ruangan_id' => $this->ruangan()->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ], $override);
    }

    public function test_dosen_dapat_membuka_kelas_untuk_dirinya(): void
    {
        [$user, $dosen] = $this->dosen();
        Sanctum::actingAs($user);

        $this->postJson('/api/kelas-lab', $this->payload())->assertCreated();

        $this->assertDatabaseHas('kelas_lab', ['dosen_id' => $dosen->id, 'dibuat_oleh' => $user->id]);
    }

    public function test_admin_tidak_dapat_membuka_kelas(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/kelas-lab', $this->payload(['dosen_id' => $this->dosen()[1]->id]))
            ->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_membuka_kelas(): void
    {
        [$user] = $this->mahasiswa('220001');
        Sanctum::actingAs($user);

        $this->postJson('/api/kelas-lab', $this->payload(['dosen_id' => $this->dosen()[1]->id]))
            ->assertForbidden();
    }

    public function test_supervisor_dapat_membuka_kelas_atas_nama_dosen(): void
    {
        $dosen = $this->dosen()[1];
        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));

        $this->postJson('/api/kelas-lab', $this->payload(['dosen_id' => $dosen->id]))->assertCreated();

        $this->assertDatabaseHas('kelas_lab', ['dosen_id' => $dosen->id]);
    }

    public function test_dosen_hanya_membuka_kelas_untuk_dirinya_meski_kirim_dosen_id_lain(): void
    {
        [$user, $dosenSendiri] = $this->dosen();
        $dosenLain = $this->dosen()[1];
        Sanctum::actingAs($user);

        $this->postJson('/api/kelas-lab', $this->payload(['dosen_id' => $dosenLain->id]))->assertCreated();

        // dosen_id dipaksa ke milik sendiri, bukan yang dikirim
        $this->assertDatabaseHas('kelas_lab', ['dosen_id' => $dosenSendiri->id]);
        $this->assertDatabaseMissing('kelas_lab', ['dosen_id' => $dosenLain->id]);
    }

    public function test_pembukaan_kelas_bentrok_dengan_kelas_lain_ditolak(): void
    {
        [$user, $dosen] = $this->dosen();
        $ruangan = $this->ruangan();
        $mk = $this->mataKuliah();

        KelasLab::create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $ruangan->id,
            'dibuat_oleh' => $user->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/kelas-lab', [
            'mata_kuliah_id' => $mk->id,
            'ruangan_id' => $ruangan->id,
            'nama_sesi' => 'Kelas B',
            'hari' => 'senin',
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ])->assertStatus(422);
    }

    public function test_pembukaan_kelas_bentrok_dengan_peminjaman_disetujui_ditolak(): void
    {
        [$user, $dosen] = $this->dosen();
        $ruangan = $this->ruangan();
        $mk = $this->mataKuliah();
        $senin = Carbon::today()->next(Carbon::MONDAY)->format('Y-m-d');

        PeminjamanRuangan::create([
            'ruangan_id' => $ruangan->id,
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'tanggal' => $senin,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan' => 'x',
            'status' => 'disetujui',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/kelas-lab', [
            'mata_kuliah_id' => $mk->id,
            'ruangan_id' => $ruangan->id,
            'dosen_id' => $dosen->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ])->assertStatus(422);
    }

    public function test_pendaftaran_melebihi_kuota_ditolak(): void
    {
        [$dosenUser, $dosen] = $this->dosen();
        $kelas = KelasLab::create([
            'mata_kuliah_id' => $this->mataKuliah()->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $this->ruangan()->id,
            'dibuat_oleh' => $dosenUser->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 1,
        ]);

        [, $m1] = $this->mahasiswa('220001');
        KelasLabPeserta::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $m1->id]);

        [$u2] = $this->mahasiswa('220002');
        Sanctum::actingAs($u2);

        $this->postJson("/api/kelas-lab/{$kelas->id}/daftar")->assertStatus(422);
    }

    public function test_mahasiswa_tidak_bisa_mendaftar_dua_kali(): void
    {
        [$dosenUser, $dosen] = $this->dosen();
        $kelas = KelasLab::create([
            'mata_kuliah_id' => $this->mataKuliah()->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $this->ruangan()->id,
            'dibuat_oleh' => $dosenUser->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ]);

        [$u] = $this->mahasiswa('220003');
        Sanctum::actingAs($u);

        $this->postJson("/api/kelas-lab/{$kelas->id}/daftar")->assertCreated();
        $this->postJson("/api/kelas-lab/{$kelas->id}/daftar")->assertStatus(422);

        $this->assertDatabaseCount('kelas_lab_peserta', 1);
    }

    public function test_aturan_pendaftaran_per_mata_kuliah_dan_bentrok(): void
    {
        [$dosenUser, $dosen] = $this->dosen();
        $ruangan = $this->ruangan();
        $semester = [
            'dosen_id' => $dosen->id,
            'ruangan_id' => $ruangan->id,
            'dibuat_oleh' => $dosenUser->id,
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ];
        $mkA = MataKuliah::create(['kode_mk' => 'MKA', 'nama_mk' => 'Praktikum A']);
        $mkB = MataKuliah::create(['kode_mk' => 'MKB', 'nama_mk' => 'Praktikum B']);
        $mkC = MataKuliah::create(['kode_mk' => 'MKC', 'nama_mk' => 'Praktikum C']);

        // Praktikum A: Kelas A (Senin 08–10) & Kelas B (Rabu 10–12)
        $aKelasA = KelasLab::create(array_merge($semester, ['mata_kuliah_id' => $mkA->id, 'nama_sesi' => 'Kelas A', 'hari' => 'senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00']));
        $aKelasB = KelasLab::create(array_merge($semester, ['mata_kuliah_id' => $mkA->id, 'nama_sesi' => 'Kelas B', 'hari' => 'rabu', 'jam_mulai' => '10:00:00', 'jam_selesai' => '12:00:00']));
        // Praktikum B: Selasa 13–15 (tidak bentrok)
        $bKelasA = KelasLab::create(array_merge($semester, ['mata_kuliah_id' => $mkB->id, 'nama_sesi' => 'Kelas A', 'hari' => 'selasa', 'jam_mulai' => '13:00:00', 'jam_selesai' => '15:00:00']));
        // Praktikum C: Senin 09–11 (bentrok dengan Praktikum A Kelas A)
        $cKelasA = KelasLab::create(array_merge($semester, ['mata_kuliah_id' => $mkC->id, 'nama_sesi' => 'Kelas A', 'hari' => 'senin', 'jam_mulai' => '09:00:00', 'jam_selesai' => '11:00:00']));

        [$u] = $this->mahasiswa('220010');
        Sanctum::actingAs($u);

        // Ambil Praktikum A Kelas A
        $this->postJson("/api/kelas-lab/{$aKelasA->id}/daftar")->assertCreated();
        // Sesi lain mata kuliah yang sama → ditolak
        $this->postJson("/api/kelas-lab/{$aKelasB->id}/daftar")->assertStatus(422);
        // Mata kuliah berbeda yang tidak bentrok → diterima
        $this->postJson("/api/kelas-lab/{$bKelasA->id}/daftar")->assertCreated();
        // Mata kuliah berbeda tapi bentrok jadwal → ditolak
        $this->postJson("/api/kelas-lab/{$cKelasA->id}/daftar")->assertStatus(422);

        $this->assertDatabaseCount('kelas_lab_peserta', 2);
    }

    public function test_pendaftaran_berstatus_menunggu_dan_disetujui_pemilik_kelas(): void
    {
        [$dosenUser, $dosen] = $this->dosen();
        $kelas = KelasLab::create(array_merge($this->payload(), ['dosen_id' => $dosen->id, 'dibuat_oleh' => $dosenUser->id]));

        [$mhsUser, $mhs] = $this->mahasiswa('220020');
        Sanctum::actingAs($mhsUser);
        $this->postJson("/api/kelas-lab/{$kelas->id}/daftar")->assertCreated();
        $this->assertDatabaseHas('kelas_lab_peserta', ['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $mhs->id, 'status' => 'menunggu']);

        $peserta = KelasLabPeserta::where('kelas_lab_id', $kelas->id)->where('mahasiswa_id', $mhs->id)->first();

        // Dosen lain tidak boleh menyetujui
        [$dosenLainUser] = $this->dosen();
        Sanctum::actingAs($dosenLainUser);
        $this->patchJson("/api/kelas-lab/pendaftaran/{$peserta->id}/approve")->assertForbidden();

        // Pemilik kelas menyetujui
        Sanctum::actingAs($dosenUser);
        $this->patchJson("/api/kelas-lab/pendaftaran/{$peserta->id}/approve")->assertOk();
        $this->assertDatabaseHas('kelas_lab_peserta', ['id' => $peserta->id, 'status' => 'disetujui', 'disetujui_oleh' => $dosenUser->id]);
    }

    public function test_pendaftaran_ditolak_boleh_diajukan_ulang(): void
    {
        [$dosenUser, $dosen] = $this->dosen();
        $kelas = KelasLab::create(array_merge($this->payload(), ['dosen_id' => $dosen->id, 'dibuat_oleh' => $dosenUser->id]));

        [$mhsUser] = $this->mahasiswa('220021');
        Sanctum::actingAs($mhsUser);
        $this->postJson("/api/kelas-lab/{$kelas->id}/daftar")->assertCreated();
        $peserta = KelasLabPeserta::where('kelas_lab_id', $kelas->id)->first();

        Sanctum::actingAs($dosenUser);
        $this->patchJson("/api/kelas-lab/pendaftaran/{$peserta->id}/reject")->assertOk();

        // Setelah ditolak, mahasiswa boleh ajukan ulang (baris di-set menunggu kembali)
        Sanctum::actingAs($mhsUser);
        $this->postJson("/api/kelas-lab/{$kelas->id}/daftar")->assertCreated();
        $this->assertDatabaseHas('kelas_lab_peserta', ['id' => $peserta->id, 'status' => 'menunggu']);
        $this->assertDatabaseCount('kelas_lab_peserta', 1);
    }

    public function test_dosen_hanya_melihat_pendaftaran_kelasnya(): void
    {
        [$dosenAUser, $dosenA] = $this->dosen();
        [$dosenBUser, $dosenB] = $this->dosen();
        $base = [
            'mata_kuliah_id' => $this->mataKuliah()->id,
            'ruangan_id' => $this->ruangan()->id,
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kuota' => 30,
        ];
        $kelasA = KelasLab::create(array_merge($base, ['nama_sesi' => 'Kelas A', 'hari' => 'senin', 'dosen_id' => $dosenA->id, 'dibuat_oleh' => $dosenAUser->id]));
        $kelasB = KelasLab::create(array_merge($base, ['nama_sesi' => 'Kelas B', 'hari' => 'rabu', 'dosen_id' => $dosenB->id, 'dibuat_oleh' => $dosenBUser->id]));

        [, $m1] = $this->mahasiswa('220022');
        [, $m2] = $this->mahasiswa('220023');
        KelasLabPeserta::create(['kelas_lab_id' => $kelasA->id, 'mahasiswa_id' => $m1->id, 'status' => 'menunggu']);
        KelasLabPeserta::create(['kelas_lab_id' => $kelasB->id, 'mahasiswa_id' => $m2->id, 'status' => 'menunggu']);

        Sanctum::actingAs($dosenAUser);
        $this->getJson('/api/kelas-lab/pendaftaran')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs(User::factory()->create(['role' => 'supervisor']));
        $this->getJson('/api/kelas-lab/pendaftaran')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_detail_kelas_menyertakan_sisa_kuota(): void
    {
        [$dosenUser, $dosen] = $this->dosen();
        $kelas = KelasLab::create([
            'mata_kuliah_id' => $this->mataKuliah()->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $this->ruangan()->id,
            'dibuat_oleh' => $dosenUser->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ]);
        [, $m] = $this->mahasiswa('220004');
        KelasLabPeserta::create(['kelas_lab_id' => $kelas->id, 'mahasiswa_id' => $m->id]);

        Sanctum::actingAs(User::factory()->create(['role' => 'mahasiswa']));

        $this->getJson("/api/kelas-lab/{$kelas->id}")
            ->assertOk()
            ->assertJsonPath('data.sisa_kuota', 29);
    }

    public function test_pemilik_kelas_dapat_melihat_peserta_dosen_lain_tidak(): void
    {
        [$dosenUser, $dosen] = $this->dosen();
        $kelas = KelasLab::create([
            'mata_kuliah_id' => $this->mataKuliah()->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $this->ruangan()->id,
            'dibuat_oleh' => $dosenUser->id,
            'nama_sesi' => 'Kelas A',
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tanggal_mulai_semester' => Carbon::today()->format('Y-m-d'),
            'tanggal_selesai_semester' => Carbon::today()->addDays(120)->format('Y-m-d'),
            'kuota' => 30,
        ]);

        Sanctum::actingAs($dosenUser);
        $this->getJson("/api/kelas-lab/{$kelas->id}/peserta")->assertOk();

        [$dosenLainUser] = $this->dosen();
        Sanctum::actingAs($dosenLainUser);
        $this->getJson("/api/kelas-lab/{$kelas->id}/peserta")->assertForbidden();
    }
}
