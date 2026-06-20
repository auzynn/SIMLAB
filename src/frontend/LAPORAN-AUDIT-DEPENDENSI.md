# 📋 Laporan Audit Dependensi Frontend — app-labriset

**Tanggal:** Januari 2026  
**Lokasi:** `src/frontend/app-labriset/`  
**Framework:** Vue 2 + Vue CLI  

---

## 1. Ringkasan Eksekutif

Secara keseluruhan, dependensi frontend proyek ini **sudah tidak up-to-date** dan beberapa di antaranya telah memasuki status **End-of-Life (EOL)**. Risiko terbesar ada pada penggunaan **Vue 2** yang sudah tidak didukung secara resmi sejak 31 Desember 2023, yang berarti tidak ada lagi patch keamanan dari tim Vue.

---

## 2. Masalah Struktural

### ⚠️ Dua `package.json` yang Saling Konflik

| Lokasi | Versi Vue |
|---|---|
| `src/frontend/package.json` | Vue **3** (`^3.2.45`) |
| `src/frontend/app-labriset/package.json` | Vue **2** (`^2.6.14`) |

**Kode sumber (`main.js`, `router/index.js`, semua `.vue`)** menggunakan API Vue 2:
- `new Vue({...}).$mount("#app")` — inisialisasi gaya Vue 2
- `Vue.use(VueRouter)` — plugin registration gaya Vue 2
- `new VueRouter({...})` — constructor gaya Vue 2
- `vue-template-compiler` — compiler khusus Vue 2

**Rekomendasi:** File `src/frontend/package.json` (di luar `app-labriset`) kemungkinan merupakan artefak sisa pembuatan proyek yang tidak terpakai. Sebaiknya **dihapus** beserta `node_modules` dan `package-lock.json` di level tersebut agar tidak membingungkan.

---

## 3. Audit Dependensi Utama (`dependencies`)

### 🔴 vue `^2.6.14` → EOL

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `^2.6.14` (terinstal: `2.7.x` karena `^`) |
| **Versi terkini** | Vue 3.5.x |
| **Status Vue 2** | **End-of-Life sejak 31 Desember 2023** |
| **Risiko** | Tidak ada lagi security patch resmi. Kerentanan baru tidak akan ditambal. |
| **Tingkat urgensi** | 🔴 **Kritis** |

### 🔴 vue-router `^3.5.1` → Terikat Vue 2

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `^3.5.1` |
| **Versi terkini** | Vue Router 4.x (untuk Vue 3) |
| **Status** | Vue Router 3.x hanya untuk Vue 2, ikut EOL bersama Vue 2 |
| **Tingkat urgensi** | 🔴 **Kritis** (terikat migrasi Vue) |

### 🟡 axios `^1.2.0` → Update minor tersedia

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `^1.2.0` |
| **Versi terkini** | `1.7.x+` |
| **Perubahan penting** | Perbaikan keamanan CSRF, SSRF protection, bug fix pada interceptor |
| **Tingkat urgensi** | 🟡 **Sedang** — bisa di-update tanpa breaking change (`^` semver) |
| **Aksi** | `npm update axios` — aman, non-breaking |

### 🟡 core-js `^3.8.3` → Update minor tersedia

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `^3.8.3` |
| **Versi terkini** | `3.40.x+` |
| **Perubahan penting** | Polyfill baru, optimisasi ukuran bundle, dukungan proposal TC39 terbaru |
| **Tingkat urgensi** | 🟡 **Sedang** |
| **Aksi** | `npm update core-js` — aman, non-breaking |

---

## 4. Audit Dev Dependencies

### 🟠 @vue/cli-service `~5.0.0` → Maintenance Mode

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `~5.0.0` |
| **Status** | Vue CLI dalam **mode maintenance** — tidak ada fitur baru |
| **Pengganti** | **Vite** (build tool resmi yang direkomendasikan oleh tim Vue) |
| **Tingkat urgensi** | 🟠 **Rendah-Sedang** (masih berfungsi, tapi tidak ideal untuk proyek baru) |

### 🟠 @vue/cli-plugin-babel `~5.0.0` → Maintenance Mode

Sama seperti `@vue/cli-service`, ikut maintenance mode bersama seluruh ekosistem Vue CLI.

### 🟠 @vue/cli-plugin-eslint `~5.0.0` → Maintenance Mode

Sama seperti di atas.

### 🟠 @vue/cli-plugin-router `~5.0.0` → Maintenance Mode

Sama seperti di atas.

### 🔴 eslint `^7.32.0` → Sangat Lawas

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `^7.32.0` |
| **Versi terkini** | ESLint 9.x (flat config) |
| **Status ESLint 7** | **End-of-Life** — tidak ada perbaikan bug/security |
| **Tingkat urgensi** | 🔴 **Tinggi** |
| **Catatan** | Upgrade ke ESLint 9.x memerlukan migrasi ke flat config (`eslint.config.js`) |

### 🟠 eslint-plugin-vue `^8.0.3` → Lawas

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `^8.0.3` |
| **Versi terkini** | `9.x` / `10.x` |
| **Tingkat urgensi** | 🟠 **Sedang** — terikat versi ESLint |

### 🟡 @babel/core `^7.12.16` → Update minor tersedia

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `^7.12.16` |
| **Versi terkini** | `7.26.x+` |
| **Tingkat urgensi** | 🟡 **Rendah** — masih dalam major v7, update aman via semver |
| **Aksi** | `npm update @babel/core` — aman |

### 🟡 @babel/eslint-parser `^7.12.16` → Update minor tersedia

Sama seperti `@babel/core`, ikut update dalam ekosistem Babel 7.

### 🔴 vue-template-compiler `^2.6.14` → Usang

| Aspek | Detail |
|---|---|
| **Versi di proyek** | `^2.6.14` |
| **Pengganti** | `@vue/compiler-sfc` (untuk Vue 3) |
| **Status** | Ikut EOL bersama Vue 2 |
| **Tingkat urgensi** | 🔴 **Kritis** (terikat migrasi Vue) |

---

## 5. Audit Konfigurasi & Tooling

### 📦 `vue.config.js` — Tidak ada masalah

```js
devServer: { proxy: "http://127.0.0.1:8000/" }
```
Konfigurasi sederhana dan valid untuk development dengan backend Laravel.

### 📦 `babel.config.js` — Minimal, tidak ada masalah

Menggunakan preset default Vue CLI. Tidak ada konfigurasi custom.

### 📦 `browserslist` (di package.json) — Bisa diperketat

```json
["> 1%", "last 2 versions", "not dead"]
```
Ini adalah default yang cukup standar. Bisa diperketat untuk mengurangi ukuran bundle jika target user hanya browser modern.

### 📦 `eslintConfig` (di package.json) — Ada catatan

Menggunakan `plugin:vue/essential` — ini adalah rule set paling minimal dari eslint-plugin-vue. Pertimbangkan upgrade ke `plugin:vue/recommended` untuk menangkap lebih banyak masalah kualitas kode.

---

## 6. Dependensi yang Hilang / Belum Ada

Beberapa dependensi yang **disarankan untuk ditambahkan** di proyek modern:

| Paket | Kegunaan |
|---|---|
| **prettier** | Code formatter konsisten |
| **@vue/test-utils** + **jest/vitest** | Unit testing untuk komponen Vue |
| **.env / dotenv** | Manajemen environment variable (baseURL axios saat ini hardcode `localhost:8000`) |

---

## 7. Ringkasan Prioritas Aksi

### ✅ Bisa Dilakukan Sekarang (Aman, Non-Breaking)
1. `npm update axios` — patch keamanan
2. `npm update core-js` — polyfill terbaru
3. `npm update @babel/core @babel/eslint-parser` — bug fix
4. Hapus `src/frontend/package.json`, `package-lock.json`, dan `node_modules` di level `src/frontend/` (bukan yang di `app-labriset`)
5. Pindahkan baseURL axios ke environment variable

### 🟠 Perlu Perencanaan (Breaking Change Terbatas)
6. Upgrade ESLint 7 → 8 (atau 9 dengan flat config)
7. Upgrade eslint-plugin-vue ke versi yang kompatibel

### 🔴 Perlu Migrasi Besar (Roadmap Tersendiri)
8. **Migrasi Vue 2 → Vue 3** — termasuk:
   - `vue` 2.x → 3.x
   - `vue-router` 3.x → 4.x
   - `vue-template-compiler` → `@vue/compiler-sfc`
   - Vue CLI → **Vite**
   - Semua komponen: Options API tetap didukung, tapi perlu penyesuaian sintaks template/lifecycle

---

## 8. Skor Kesehatan Dependensi

| Kategori | Skor |
|---|---|
| Keamanan | ⭐⭐ / ⭐⭐⭐⭐⭐ (Vue 2 EOL = risiko tinggi) |
| Keterkinian | ⭐⭐ / ⭐⭐⭐⭐⭐ |
| Kompatibilitas | ⭐⭐⭐ / ⭐⭐⭐⭐⭐ (masih berjalan, tapi semakin sulit maintain) |
| Ekosistem & Dukungan | ⭐⭐ / ⭐⭐⭐⭐⭐ (library baru sering drop support Vue 2) |

**Skor keseluruhan: 2/5 — Perlu perhatian serius.**

---

*Laporan ini dibuat sebagai referensi audit. Tidak ada kode yang diubah.*
