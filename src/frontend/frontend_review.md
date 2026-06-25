# Review Kesesuaian Folder Frontend

> Berdasarkan aturan di `.clinerules/agent.md` (§5 Aturan Frontend) dan prinsip ponytail (YAGNI).

---

## Struktur Folder

```
src/frontend/app-labriset/
├── src/
│   ├── App.vue
│   ├── main.js
│   ├── posts.vue           ⚠️ MASALAH — file salah tempat
│   ├── assets/
│   ├── components/
│   ├── composables/        ✅ ada (hanya .gitkeep — kosong)
│   ├── router/
│   ├── services/
│   ├── stores/
│   └── views/
├── .env                    ✅
├── .env.example            ✅
├── package.json            ✅
├── vite.config.js          ✅
└── ...
```

---

## ✅ Hal yang Sudah Sesuai

| Aspek | Status | Detail |
|---|---|---|
| **Framework** | ✅ | Vue 3 + Vite, sesuai stack |
| **Composition API** | ✅ | Semua file pakai `<script setup>` |
| **Pinia** | ✅ | Terdaftar di `main.js`, ada `stores/auth.js` |
| **Vue Router** | ✅ | Konfigurasi di `router/index.js`, lazy loading |
| **Axios via services** | ✅ | Axios diakses lewat `services/api.js`, tidak langsung di template |
| **VITE_API_BASE_URL** | ✅ | Diambil dari `.env`, tidak hardcode di kode |
| **Struktur folder** | ✅ | `components/`, `views/`, `composables/`, `stores/`, `router/`, `services/` semua ada |
| **Komentar Bahasa Indonesia** | ✅ | Konsisten di semua file yang diperiksa |
| **Auth via Sanctum** | ✅ | `services/auth.js` pakai CSRF cookie + Sanctum pattern |
| **Proxy Vite** | ✅ | `/api` dan `/sanctum` diproxy ke backend, konsisten |
| **Penamaan variabel/fungsi** | ✅ | Bahasa Inggris (`login`, `fetchUser`, `logout`, dll.) |

---

## ⚠️ Masalah yang Ditemukan

### 1. `posts.vue` — File Sampah / Sisa Testing

**File**: [`src/posts.vue`](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/src/frontend/app-labriset/src/posts.vue)

- Bukan bagian dari fitur apapun di PRD/SRS
- Merupakan file percobaan koneksi API (CRUD post fiktif)
- Salah tempat: berada di `src/` langsung, bukan di `views/`
- Tidak terdaftar di router sama sekali
- **Rekomendasi**: **Hapus** file ini (ponytail: deletion over addition)

---

### 2. `composables/` — Kosong

**Path**: [`src/composables/`](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/src/frontend/app-labriset/src/composables/) (hanya berisi `.gitkeep`)

- Folder ini ada tapi belum diisi
- Tidak ada composable sama sekali (useAuth, dll.)
- **Status**: Acceptable untuk tahap ini (YAGNI — belum ada kebutuhan composable terpisah)

---

### 3. Router Tanpa Route Guard / Auth Navigation Guard

**File**: [`router/index.js`](file:///d:/Kuliah/Kerja Praktek/Lab. Riset/Test SIM/SIMLAB/src/frontend/app-labriset/src/router/index.js)

- Semua route bebas diakses tanpa autentikasi
- `/credential`, `/jadwallab`, `/detaildosen` harusnya protected berdasarkan SRS (ada RBAC)
- Auth store (`stores/auth.js`) sudah ada tapi belum diintegrasikan ke router guard
- **Rekomendasi**: Tambahkan `router.beforeEach` guard sesuai RBAC di `docs/2_SRS.md` — tapi **konfirmasi dulu ke user** karena ini perubahan yang berdampak

---

### 4. `login-page.vue` — Form Login Belum Terhubung ke Store

**File**: [`views/login-page.vue`](file:///d:/Kuliah/Kerja Praktek/Lab. Riset/Test SIM/SIMLAB/src/frontend/app-labriset/src/views/login-page.vue)

- Input email & password tidak di-bind dengan `v-model`
- Tombol "Masuk" adalah `<a href="#">`, bukan `<button type="submit">` atau `@click`
- Tidak memanggil `useAuthStore().login()` sama sekali
- Hanya login UNSIL/Google yang punya handler (`loginUnsil`)
- **Status**: **⚠️ Belum fungsional** — halaman login hanya UI statis

---

### 5. `list-dosen.vue` — Data Hardcoded / Duplikat

**File**: [`views/list-dosen.vue`](file:///d:/Kuliah/Kerja Praktek/Lab. Riset/Test SIM/SIMLAB/src/frontend/app-labriset/src/views/list-dosen.vue)

- 5 card dosen identis, semua pakai nama & foto yang sama (placeholder)
- Tidak ada pemanggilan API ke backend
- Deskripsi dosen masih "Lorem ipsum"
- **Status**: Wajar untuk tahap UI mockup, tapi harus dihubungkan ke API sebelum bisa disebut selesai

---

### 6. Style CSS di-import Berulang di Tiap Komponen

- `App.vue` sudah `@import 'assets/style/style.css'`
- Beberapa komponen seperti `header-component.vue` dan `login-page.vue` juga mengimport `style.css` di `<style>` lokal mereka
- Berisiko: style duplikat, urutan cascade tidak terprediksi
- **Rekomendasi**: Import CSS global **hanya di `App.vue`** atau `main.js`, hapus import redundan di komponen individual

---

### 7. `dist/` Ada di Repository

- Folder `dist/` (output build) terdeteksi ada
- Seharusnya di-gitignore, bukan di-commit ke repo
- **Cek**: Pastikan `dist/` sudah ada di `.gitignore`

---

## Ringkasan Prioritas Perbaikan

| Prioritas | Masalah | Aksi |
|---|---|---|
| 🔴 Segera | `posts.vue` sisa testing di `src/` | Hapus |
| 🔴 Segera | `login-page.vue` tidak fungsional | Hubungkan ke `authStore.login()` |
| 🟡 Penting | Route guard RBAC belum ada | Tambahkan setelah konfirmasi SRS |
| 🟡 Penting | CSS import duplikat di komponen | Pindahkan ke `App.vue` saja |
| 🟢 Backlog | `list-dosen.vue` data hardcoded | Sambungkan ke API saat task dosen aktif |
| 🟢 Backlog | `composables/` kosong | Isi saat ada kebutuhan nyata (YAGNI) |
| 🔍 Cek | `dist/` di repo | Pastikan ada di `.gitignore` |
