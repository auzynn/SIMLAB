# BAB III — PELAKSANAAN KERJA PRAKTEK (Draft Pengganti)

> Tempel per sub-bab menggantikan isi lama di file Word. Nomor tabel/gambar mengikuti pola lama (Tabel III.x, Gambar III.x) — sesuaikan ulang setelah ditempel.
> Blok kode ditulis ringkas; potongan lengkap dapat diletakkan di Lampiran.

---

## 3.1 Hasil dan Ringkasan Log Book Kegiatan

Kegiatan yang dilakukan selama kerja praktek di Laboratorium Riset Kelompok Keahlian (KK) Jaringan, Komputer, dan Forensik (JKF) ditempatkan pada bagian Backend Developer untuk melakukan perancangan dan implementasi backend Sistem Informasi Manajemen Laboratorium Riset (SIM Lab. Riset) berbasis REST API. Kegiatan dilaksanakan secara Work From Home (WFH), dengan pelaporan kemajuan dan diskusi kebutuhan dilakukan secara berkala bersama pembimbing lapangan.

Adapun seluruh kegiatan yang dilakukan selama kerja praktek secara garis besar adalah:

1. Melakukan analisis kebutuhan bersama pihak laboratorium dan menyusun dokumen kebutuhan (PRD, SRS, dan SDD).
2. Merancang arsitektur sistem, skema basis data, dan struktur REST API.
3. Menyiapkan fondasi proyek (Laravel, MySQL, autentikasi Sanctum, dan Google OAuth).
4. Mengimplementasikan modul-modul backend secara iteratif per fase: autentikasi dan manajemen user, informasi lab, peminjaman ruangan dan Kelas Lab, peminjaman dan perpanjangan perangkat, pengumpulan tugas dan rekap tugas, sertifikasi, portofolio, laporan, serta notifikasi in-app.
5. Menulis dan menjalankan pengujian fitur otomatis (feature test) untuk setiap modul yang dibangun.

## 3.2 Hasil Kerja Praktek

### 3.2.1 Brainstorming

Brainstorming merupakan teknik yang dilakukan anggota tim untuk memunculkan ide dan mencari solusi dari permasalahan yang dihadapi. Pada proses ini tim bersama pihak laboratorium mendiskusikan permasalahan administrasi laboratorium yang berjalan saat ini. Hasil diskusi tersebut dituangkan menjadi beberapa analisis, di antaranya analisis permasalahan, analisis kebutuhan aktor, dan analisis kebutuhan fungsional.

#### 3.2.1.1 Analisis Permasalahan

Administrasi Laboratorium Riset KK JKF saat ini masih dilakukan secara manual dan tersebar di berbagai media, seperti spreadsheet, pesan singkat, dan formulir cetak. Proses peminjaman ruangan, peminjaman perangkat, penjadwalan Kelas Lab/Praktikum, hingga pemantauan pengumpulan tugas mahasiswa sulit dilacak riwayatnya, rawan duplikasi data, dan berpotensi menimbulkan bentrok jadwal antara peminjaman ruangan dengan sesi Kelas Lab yang sedang berjalan. Selain itu, belum ada mekanisme persetujuan yang terdokumentasi, sehingga status sebuah pengajuan tidak dapat dipantau oleh pemohon maupun pengelola lab.

Dengan merancang dan membangun SIM Lab. Riset, seluruh proses administrasi tersebut dipusatkan ke dalam satu platform berbasis web: jadwal ketersediaan ruangan tervalidasi otomatis terhadap bentrok, setiap pengajuan melewati alur persetujuan yang jelas, pengumpulan tugas terpantau per pertemuan, dan pengelola lab dapat mengunduh rekapitulasi aktivitas laboratorium dalam bentuk PDF/Excel.

#### 3.2.1.2 Analisis Kebutuhan Aktor

Aktor merupakan pengguna yang berhubungan langsung dengan jalannya proses bisnis pada aplikasi. Aktor dalam proses bisnis SIM Lab. Riset dijelaskan pada Tabel III.1 di bawah ini.

**Tabel III.1 Analisis Kebutuhan Aktor**

| No | Aktor | Deskripsi |
|---|---|---|
| 1 | Admin (Kepala Lab) | Pengguna dengan kewenangan tertinggi; mengelola akun pengguna dan role, delegasi Asisten Lab, konten informasi lab, data master, serta menyetujui pengajuan. |
| 2 | Supervisor (Asisten Lab) | Pengguna yang mengelola operasional lab; menyetujui/menolak peminjaman ruangan dan perangkat, mengelola data master dan katalog sertifikasi, membuka Kelas Lab atas nama Dosen, serta mengunduh laporan dan rekap tugas. |
| 3 | Dosen | Pengguna yang membuka dan mengelola Kelas Lab miliknya, menetapkan materi dan deadline tugas per pertemuan, menyetujui pendaftaran peserta, serta mengelola profil dan roadmap riset pribadi. |
| 4 | Mahasiswa | Pengguna layanan lab; mengajukan peminjaman ruangan dan perangkat, mendaftar Kelas Lab, mengumpulkan tugas per pertemuan, dan mengelola portofolio pribadi. |

#### 3.2.1.3 Analisis Kebutuhan Fungsional

Berdasarkan hasil analisis aktor, dilakukan analisis kebutuhan fungsional untuk setiap aktor. Kebutuhan fungsional Admin dijelaskan pada Tabel III.2.

**Tabel III.2 Kebutuhan Fungsional Admin**

| No | Fungsi | Keterangan |
|---|---|---|
| 1 | Login Google OAuth UNSIL | Jalur utama masuk sistem sekaligus pembuatan akun pertama kali. |
| 2 | Login manual (email + password) | Alternatif; aktif setelah password diatur di halaman Profil. |
| 3 | Kelola data user & role | CRUD data user lintas role. |
| 4 | Delegasi Asisten Lab | Menetapkan Mahasiswa menjadi Supervisor dan mengembalikannya. |
| 5 | Kelola konten informasi lab | Update Beranda, Visi-Misi, Profil Kepala Lab, dan Roadmap KK. |
| 6 | Kelola data master | CRUD ruangan, perangkat, mata kuliah, dan katalog sertifikasi. |
| 7 | Approve/reject pengajuan | Peminjaman ruangan, peminjaman perangkat, dan perpanjangan. |
| 8 | Kelola Kelas Lab (semua kelas) | Buka/ubah/hapus kelas dengan menunjuk dosen pengampu + approve pendaftaran. |
| 9 | Unduh Laporan & Rekap Tugas | Laporan rekap aktivitas (PDF) dan Rekap Tugas semua kelas (PDF/Excel). |
| 10 | Kelola notifikasi in-app | Tandai baca (satu/semua) dan hapus notifikasi milik sendiri. |
| 11 | Edit profil akun pribadi | Update nama, no. telepon, foto profil, atur/ubah password. |

**Tabel III.3 Kebutuhan Fungsional Supervisor (Asisten Lab)**

| No | Fungsi | Keterangan |
|---|---|---|
| 1 | Login Google OAuth UNSIL + login manual | Sama seperti Admin. |
| 2 | Approve/reject peminjaman ruangan | Wajib memvalidasi tidak ada bentrok jadwal sebelum menyetujui. |
| 3 | Approve/reject peminjaman & perpanjangan perangkat | Konfirmasi pengembalian perangkat. |
| 4 | Kelola data master | CRUD ruangan, perangkat, mata kuliah, dan katalog sertifikasi. |
| 5 | Membuka & mengelola Kelas Lab | Membuat/ubah/hapus jadwal kelas atas nama Dosen + approve pendaftaran. |
| 6 | Kelola konten informasi lab | Setara Admin (didelegasikan melalui Gate `manage-info-lab`). |
| 7 | Unduh Laporan & Rekap Tugas | Laporan rekap aktivitas (PDF) dan Rekap Tugas semua kelas (PDF/Excel). |
| 8 | Kelola notifikasi in-app & profil pribadi | Sama seperti Admin. |

**Tabel III.4 Kebutuhan Fungsional Dosen**

| No | Fungsi | Keterangan |
|---|---|---|
| 1 | Login Google OAuth UNSIL + login manual | Akun dibuat otomatis dari email `@unsil.ac.id`. |
| 2 | Edit profil pribadi | Data diri, NIDN, jabatan fungsional, bidang minat, biografi, publikasi, roadmap riset. |
| 3 | Membuka & mengelola Kelas Lab miliknya | Tentukan mata kuliah, ruangan, jadwal mingguan, kuota, multi-sesi paralel. |
| 4 | Approve/reject pendaftaran peserta | Untuk kelas yang diampunya. |
| 5 | Menetapkan materi & deadline per pertemuan | Pertemuan 1–16; materi boleh berdiri sendiri tanpa deadline. |
| 6 | Lihat tugas masuk & unduh Rekap Tugas | Di-scope hanya untuk kelas miliknya. |
| 7 | Tambah referensi katalog sertifikasi | Hanya dapat mengubah/menghapus entri miliknya sendiri. |
| 8 | Lihat jadwal ketersediaan lab | Read-only; Dosen tidak mengajukan peminjaman ruangan. |
| 9 | Kelola notifikasi in-app | Menerima notifikasi saat ada tugas baru masuk pada kelasnya. |

**Tabel III.5 Kebutuhan Fungsional Mahasiswa**

| No | Fungsi | Keterangan |
|---|---|---|
| 1 | Registrasi akun otomatis | Akun tercipta otomatis saat login Google pertama kali dengan email `@student.unsil.ac.id`; NPM terisi otomatis dari email. |
| 2 | Login Google OAuth UNSIL + login manual | Sama seperti role lain. |
| 3 | Lihat kalender ketersediaan lab | Jadwal Kelas Lab + peminjaman yang disetujui. |
| 4 | Ajukan peminjaman ruangan | Mode satu hari/beberapa hari; slot terisi Kelas Lab tidak tersedia. |
| 5 | Mendaftar sesi Kelas Lab | Pilih sesi paralel selama kuota belum penuh; butuh persetujuan. |
| 6 | Ajukan peminjaman & perpanjangan perangkat | Perpanjangan hanya sebelum batas waktu pinjam habis. |
| 7 | Kumpulkan tugas per pertemuan | Kirim tautan hasil tugas; satu tugas per pertemuan; ditandai "Terlambat" bila lewat deadline. |
| 8 | Lihat katalog sertifikasi | Murni informasi; pendaftaran langsung ke penyelenggara. |
| 9 | Kelola portofolio pribadi | CRUD hasil riset/proyek/publikasi milik sendiri. |
| 10 | Kelola notifikasi in-app | Menerima notifikasi status pengajuan dan pengingat deadline. |

### 3.2.2 Design

Setelah kebutuhan dianalisis, tahap selanjutnya adalah perancangan yang mencakup perancangan arsitektur, perancangan basis data, dan perancangan UI/UX. Penulis berfokus pada perancangan arsitektur dan basis data sesuai penempatan pada sisi backend.

#### 3.2.2.1 Perancangan Arsitektur

SIM Lab. Riset dibangun dengan arsitektur yang memisahkan sepenuhnya antara frontend dan backend (decoupled architecture). Frontend berupa Single Page Application (SPA) menggunakan Vue 3 yang dibangun dengan Vite, sedangkan backend berupa REST API menggunakan framework Laravel dengan basis data MySQL. Keduanya berkomunikasi melalui protokol HTTP dengan format pertukaran data JSON, dan setiap request terautentikasi menggunakan token Bearer yang diterbitkan oleh Laravel Sanctum.

*(Sisipkan Gambar III.1 — Arsitektur Sistem SIM Lab. Riset; file sudah dibuatkan: `Gambar_III1_Arsitektur.svg`)*

Pemilihan arsitektur ini memiliki beberapa pertimbangan. Pertama, pemisahan frontend–backend memungkinkan tim frontend dan backend bekerja paralel tanpa saling menunggu, cukup dengan menyepakati kontrak API. Kedua, seluruh logika bisnis dan otorisasi terpusat di backend sehingga aturan hak akses tidak dapat dilewati dari sisi klien. Ketiga, REST API yang sama dapat digunakan kembali apabila di kemudian hari dikembangkan klien lain (misalnya aplikasi mobile).

Autentikasi menggunakan dua jalur: (1) Single Sign-On Google OAuth 2.0 melalui Laravel Socialite yang dibatasi hanya untuk domain email institusi (`@unsil.ac.id` untuk Dosen dan `@student.unsil.ac.id` untuk Mahasiswa) sekaligus menjadi jalur pembuatan akun otomatis; dan (2) login manual email + password sebagai alternatif setelah pengguna mengatur password di halaman Profil. Kontrol hak akses berbasis peran (RBAC) untuk empat role (Admin, Supervisor, Dosen, Mahasiswa) ditegakkan di level backend menggunakan Laravel Gate dan Policy.

#### 3.2.2.2 Perancangan Basis Data

Basis data dirancang menggunakan MySQL dengan 18 tabel utama, yaitu: `users`, `dosen`, `mahasiswa`, `bidang_minat` beserta tabel pivot `dosen_bidang_minat`, `ruangan`, `mata_kuliah`, `peminjaman_ruangan`, `kelas_lab`, `kelas_lab_peserta`, `perangkat`, `peminjaman_perangkat`, `perpanjangan_peminjaman`, `tugas`, `deadline_pertemuan`, `sertifikasi`, `portofolio`, `info_lab`, dan `notifikasi`. Seluruh perubahan skema dilakukan melalui migration Laravel agar terdokumentasi dan dapat direplikasi.

*(Sisipkan Gambar III.2 — Diagram Relasi Antar Tabel / ERD; file sudah dibuatkan: `Gambar_III2_ERD.svg`)*

Beberapa keputusan perancangan yang penting:

1. Tabel `users` menampung akun seluruh role yang dibedakan oleh kolom `role`, sedangkan data profil spesifik disimpan pada tabel `dosen` dan `mahasiswa` yang terhubung one-to-one melalui `user_id`. Kolom `npm` dan `angkatan` mahasiswa diisi otomatis dari email institusi dan bersifat immutable.
2. Jadwal Kelas Lab (`kelas_lab`) dipisahkan dari peminjaman ruangan (`peminjaman_ruangan`) karena polanya berbeda: Kelas Lab berulang mingguan sepanjang semester, sedangkan peminjaman bersifat sekali pakai pada tanggal tertentu. Keduanya tetap divalidasi silang agar tidak saling bentrok.
3. Status pengajuan (peminjaman ruangan/perangkat, pendaftaran kelas, perpanjangan) disimpan sebagai enum (`menunggu`, `disetujui`, `ditolak`, dst.) beserta kolom `disetujui_oleh` untuk jejak audit siapa yang memproses.
4. Tabel `notifikasi` menggunakan `referensi_id` lintas tabel tanpa foreign key agar satu tabel dapat merujuk entitas pemicu dari modul mana pun, dengan index komposit `(user_id, is_read)` untuk query lonceng notifikasi.

#### 3.2.2.3 Perancangan UI/UX

Perancangan antarmuka dilakukan oleh tim frontend dengan pendekatan komponen pada Vue 3. Penulis berperan menyediakan kontrak API (bentuk request dan response JSON) yang menjadi acuan tim frontend dalam membangun halaman. Konvensi response yang disepakati: response sukses selalu memuat field `data` dan `message`, error validasi mengembalikan HTTP 422 beserta rincian `errors` per field, dan error otorisasi mengembalikan HTTP 403.

*(Sisipkan Gambar III.3 — contoh tampilan halaman aplikasi, mis. kalender ketersediaan ruangan / dashboard)*

### 3.2.3 Development

#### 3.2.3.1 Lingkungan Pengembangan

Lingkungan pengembangan yang digunakan selama pembangunan backend SIM Lab. Riset dijelaskan pada Tabel III.6.

**Tabel III.6 Lingkungan Pengembangan**

| No | Perangkat | Keterangan |
|---|---|---|
| 1 | Bahasa pemrograman | PHP 8.5 |
| 2 | Framework backend | Laravel 13 (REST API) |
| 3 | Autentikasi | Laravel Sanctum (token) + Laravel Socialite (Google OAuth 2.0) |
| 4 | Basis data | MySQL (pengembangan lokal melalui Laragon) |
| 5 | Frontend (tim) | Vue 3 (Composition API), Vite, Vue Router, Pinia, Axios |
| 6 | Pembuatan dokumen | dompdf (PDF) dan PhpSpreadsheet (Excel .xlsx) |
| 7 | Pengujian | PHPUnit — feature test Laravel dengan basis data SQLite in-memory |
| 8 | Alat bantu | Visual Studio Code, Git/GitHub, Postman, Laravel Pint |

#### 3.2.3.2 Implementasi Model

Model merupakan class yang berinteraksi langsung dengan tabel pada basis data melalui Eloquent ORM. Setiap tabel memiliki satu model yang mendefinisikan kolom yang boleh diisi (`$fillable`), konversi tipe data (`casts`), dan relasi antar tabel. Sebagai contoh, model `PeminjamanRuangan` pada Tabel III.7 mendefinisikan relasi ke ruangan yang dipinjam, pengaju, serta penyetuju.

**Tabel III.7 Kode Program Model PeminjamanRuangan**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeminjamanRuangan extends Model
{
    protected $table = 'peminjaman_ruangan';

    protected $fillable = [
        'ruangan_id', 'user_id', 'tanggal', 'jam_mulai',
        'jam_selesai', 'keperluan', 'status', 'disetujui_oleh',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date:Y-m-d'];
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    // Pengaju peminjaman (Mahasiswa).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Supervisor/Admin yang menyetujui/menolak.
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
```

Model `KelasLab` pada Tabel III.8 menunjukkan pemakaian accessor Eloquent untuk menghitung sisa kuota sesi secara dinamis: slot terpakai dihitung dari pendaftar berstatus `menunggu` dan `disetujui`, sehingga kuota tidak bisa terlampaui walaupun sebagian pendaftar belum diproses.

**Tabel III.8 Kode Program Model KelasLab (potongan)**

```php
class KelasLab extends Model
{
    protected $table = 'kelas_lab';

    protected $fillable = [
        'mata_kuliah_id', 'dosen_id', 'ruangan_id', 'dibuat_oleh',
        'nama_sesi', 'hari', 'jam_mulai', 'jam_selesai',
        'tanggal_mulai_semester', 'tanggal_selesai_semester',
        'kuota', 'tautan_pengumpulan',
    ];

    // Sisa kuota = kuota - peserta yang mengisi slot (menunggu + disetujui).
    protected function sisaKuota(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->kuota
                - ($this->peserta_count
                    ?? $this->peserta()->where('status', '!=', 'ditolak')->count()),
        );
    }

    public function mataKuliah(): BelongsTo { return $this->belongsTo(MataKuliah::class); }
    public function dosen(): BelongsTo { return $this->belongsTo(Dosen::class); }
    public function ruangan(): BelongsTo { return $this->belongsTo(Ruangan::class); }
    public function peserta(): HasMany { return $this->hasMany(KelasLabPeserta::class); }
    public function deadlinePertemuan(): HasMany { return $this->hasMany(DeadlinePertemuan::class); }
}
```

#### 3.2.3.3 Implementasi View

Pada arsitektur REST API, backend tidak lagi merender halaman HTML sebagaimana pola MVC monolitik; peran "view" digantikan oleh response JSON yang dikonsumsi frontend Vue 3. Konvensi format response yang diterapkan konsisten di seluruh endpoint dijelaskan pada Tabel III.9.

**Tabel III.9 Konvensi Format Response JSON**

```json
// Sukses (HTTP 200/201)
{ "data": { ... }, "message": "Berhasil mengambil data" }

// Error validasi (HTTP 422)
{ "message": "Data tidak valid",
  "errors": { "jam_selesai": ["Jam selesai harus setelah jam mulai."] } }

// Error otorisasi (HTTP 403)
{ "message": "Anda tidak memiliki akses untuk tindakan ini" }
```

Satu-satunya pemakaian template Blade di backend adalah untuk merender dokumen PDF (laporan rekap aktivitas lab dan rekap tugas) melalui pustaka dompdf, serta pembuatan berkas Excel `.xlsx` melalui PhpSpreadsheet. Tampilan halaman aplikasi sepenuhnya diimplementasikan tim frontend sebagai komponen Vue 3 yang mengambil data dari REST API menggunakan Axios.

*(Opsional: sisipkan Gambar III.4 — tangkapan layar hasil PDF Rekap Tugas / Laporan)*

#### 3.2.3.4 Implementasi Controller

Controller merupakan bagian yang mengatur logika bisnis aplikasi dan menjadi jembatan antara request HTTP dengan Model. Setiap modul memiliki controller tersendiri, di antaranya `AuthController`, `GoogleAuthController`, `UserController`, `PeminjamanRuanganController`, `KelasLabController`, `PeminjamanPerangkatController`, `PerpanjanganController`, `TugasController`, `DeadlinePertemuanController`, `RekapTugasController`, `ReportController`, `SertifikasiController`, `PortofolioController`, `InfoLabController`, dan `NotifikasiController`.

Validasi input dipisahkan ke dalam Form Request agar controller tetap ramping. Tabel III.10 menunjukkan `StorePeminjamanRuanganRequest` yang memvalidasi pengajuan peminjaman ruangan: hanya Mahasiswa yang boleh mengajukan, jam wajib dalam rentang operasional 07.00–17.00 WIB, dan slot yang diajukan tidak boleh bentrok dengan peminjaman disetujui maupun jadwal Kelas Lab.

**Tabel III.10 Kode Program Form Request Peminjaman Ruangan (potongan)**

```php
class StorePeminjamanRuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'mahasiswa';
    }

    public function rules(): array
    {
        return [
            'ruangan_id' => ['required', 'integer', 'exists:ruangan,id'],
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam_mulai' => ['required', 'date_format:H:i', 'after_or_equal:07:00'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai',
                              'before_or_equal:17:00'],
            'keperluan' => ['required', 'string', 'max:1000'],
        ];
    }

    // Validasi bisnis: status ruangan & bentrok jadwal (UC-02).
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $bentrok = app(JadwalRuanganService::class)->peminjamanBentrok(
                (int) $this->input('ruangan_id'),
                $this->input('tanggal'),
                $this->input('jam_mulai'),
                $this->input('jam_selesai'),
            );

            if ($bentrok) {
                $validator->errors()->add('jam_mulai',
                    'Slot bentrok dengan jadwal lain pada ruangan ini.');
            }
        });
    }
}
```

Logika deteksi bentrok jadwal dipusatkan pada satu service `JadwalRuanganService` yang digunakan bersama oleh modul Peminjaman Ruangan dan Kelas Lab, sehingga validasi bentrok berlaku dua arah: pengajuan peminjaman dicek terhadap jadwal Kelas Lab, dan pembukaan Kelas Lab dicek terhadap peminjaman yang sudah disetujui.

Tabel III.11 menunjukkan potongan `PeminjamanRuanganController` pada method `store` (pengajuan) dan `approve` (persetujuan). Dua hal yang menjadi perhatian: (1) pembuatan notifikasi dilakukan dalam transaksi basis data yang sama dengan aksi pemicunya, sehingga jika salah satu gagal keduanya dibatalkan (rollback); dan (2) saat persetujuan, sistem memvalidasi ulang bentrok di dalam transaksi dengan penguncian baris (`lockForUpdate`) untuk mencegah dua approver menyetujui slot yang sama secara bersamaan (race condition).

**Tabel III.11 Kode Program PeminjamanRuanganController (potongan)**

```php
class PeminjamanRuanganController extends Controller
{
    public function __construct(
        private JadwalRuanganService $jadwal,
        private NotifikasiService $notifikasi,
    ) {}

    // Ajukan peminjaman (Mahasiswa). Validasi bentrok di Form Request.
    public function store(StorePeminjamanRuanganRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'menunggu';

        // Notifikasi ke approver dalam transaksi yang sama (UC-07).
        $peminjaman = DB::transaction(function () use ($data, $request) {
            $peminjaman = PeminjamanRuangan::create($data);

            $this->notifikasi->kirimKeApprover(
                'Pengajuan peminjaman ruangan baru',
                $request->user()->name.' mengajukan peminjaman ruangan pada '
                    .$peminjaman->tanggal->format('d-m-Y').'.',
                'pengajuan_masuk',
                $peminjaman->id,
            );

            return $peminjaman;
        });

        return response()->json([
            'data' => $peminjaman->load(['ruangan', 'user']),
            'message' => 'Pengajuan peminjaman ruangan berhasil dikirim, menunggu persetujuan.',
        ], 201);
    }

    // Setujui pengajuan (Admin/Supervisor) — validasi ulang bentrok dalam transaksi.
    public function approve(PeminjamanRuangan $peminjamanRuangan): JsonResponse
    {
        Gate::authorize('approve-peminjaman-ruangan');

        DB::transaction(function () use ($peminjamanRuangan) {
            Ruangan::whereKey($peminjamanRuangan->ruangan_id)
                ->lockForUpdate()->first();

            $bentrok = $this->jadwal->peminjamanBentrok(
                $peminjamanRuangan->ruangan_id,
                $peminjamanRuangan->tanggal,
                $peminjamanRuangan->jam_mulai,
                $peminjamanRuangan->jam_selesai,
                $peminjamanRuangan->id,
            );

            // ... jika bentrok: tandai kedaluwarsa + notifikasi;
            // jika aman: set 'disetujui' + notifikasi ke pengaju.
        });

        return response()->json([
            'data' => $peminjamanRuangan->load(['ruangan', 'user', 'penyetuju']),
            'message' => 'Pengajuan peminjaman disetujui.',
        ]);
    }
}
```

Otorisasi per role tidak dilakukan dengan pengecekan manual yang tersebar di controller, melainkan melalui Laravel Gate dan Policy (misalnya Gate `approve-peminjaman-ruangan` untuk Admin/Supervisor, Gate `view-rekap-tugas` untuk Admin/Supervisor/Dosen, dan `SertifikasiPolicy` untuk aturan kepemilikan entri sertifikasi), mengacu pada matriks RBAC yang telah dirancang.

### 3.2.4 Testing

Pengujian dilakukan dengan feature test otomatis menggunakan PHPUnit bawaan Laravel. Berbeda dengan pengujian black box manual, feature test memanggil endpoint REST API sebagaimana klien sungguhan (request HTTP + token autentikasi), kemudian memeriksa response dan perubahan data di basis data. Pendekatan ini dipilih karena dapat diulang secara otomatis setiap ada perubahan kode, sehingga regresi (fitur lama yang rusak akibat perubahan baru) langsung terdeteksi.

Setiap modul memiliki berkas pengujian tersendiri (25 berkas), di antaranya `LoginTest`, `GoogleAuthTest`, `ProfileTest`, `UserManagementTest`, `AslabTest`, `RuanganTest`, `PeminjamanRuanganTest`, `KelasLabTest`, `DeadlinePertemuanTest`, `TugasTest`, `RekapTugasTest`, `PeminjamanPerangkatTest`, `PerpanjanganTest`, `SertifikasiTest`, `PortofolioTest`, `InfoLabTest`, `NotifikasiTest`, `PengingatDeadlineTest`, `PengingatPengembalianTest`, dan `ReportTest`. Total keseluruhan **207 pengujian dengan 494 assertion dinyatakan lulus**.

Contoh skenario pengujian pada modul peminjaman ruangan dijelaskan pada Tabel III.12.

**Tabel III.12 Contoh Skenario Feature Test Modul Peminjaman Ruangan**

| No | Skenario | Hasil yang Diharapkan | Hasil | Keterangan |
|---|---|---|---|---|
| 1 | Mahasiswa mengajukan peminjaman pada slot kosong | HTTP 201, data tersimpan berstatus `menunggu`, notifikasi terkirim ke approver | Sesuai | Valid |
| 2 | Mahasiswa mengajukan peminjaman pada slot yang bentrok dengan jadwal Kelas Lab | HTTP 422, pesan error bentrok jadwal | Sesuai | Valid |
| 3 | Mahasiswa mengajukan peminjaman di luar jam operasional (mis. 18.00) | HTTP 422, pesan error validasi jam | Sesuai | Valid |
| 4 | Dosen mencoba mengajukan peminjaman ruangan | HTTP 403, pengajuan ditolak (hanya Mahasiswa) | Sesuai | Valid |
| 5 | Supervisor menyetujui pengajuan yang masih valid | HTTP 200, status `disetujui`, notifikasi ke pengaju | Sesuai | Valid |
| 6 | Supervisor menyetujui pengajuan yang slotnya sudah terisi | HTTP 422, status otomatis `kadaluarsa`, notifikasi ke pengaju | Sesuai | Valid |
| 7 | Mahasiswa membatalkan pengajuan miliknya yang masih `menunggu` | HTTP 200, data terhapus | Sesuai | Valid |

*(Sisipkan Gambar III.5 — tangkapan layar hasil eksekusi `php artisan test`: 207 passed, 494 assertions)*
