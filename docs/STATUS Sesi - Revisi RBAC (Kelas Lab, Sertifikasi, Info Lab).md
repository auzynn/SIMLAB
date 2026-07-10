# STATUS Serah-Terima Sesi — Revisi Matrix RBAC (3 Penyesuaian Wewenang)

**Produk**: SIM Lab. Riset KK JKF · **Branch**: `main` · **Per**: 2026-07-10
**Acuan**: `1_PRD.md`, `2_SRS.md` (v1.2), `3_SDD.md`, `docs/final/*`, `.clinerules/agent.md`

> **Konteks besar**: Seluruh **Fase 0–9 sudah tuntas**. Sesi ini **bukan fase baru** — hanya **penyesuaian wewenang RBAC** atas 3 modul, diminta pemilik karena dinilai lebih sesuai operasional. Backend (Gate/Policy) adalah sumber kebenaran; frontend & dokumen diselaraskan.
>
> **Semua verifikasi hijau**: backend **198/198 test lulus** (479 assertions). **Belum ada commit** — perubahan ada di working tree.

---

## 1. Tiga perubahan wewenang (inti sesi)

Legenda: C=Create, R=Read, U=Update, D=Delete.

| Modul | SEBELUM | SESUDAH |
|---|---|---|
| **Kelas Lab/Praktikum** — Admin | R saja (tak bisa buka kelas) | **CRUD semua kelas + approve/reject pendaftaran** (menunjuk dosen pengampu saat buka) |
| **Sertifikasi (katalog)** — Dosen | – (hanya R) | **C + U/D milik sendiri** (`created_by`) |
| **Halaman Informasi Lab** — Supervisor | R | **CRUD (kelola konten)** |

**Keputusan penting (dari sesi tanya-jawab):**
- Admin "seperti Dosen" **tidak harfiah** — Admin bukan `dosen`, jadi ia mengelola SEMUA kelas dengan **menunjuk `dosen_id` pengampu** (mekanisme sama seperti Supervisor). Dosen tetap dibatasi kelas miliknya.
- Sertifikasi Dosen **dibatasi "milik sendiri"** (bukan CRUD penuh bersama) agar dosen tak bisa mengubah/menghapus entri admin/dosen lain → butuh kolom pemilik `created_by`.
- Info Lab = halaman ber-enum tetap (beranda/visi_misi/kepala_lab/roadmap_kk) yang di-*upsert*, jadi "CRUD" praktis = kelola/Update konten + upload lampiran.

---

## 2. Perubahan Backend (`src/backend`) — sumber kebenaran RBAC

### Kelas Lab — Admin hak akses penuh
- `app/Policies/KelasLabPolicy.php`: `create` kini `['admin','dosen','supervisor']`; `update`/`delete` kini `['admin','supervisor']` (semua kelas) ATAU dosen pemilik (`$user->dosen?->id === $kelasLab->dosen_id`). `viewPeserta` sudah memuat admin sejak awal.
- `app/Http/Controllers/KelasLabController.php`: `pendaftaran()` `abort_unless` kini memuat `admin`. Approve/reject/hapus peserta otomatis ikut karena `authorizePendaftaran()` memakai policy `update` (yang kini mengizinkan admin).
- `app/Http/Requests/StoreKelasLabRequest.php`: **tidak berubah logika** — `prepareForValidation()` hanya memaksa `dosen_id` untuk role `dosen`; Admin (seperti Supervisor) mengirim `dosen_id` (sudah `required`). Hanya komentar diperbarui.

### Sertifikasi — Dosen CRUD milik sendiri
- **Migration baru**: `database/migrations/2026_07_10_000001_add_created_by_to_sertifikasi_table.php` — kolom `created_by` (nullable FK `users`, `nullOnDelete`). **Sudah di-`migrate`.** Entri lama `created_by = NULL` (hanya Admin/Supervisor yang bisa ubah).
- `app/Models/Sertifikasi.php`: `created_by` di `$fillable` + relasi `creator(): BelongsTo`.
- **Policy baru** `app/Policies/SertifikasiPolicy.php` (auto-discovery): `create` = admin/supervisor/dosen; `update`/`delete` = admin/supervisor (semua) ATAU dosen pemilik (`$user->id === $sertifikasi->created_by`).
- `app/Http/Controllers/SertifikasiController.php`: `Gate::authorize('manage-master-data')` diganti `Gate::authorize('create'|'update'|'delete', ...)` via policy; `store()` set `created_by = $request->user()->id`.

### Info Lab — Supervisor CRUD
- `app/Providers/AppServiceProvider.php`: Gate `manage-info-lab` dari `admin` saja → `['admin','supervisor']`. Ini otomatis mencakup `UpdateInfoLabRequest::authorize()` & `InfoLabController::uploadLampiran`. Tidak ada perubahan route.

---

## 3. Perubahan Frontend (`src/frontend/app-labriset/src`)

### Kelas Lab
- `router/index.js`: `kelola-kelas-lab` & `persetujuan-kelas-lab` `meta.roles` +`admin`.
- `views/kelas-lab.vue`: `bisaKelola` +`admin` (tampilkan tombol Kelola & Persetujuan).
- `views/kelola-kelas-lab.vue`: konsep `isSupervisor` → **`kelolaSemua = ['admin','supervisor']`** (lihat semua kelas, dropdown pilih dosen pengampu, kirim `dosen_id`). Dosen tetap dipaksa dirinya di backend.
- `views/persetujuan-kelas-lab.vue`: `isSupervisor` → `lihatSemua = ['admin','supervisor']` (label "seluruh kelas").

### Sertifikasi
- **Kontrol kelola milik-sendiri ditaruh di halaman katalog publik `views/sertifikasi.vue`** (BUKAN `admin-sertifikasi.vue`), karena `admin-sertifikasi.vue` memuat `SidemenuAdmin` (menu admin-only, tak cocok untuk Dosen). Dosen mengakses via menu navbar **"Layanan Akademik → Sertifikasi"** yang sudah ada. Tombol "+ Tambah" untuk admin/supervisor/dosen; Edit/Hapus per-kartu digerbang `bisaKelola(s)` (admin/supervisor: semua; dosen: `s.created_by === auth.user.id`).
- `router/index.js`: `admin-sertifikasi` `meta.roles` tetap `['admin','supervisor']` (panel admin/supervisor tak berubah; Dosen tak masuk panel).

### Info Lab & akses Panel untuk Supervisor
- **Masalah yang ditemukan**: Supervisor sebelumnya **tak punya jalur UI ke panel `/admin/*`** sama sekali (dashboard `/admin` `roles:['admin']`, link "Panel Admin" `role==='admin'`). Padahal Supervisor sudah boleh akses `/admin/data-master` & `/admin/sertifikasi`.
- **Solusi (panel dibuat role-aware):**
  - `router/index.js`: `/admin` `roles` → `['admin','supervisor']`; `admin-info-lab` → `['admin','supervisor']`.
  - `views/admin-page.vue`: array `areas` diberi field `roles`; render `areasTampil` (filter per role). Supervisor lihat: Konten Info Lab, Data Master, Persetujuan Peminjaman, Katalog Sertifikasi, Laporan. Admin lihat semua.
  - `components/sidemenu-admin.vue`: menu statis → data-driven `menu[]` dengan `roles`, render `menuTampil` (filter per role).
  - `components/header-component.vue`: link "Panel Admin" (`role==='admin'`) → **"Panel Kelola"** dengan `bisaPanel = ['admin','supervisor']`.

---

## 4. Dokumen yang diselaraskan

- `docs/2_SRS.md` — **naik ke v1.2**, changelog ditambah; matrix Bagian 1 (3 baris), aturan implementasi (+`created_by`), Bagian 2 (F-AD-13 Kelas Lab, F-SV-09 diperbarui, F-SV-12 Info Lab, F-DS-09 Sertifikasi), catatan UC-02a.
- `docs/final/2_SRS_FINAL.md` — cerminan perubahan yang sama.
- `docs/3_SDD.md` & `docs/final/3_SDD_FINAL.md` — tabel endpoint kelas-lab, sertifikasi, info-lab.
- `docs/1_PRD.md` & `docs/final/1_PRD_FINAL.md` — §2.4 Admin (hapus "pengecualian Admin tak bisa kelola Kelas Lab"), §3.3a pembukaan kelas.
- Docblock kode terkait (Policy/Controller/FormRequest/Model) & komentar `routes/api.php`.

---

## 5. Test backend (`src/backend/tests/Feature`)

- `KelasLabTest.php`: `test_admin_tidak_dapat_membuka_kelas` (403) **diganti** → `test_admin_dapat_membuka_kelas_atas_nama_dosen`; +`test_admin_dapat_mengubah_dan_menghapus_kelas_dosen_lain`, +`test_admin_dapat_menyetujui_pendaftaran_kelas_dosen_lain`. (Catatan: saat menulis, awalnya `payload()` dipanggil dobel → langgar unique `kode_mk`; fix: bangun payload sekali & pakai ulang.)
- `InfoLabTest.php`: +`test_supervisor_dapat_memperbarui_konten`; `test_non_admin_ditolak...` → `test_dosen_dan_mahasiswa_ditolak_memperbarui_konten`.
- `SertifikasiTest.php`: +`test_dosen_dapat_menambah_dan_mengelola_sertifikasi_miliknya`, +`test_dosen_tidak_dapat_mengelola_sertifikasi_milik_orang_lain`; `test_admin_dapat_menambah...` assert `created_by`.

---

## 6. Verifikasi (semua hijau)

1. `php artisan migrate` — kolom `sertifikasi.created_by` terbuat.
2. **Suite penuh backend: 198/198 lulus** (479 assertions, ~8s).
3. Runtime Gate/Policy diuji terpisah (22/22 OK) sebelum suite dipulihkan.

> **Kendala vendor (teratasi):** dependency dev `sebastian/*` & phpunit korup (Defender) sehingga `php artisan test` mati (`Class ...CliParser\Parser not found`). Pulih dengan `rm -rf vendor && composer install --no-cache -o`, lalu `composer dump-autoload -o` (Defender sempat mengarantina `vendor/autoload.php` yang baru). Resep lengkap ada di memory `composer-av-blocks-zips`. **Frontend belum di-`vite build` sesi ini** — perubahan Vue murni penyesuaian role guard/tampilan, disarankan build+smoke test per role sebelum rilis.

---

## 7. Ringkas: yang MASIH konsisten (tidak berubah)

- Mahasiswa: tetap tak bisa buka kelas / CUD sertifikasi / update info-lab (semua 403).
- Dosen: tetap tak bisa update info-lab; akses tugas tetap by `dosen_id` kelas.
- Read data master/sertifikasi/katalog tetap terbuka semua role login.
- Tidak ada perubahan pada modul Peminjaman, Tugas, Portofolio, Notifikasi, Report, Rekap Tugas.
