# 🔗 Analisis Integrasi Backend (Laravel) ↔ Frontend (Vue SPA)

**Tanggal:** Januari 2026  
**Konteks:** Proyek terpisah — Laravel sebagai REST API, Vue sebagai SPA Landing Page  

---

## 1. Apakah `agent.md` Mempengaruhi Rencana Migrasi Frontend?

### ✅ Jawaban Singkat: Ya, dan ke arah yang POSITIF

File `agent.md` sudah secara eksplisit menetapkan stack yang **persis sama** dengan tujuan migrasi kita:

| Aturan di `agent.md` | Status Frontend Saat Ini | Setelah Migrasi |
|---|---|---|
| Vue.js **3.x** | ❌ Vue 2.6 (EOL) | ✅ Vue 3.5 |
| Build tool: **Vite** | ❌ Vue CLI (Webpack) | ✅ Vite 6.x |
| **Composition API** + `<script setup>` | ❌ Options API | ✅ (opsional, tapi direkomendasikan) |
| Komunikasi via **Axios** ke REST API | ✅ Sudah pakai Axios | ✅ Tetap |
| Frontend **BUKAN** Nuxt/**BUKAN** Inertia | ✅ SPA mandiri | ✅ SPA mandiri |
| ENV variable, **bukan hardcode URL** | ❌ Hardcode `localhost:8000` | ✅ `VITE_API_BASE_URL` |
| Struktur folder standar | ⚠️ Sebagian | ✅ Lengkap |

**Kesimpulan:** Rencana migrasi di `RENCANA-MIGRASI-FRONTEND.md` sepenuhnya **sejalan** dengan `agent.md`. Bahkan, migrasi ini **wajib dilakukan** agar frontend memenuhi standar yang ditetapkan di `agent.md`.

### Hal Tambahan dari `agent.md` yang Perlu Ditambahkan ke Rencana Migrasi

Beberapa poin dari `agent.md` yang belum tercakup di rencana migrasi sebelumnya:

1. **Composition API + `<script setup>`** — `agent.md` mewajibkan ini, bukan opsional
2. **Pinia** sebagai state management — belum ada, perlu ditambahkan
3. **Folder `composables/`** dan **`services/`** — belum ada, perlu dibuat
4. **Folder `stores/`** — untuk Pinia, belum ada
5. **Laravel Sanctum** untuk autentikasi — belum tersetup di backend maupun frontend
6. **Komentar Bahasa Indonesia** — perlu disesuaikan
7. **Testing** — minimal untuk composables dan services

---

## 2. Kondisi Backend Laravel Saat Ini

Setelah membaca kode backend, berikut kondisinya:

| Aspek | Status | Detail |
|---|---|---|
| Laravel Version | ✅ `13.x` | Terbaru, sesuai `agent.md` |
| PHP Version | ✅ `^8.3` | Modern |
| `routes/api.php` | ❌ **Belum ada** | Hanya ada `web.php` dan `console.php` |
| API routing di `bootstrap/app.php` | ❌ **Belum didaftarkan** | Belum ada `->withRouting(api: ...)` |
| Sanctum (auth token) | ❌ **Belum terinstal** | Tidak ada di `composer.json` |
| CORS config | ❌ **Belum ada** | Tidak ada `config/cors.php` |
| JSON exception handling | ✅ Sudah ada | `shouldRenderJsonWhen(fn($req) => $req->is('api/*'))` |
| Vite (backend) | ✅ Ada | Tapi ini untuk asset Laravel (Blade), bukan untuk Vue SPA |

**Kesimpulan:** Backend masih dalam kondisi **scaffold awal** — Laravel baru diinstal, belum ada API endpoint, autentikasi, atau konfigurasi CORS untuk menerima request dari frontend terpisah.

---

## 3. Apakah Bisa Disambungkan? Bagaimana Caranya?

### ✅ Jawaban: BISA. Ini memang arsitektur yang lazim dan didukung penuh.

Arsitektur "Laravel REST API + Vue SPA terpisah" adalah pola yang sangat umum dan well-supported. Berikut yang perlu disiapkan:

---

### 3.1 Yang Perlu Disiapkan di Backend (Laravel)

#### A. Buat `routes/api.php`

```php
<?php
// routes/api.php

use Illuminate\Support\Facades\Route;

// Contoh endpoint publik
Route::get('/posts', [PostController::class, 'index']);
Route::get('/dosen', [DosenController::class, 'index']);

// Endpoint yang butuh autentikasi
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    // ... endpoint lain
});
```

#### B. Daftarkan API routing di `bootstrap/app.php`

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',    // ← TAMBAHKAN INI
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

#### C. Install & Setup Laravel Sanctum (untuk autentikasi SPA)

```bash
cd src/backend
composer require laravel/sanctum
php artisan install:api
```

Sanctum mendukung **SPA Authentication** via cookie-based session — ideal untuk kasus ini karena frontend dan backend bisa di-serve dari domain yang sama (via proxy) saat development.

Tambahkan di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();  // ← Untuk Sanctum SPA auth
})
```

#### D. Setup CORS (Cross-Origin Resource Sharing)

Untuk Laravel 13, publikasikan config CORS:

```bash
php artisan config:publish cors
```

Edit `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',  // Vite dev server
    ],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,  // Penting untuk Sanctum SPA
];
```

#### E. Setup `.env` Backend

```env
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```

---

### 3.2 Yang Perlu Disiapkan di Frontend (Vue SPA)

#### A. Konfigurasi Axios untuk Sanctum SPA

Buat `src/services/api.js`:

```js
// src/services/api.js
// Konfigurasi Axios untuk komunikasi dengan backend Laravel

import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true, // Penting untuk Sanctum cookie-based auth
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }
})

export default api
```

#### B. Flow Autentikasi Sanctum SPA

```js
// src/services/auth.js
// Service autentikasi menggunakan Laravel Sanctum

import api from './api'

export const authService = {
  // Langkah 1: Ambil CSRF cookie (wajib sebelum login)
  async getCsrfCookie() {
    await api.get('/sanctum/csrf-cookie')
  },

  // Langkah 2: Login
  async login(email, password) {
    await this.getCsrfCookie()
    return api.post('/login', { email, password })
  },

  // Langkah 3: Ambil data user yang sedang login
  async getUser() {
    return api.get('/api/user')
  },

  // Logout
  async logout() {
    return api.post('/logout')
  }
}
```

#### C. Konfigurasi Vite Proxy (Development)

Di `vite.config.js` frontend:

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src')
    }
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      }
    }
  }
})
```

#### D. Tambahkan Pinia (State Management)

```bash
npm install pinia
```

Buat store untuk auth state:

```js
// src/stores/auth.js
// Store autentikasi global menggunakan Pinia

import { defineStore } from 'pinia'
import { ref } from 'vue'
import { authService } from '@/services/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const isAuthenticated = ref(false)

  async function login(email, password) {
    const response = await authService.login(email, password)
    user.value = response.data
    isAuthenticated.value = true
  }

  async function fetchUser() {
    try {
      const response = await authService.getUser()
      user.value = response.data
      isAuthenticated.value = true
    } catch {
      user.value = null
      isAuthenticated.value = false
    }
  }

  async function logout() {
    await authService.logout()
    user.value = null
    isAuthenticated.value = false
  }

  return { user, isAuthenticated, login, fetchUser, logout }
})
```

#### E. Daftarkan Pinia di `main.js`

```js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
```

#### F. Struktur Folder Setelah Migrasi Lengkap

```
src/frontend/app-labriset/
├── index.html
├── vite.config.js
├── package.json
├── .env                          ← Environment variables
├── .env.example
├── eslint.config.js
├── .prettierrc
└── src/
    ├── main.js                   ← Entry point (createApp)
    ├── App.vue                   ← Root component
    ├── router/
    │   └── index.js              ← Vue Router 4
    ├── views/                    ← Halaman/page
    │   ├── home-page.vue
    │   ├── login-page.vue
    │   ├── kepala-lab.vue
    │   ├── visi-misi.vue
    │   ├── list-dosen.vue
    │   ├── detail-dosen.vue
    │   ├── roadmap-lab.vue
    │   ├── roadmap-dosen.vue
    │   ├── jadwal-lab.vue
    │   ├── buku-info.vue
    │   ├── penelitian-publikasi.vue
    │   └── credential-info.vue
    ├── components/               ← Komponen reusable
    │   ├── header-component.vue
    │   ├── footer-component.vue
    │   ├── jumbotron-default.vue
    │   ├── jumbotron-small.vue
    │   ├── sidemenu-profile.vue
    │   ├── sidemenu-dosen.vue
    │   └── partner.vue
    ├── composables/              ← BARU: Composition logic reusable
    │   └── useApi.js
    ├── services/                 ← BARU: Panggilan API
    │   ├── api.js                ← Axios instance
    │   └── auth.js               ← Auth service
    ├── stores/                   ← BARU: Pinia stores
    │   └── auth.js               ← Auth store
    └── assets/
        ├── style/
        │   └── style.css
        └── (gambar-gambar)
```

---

## 4. Diagram Arsitektur Akhir

```
┌─────────────────────────────────────────────────────┐
│                    BROWSER (User)                    │
│                                                     │
│  Vue 3 SPA (Vite)         http://localhost:5173     │
│  ┌───────────────────────────────────────────────┐  │
│  │  Views ←→ Components                          │  │
│  │    ↕                                          │  │
│  │  Pinia Stores (state management)              │  │
│  │    ↕                                          │  │
│  │  Services (api.js, auth.js)                   │  │
│  │    ↕                                          │  │
│  │  Axios → HTTP Request (JSON)                  │  │
│  └───────────────┬───────────────────────────────┘  │
│                  │                                   │
└──────────────────┼───────────────────────────────────┘
                   │  /api/*  (REST)
                   │  /sanctum/csrf-cookie
                   ▼
┌─────────────────────────────────────────────────────┐
│              BACKEND (Laravel 13)                    │
│              http://localhost:8000                    │
│                                                     │
│  ┌───────────────────────────────────────────────┐  │
│  │  routes/api.php                               │  │
│  │    ↕                                          │  │
│  │  Middleware (Sanctum, CORS)                   │  │
│  │    ↕                                          │  │
│  │  Controllers → Form Requests (validasi)       │  │
│  │    ↕                                          │  │
│  │  Models (Eloquent) → Policy/Gate (RBAC)       │  │
│  │    ↕                                          │  │
│  │  MySQL Database                               │  │
│  └───────────────────────────────────────────────┘  │
│                                                     │
│  Response: { "data": ..., "message": ... }          │
└─────────────────────────────────────────────────────┘
```

---

## 5. Flow Koneksi Development vs Production

### Development (Local)

```
Vue SPA (localhost:5173) ──proxy──→ Laravel API (localhost:8000)
```

Vite proxy menangani CORS secara transparan. Request `/api/posts` dari Vue di-forward ke `localhost:8000/api/posts`.

### Production

Ada dua opsi umum:

**Opsi A — Satu Domain (Recommended)**
```
https://labriset.example.com/          → Vue SPA (static files)
https://labriset.example.com/api/*     → Laravel API
```
Diatur via Nginx/Apache reverse proxy. Tidak ada masalah CORS karena same-origin.

**Opsi B — Domain Terpisah**
```
https://labriset.example.com           → Vue SPA
https://api.labriset.example.com       → Laravel API
```
Perlu konfigurasi CORS di Laravel. Sanctum perlu dikonfigurasi untuk cross-domain.

---

## 6. Checklist Kesiapan Integrasi

### Backend (Laravel)
- [ ] `routes/api.php` dibuat dengan endpoint sesuai `docs/3_SDD.md`
- [ ] API routing didaftarkan di `bootstrap/app.php`
- [ ] Sanctum terinstal dan dikonfigurasi
- [ ] CORS dikonfigurasi untuk menerima request dari frontend
- [ ] Response format konsisten (`{ "data": ..., "message": ... }`)
- [ ] Form Request classes untuk validasi
- [ ] Policy/Gate untuk RBAC
- [ ] `.env` berisi `SANCTUM_STATEFUL_DOMAINS` dan `SESSION_DOMAIN`

### Frontend (Vue SPA)
- [ ] Migrasi ke Vue 3 + Vite selesai
- [ ] Axios instance terkonfigurasi (`withCredentials: true`)
- [ ] Service layer (`services/api.js`, `services/auth.js`) dibuat
- [ ] Pinia store untuk state management
- [ ] Router guard untuk halaman yang butuh autentikasi
- [ ] `.env` berisi `VITE_API_BASE_URL`
- [ ] Vite proxy dikonfigurasi untuk development

---

## 7. Urutan Kerja yang Direkomendasikan

```
1. Migrasi Frontend (Vue 2 → Vue 3 + Vite)        ← SEKARANG
   └── Fase 1, 2, 3 dari RENCANA-MIGRASI-FRONTEND.md

2. Setup Backend API Foundation
   ├── Buat routes/api.php
   ├── Install Sanctum
   ├── Setup CORS
   └── Buat 1 endpoint test sederhana

3. Koneksi Pertama (Smoke Test)
   ├── Frontend memanggil endpoint test
   ├── Verifikasi CORS berjalan
   └── Verifikasi Sanctum auth flow

4. Bangun Fitur (sesuai docs/4_TASK_BREAKDOWN.md)
   ├── Endpoint API per fitur
   ├── Frontend view + service per fitur
   └── Testing per fitur
```

---

*Dokumen ini berisi analisis integrasi. Tidak ada kode yang diubah.*
