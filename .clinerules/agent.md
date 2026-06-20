# agent.md — Aturan Inti AI Agent

> File ini dibaca otomatis oleh **Hermes** (AI Agent eksekusi) dan **"Mandor"**
> (Roo Code / Kilo / Copilot di VS Code). Tujuannya: memastikan SEMUA AI tool
> bekerja dengan konteks, batasan, dan standar kerja yang SAMA sepanjang
> proyek magang ini — tidak ada AI yang menebak-nebak sendiri.

---

## 1. IDENTITAS PROYEK

- **Nama Proyek**: Nama-Proyek-Magang
- **Jenis**: Aplikasi web — REST API (backend) + SPA terpisah (frontend)
- **Arsitektur**: Backend dan frontend terpisah penuh, berkomunikasi lewat HTTP/JSON (bukan Inertia, bukan SSR)

### Stack Teknologi
| Layer | Teknologi | Versi |
|---|---|---|
| Backend Framework | Laravel | 13.16 |
| Bahasa Backend | PHP | 8.5.7 |
| Database | MySQL | - |
| Frontend Framework | Vue.js (SPA) | 3.x |
| Build Tool Frontend | Vite | - |
| Runtime JS | Node.js | 24.16 |
| Package Manager | NPM | 11.16 |

**Catatan penting**: Frontend Vue **TIDAK** menggunakan Nuxt dan **TIDAK** menggunakan Inertia.js. Vue berjalan sebagai SPA mandiri di `src/frontend`, terpisah dari Laravel. Laravel di `src/backend` HANYA berfungsi sebagai REST API (mengembalikan JSON), tidak merender view Blade untuk halaman aplikasi.

---

## 2. SUMBER KEBENARAN (SOURCE OF TRUTH)

Sebelum mengerjakan task apapun, AI **WAJIB** membaca dokumen berikut secara berurutan:

1. `docs/1_PRD.md` — visi produk, user persona, user flow
2. `docs/2_SRS.md` — aturan validasi bisnis & hak akses (RBAC)
3. `docs/3_SDD.md` — skema database (ERD), struktur API, arsitektur
4. `docs/4_TASK_BREAKDOWN.md` — task/backlog yang sedang dieksekusi

AI **DILARANG** mengasumsikan struktur data, alur bisnis, kontrak API (request/response shape), atau hak akses yang tidak tercantum di dokumen-dokumen ini. Jika informasi tidak tersedia atau ambigu, AI **WAJIB bertanya ke user**, bukan menebak atau mengarang.

---

## 3. ATURAN UMUM CODING

- Komentar kode: **Bahasa Indonesia**, singkat dan jelas tujuannya
- Penamaan variabel, fungsi, class: **Bahasa Inggris**, konsisten dengan konvensi tiap stack (lihat bawah)
- Tidak boleh mengubah atau menghapus kode di luar scope task yang sedang diminta
- Tidak boleh menambah dependency/package baru tanpa konfirmasi user
- Setiap fungsi/komponen baru disertai komentar singkat tujuannya

---

## 4. ATURAN BACKEND — Laravel 13.16 (PHP 8.5.7)

- Struktur folder mengikuti konvensi default Laravel (`app/Http/Controllers`, `app/Models`, `app/Http/Requests`, dst.)
- **Backend HANYA mengembalikan JSON** — gunakan `Route::apiResource` / prefix `api/` untuk semua endpoint, jangan buat route yang merender Blade view untuk fitur aplikasi
- Validasi input **WAJIB** menggunakan Form Request class (`php artisan make:request`), bukan validasi inline di controller
- Query database menggunakan **Eloquent ORM**; hindari raw SQL kecuali tidak ada alternatif dan harus dijelaskan alasannya
- Otorisasi/hak akses (RBAC) mengikuti aturan di `docs/2_SRS.md`, diimplementasikan lewat Laravel **Policy** atau **Gate**
- Response API mengikuti format konsisten (status code HTTP yang sesuai + struktur JSON seragam, mis. `{ "data": ..., "message": ... }`) — format pasti mengikuti `docs/3_SDD.md`
- Migration **WAJIB** dibuat untuk setiap perubahan skema, tidak mengubah database langsung tanpa migration
- Mengikuti PHP 8.5 syntax modern (typed properties, enum, readonly property) bila relevan

---

## 5. ATURAN FRONTEND — Vue 3 (SPA) + Vite

- Gunakan **Composition API** dengan `<script setup>`, bukan Options API
- State management: tentukan satu pendekatan konsisten (mis. **Pinia**) — jangan campur dengan state lokal yang seharusnya global
- Komunikasi ke backend Laravel **HANYA** lewat HTTP client (mis. Axios) ke endpoint `api/...`, tidak ada akses langsung ke database atau logic backend dari sisi frontend
- Autentikasi mengikuti mekanisme token (mis. Laravel Sanctum) sesuai yang didefinisikan di `docs/3_SDD.md` — frontend tidak boleh mengasumsikan mekanisme auth sendiri
- Komponen harus reusable dan dipecah per tanggung jawab (hindari komponen raksasa)
- Struktur folder mengikuti pola: `components/`, `views/` atau `pages/`, `composables/`, `stores/` (jika pakai Pinia), `router/`, `services/` (untuk pemanggilan API)
- Environment variable (`VITE_API_BASE_URL`, dll) diatur lewat `.env`, tidak hardcode URL backend di kode

---

## 6. ATURAN TESTING

- Setiap fitur backend baru **WAJIB** disertai unit/feature test minimal di `tests/` (PHPUnit/Pest)
- AI **tidak boleh** menandai task selesai di `4_TASK_BREAKDOWN.md` sebelum test relevan dibuat dan lulus
- Untuk frontend, prioritaskan test pada logic penting (composables, services) bila waktu memungkinkan

---

## 7. ATURAN GIT & COMMIT

- Format commit: `[tipe]: deskripsi singkat` (contoh: `feat: tambah endpoint login`, `fix: validasi form registrasi`)
- Tipe yang dipakai: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`
- AI **tidak melakukan commit otomatis** tanpa konfirmasi eksplisit dari user
- Tidak push langsung ke branch utama (`main`/`master`) tanpa instruksi

---

## 8. BATASAN AI AGENT (GUARDRAILS)

- AI **DILARANG** menjalankan perintah destruktif (`rm -rf`, `DROP TABLE`, `migrate:fresh` di luar local/dev, dsb.) tanpa konfirmasi eksplisit dari user
- AI **DILARANG** mengubah file di luar `src/backend`, `src/frontend`, dan `tests/` kecuali diminta secara eksplisit
- AI **DILARANG** mengubah file di `docs/` kecuali diminta secara eksplisit (dokumen adalah sumber kebenaran, bukan output AI)
- Jika task tidak jelas, ambigu, atau bertentangan dengan dokumen di `docs/`, AI **WAJIB bertanya**, bukan berasumsi
- Perubahan besar pada arsitektur (struktur folder, library inti, pola desain) **WAJIB dilaporkan dan dikonfirmasi** ke user sebelum dieksekusi
- AI tidak menyimpan kredensial, API key, atau data sensitif di kode — selalu lewat `.env`

---

## 9. WORKFLOW KERJA STANDAR

1. Baca task aktif dari `docs/4_TASK_BREAKDOWN.md`
2. Cek aturan bisnis terkait di `docs/2_SRS.md` dan struktur data/API di `docs/3_SDD.md`
3. Implementasi kode (backend dan/atau frontend sesuai scope task)
4. Tulis/jalankan test yang relevan
5. Update status task di `4_TASK_BREAKDOWN.md`
6. Laporkan ringkasan perubahan ke user (file yang diubah, alasan, hal yang perlu direview)
