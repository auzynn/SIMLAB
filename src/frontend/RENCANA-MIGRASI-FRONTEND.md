# 🚀 Rencana Migrasi Frontend — app-labriset

**Tanggal:** Januari 2026  
**Estimasi Total Waktu:** 2–4 jam (proyek ini tergolong ringan untuk migrasi)  
**Kompleksitas:** ⭐⭐ Rendah — hanya 9 breaking changes di 2 file inti  

---

## 📊 Ringkasan Analisis Kode

Dari 23 file sumber yang dianalisis:

| Kategori | Jumlah File | Detail |
|---|---|---|
| ✅ Tidak perlu diubah | 15 file | Komponen tanpa pola Vue 2 spesifik |
| 🔴 Breaking changes | **2 file** | `main.js` (4 lokasi), `router/index.js` (5 lokasi) |
| ℹ️ Options API (kompatibel) | 5 file | `data()`, `methods`, `created()` — tetap bekerja di Vue 3 |
| 📡 Axios (tidak berubah) | 3 file | Axios tidak terikat versi Vue |

**Kabar baik:** Karena semua komponen menggunakan Options API standar tanpa API Vue 2 yang dihapus (tidak ada `filters`, `$listeners`, `$children`, event bus, dll.), migrasi ini sangat straightforward.

---

## 📋 Rencana Migrasi — 3 Fase

---

### FASE 1: Update Aman (Tanpa Breaking Change)
> ⏱️ Estimasi: 15 menit  
> 🎯 Tujuan: Patch keamanan dan bug fix tanpa risiko

**Jalankan di terminal (`src/frontend/app-labriset/`):**

```bash
# Update dependensi dalam range semver yang sama (aman)
npm update axios core-js @babel/core @babel/eslint-parser
```

**Versi yang akan ter-update:**

| Paket | Dari | Ke (estimasi) |
|---|---|---|
| axios | `^1.2.0` | `~1.18.x` (latest dalam ^1) |
| core-js | `^3.8.3` | `~3.49.x` |
| @babel/core | `^7.12.16` | `~7.26.x` |
| @babel/eslint-parser | `^7.12.16` | `~7.26.x` |

**Tidak ada kode yang perlu diubah pada fase ini.**

---

### FASE 2: Migrasi Vue 2 → Vue 3 + Vite
> ⏱️ Estimasi: 1–2 jam  
> 🎯 Tujuan: Upgrade framework inti dan build tool

#### Langkah 2.1 — Buat branch baru

```bash
git checkout -b migration/vue3-vite
```

#### Langkah 2.2 — Update `package.json`

Ganti seluruh isi `package.json` dengan:

```json
{
  "name": "app-labriset",
  "version": "0.2.0",
  "private": true,
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "lint": "eslint . --fix"
  },
  "dependencies": {
    "axios": "^1.18.0",
    "vue": "^3.5.0",
    "vue-router": "^4.5.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^6.0.0",
    "eslint": "^9.0.0",
    "eslint-plugin-vue": "^10.0.0",
    "prettier": "^3.8.0",
    "vite": "^6.0.0"
  }
}
```

> **Catatan:** `core-js`, `@babel/*`, `vue-template-compiler`, dan seluruh `@vue/cli-*` tidak diperlukan lagi — Vite menggunakan esbuild untuk transpilasi.

#### Langkah 2.3 — Buat `vite.config.js` (menggantikan `vue.config.js`)

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      }
    }
  }
})
```

#### Langkah 2.4 — Ubah `src/main.js` (4 perubahan)

**SEBELUM (Vue 2):**
```js
import Vue from "vue";
import axios from "axios";
import App from "./App.vue";
import router from "./router";

axios.defaults.withCredentials = true;
axios.defaults.baseURL = "http://localhost:8000";
axios.defaults.headers.common["Accept"] = "application/json";
axios.defaults.headers.common["Content-Type"] = "application/json";

// Vue.config.productionTip = false

new Vue({
  router,
  render: (h) => h(App),
}).$mount("#app");
```

**SESUDAH (Vue 3):**
```js
import { createApp } from "vue";
import axios from "axios";
import App from "./App.vue";
import router from "./router";

axios.defaults.withCredentials = true;
axios.defaults.baseURL = "http://localhost:8000";
axios.defaults.headers.common["Accept"] = "application/json";
axios.defaults.headers.common["Content-Type"] = "application/json";

const app = createApp(App);
app.use(router);
app.mount("#app");
```

**Perubahan yang terjadi:**
1. `import Vue from "vue"` → `import { createApp } from "vue"`
2. `new Vue({...}).$mount("#app")` → `createApp(App).mount("#app")`
3. Router dipasang via `app.use(router)` (bukan opsi constructor)
4. `render: (h) => h(App)` → tidak diperlukan lagi
5. `Vue.config.productionTip` → dihapus (sudah tidak ada di Vue 3)

#### Langkah 2.5 — Ubah `src/router/index.js` (5 perubahan)

**SEBELUM (Vue Router 3):**
```js
import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)

const routes = [
  // ... routes tetap sama ...
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
})

export default router
```

**SESUDAH (Vue Router 4):**
```js
import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // ... routes TETAP SAMA, tidak perlu diubah ...
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
```

**Perubahan yang terjadi:**
1. `import Vue from 'vue'` → dihapus (tidak diperlukan)
2. `import VueRouter from 'vue-router'` → `import { createRouter, createWebHistory } from 'vue-router'`
3. `Vue.use(VueRouter)` → dihapus (sudah ditangani oleh `app.use(router)` di `main.js`)
4. `new VueRouter({...})` → `createRouter({...})`
5. `mode: 'history'` → `history: createWebHistory()`
6. `process.env.BASE_URL` → `import.meta.env.BASE_URL`

#### Langkah 2.6 — Pindahkan `index.html`

Vite memerlukan `index.html` di root proyek (bukan di `public/`).

```bash
mv public/index.html ./index.html
```

Edit `index.html` — tambahkan script tag:

```html
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Lab Riset</title>
  </head>
  <body>
    <div id="app"></div>
    <!-- Tambahkan baris ini untuk Vite -->
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
```

#### Langkah 2.7 — Hapus file lama

```bash
rm vue.config.js
rm babel.config.js
rm -rf node_modules package-lock.json
```

#### Langkah 2.8 — Install ulang dan test

```bash
npm install
npm run dev
```

Buka browser → `http://localhost:5173` (default Vite) dan verifikasi semua halaman:
- [ ] Home (`/`)
- [ ] Login (`/login`)
- [ ] Kepala Lab (`/kepalalab`)
- [ ] Visi Misi (`/visimisi`)
- [ ] List Dosen (`/listdosen`)
- [ ] Roadmap Lab (`/roadmaplab`)
- [ ] Detail Dosen (`/detaildosen`)
- [ ] Credential (`/credential`)
- [ ] Publikasi (`/publikasi`)
- [ ] Buku (`/buku`)
- [ ] Roadmap Dosen (`/roadmapdosen`)
- [ ] Jadwal Lab (`/jadwallab`)

---

### FASE 3: Pembersihan & Peningkatan (Opsional)
> ⏱️ Estimasi: 30–60 menit  
> 🎯 Tujuan: Modernisasi konfigurasi dan best practice

#### 3.1 — Setup ESLint Flat Config

Buat `eslint.config.js` (menggantikan config di `package.json`):

```js
import pluginVue from 'eslint-plugin-vue'

export default [
  ...pluginVue.configs['flat/recommended'],
  {
    rules: {
      'vue/multi-word-component-names': 'off',
    }
  }
]
```

#### 3.2 — Setup Prettier

Buat `.prettierrc`:

```json
{
  "semi": false,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5"
}
```

#### 3.3 — Environment Variables

Buat `.env`:

```env
VITE_API_BASE_URL=http://localhost:8000
```

Update `main.js`:

```js
axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL;
```

> **Catatan Vite:** Environment variable harus diawali `VITE_` agar terekspos ke kode klien.

#### 3.4 — Hapus artefak `src/frontend/package.json`

```bash
# Dari root proyek
rm src/frontend/package.json
rm src/frontend/package-lock.json
rm -rf src/frontend/node_modules
```

#### 3.5 — (Opsional) Migrasi Komponen ke Composition API

Ini **tidak wajib** — Options API tetap didukung penuh di Vue 3. Namun jika ingin mengikuti best practice Vue 3, berikut contoh migrasi:

**SEBELUM (Options API):**
```vue
<script>
import axios from "axios"

export default {
  data() {
    return {
      posts: [],
      newPost: { title: "", body: "" }
    }
  },
  created() {
    this.fetchPosts()
  },
  methods: {
    async fetchPosts() {
      const response = await axios.get("/api/posts")
      this.posts = response.data
    }
  }
}
</script>
```

**SESUDAH (Composition API + `<script setup>`):**
```vue
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const posts = ref([])
const newPost = ref({ title: '', body: '' })

async function fetchPosts() {
  const response = await axios.get('/api/posts')
  posts.value = response.data
}

onMounted(fetchPosts)
</script>
```

File yang bisa dimigrasikan (opsional):
- `posts.vue` — `data()`, `created()`, `methods`
- `header-component.vue` — `data()`
- `sidemenu-profile.vue` — `data()`
- `login-page.vue` — `methods`
- `visi-misi.vue` — `data()`

---

## 📐 Diagram Perubahan

```
SEBELUM                              SESUDAH
──────                              ──────
vue@2.6 (EOL)           →    vue@3.5 (aktif)
vue-router@3.5           →    vue-router@4.5
vue-template-compiler    →    (tidak perlu)
@vue/cli-service         →    vite@6.x
@vue/cli-plugin-*        →    @vitejs/plugin-vue
eslint@7 (EOL)           →    eslint@9.x (flat config)
eslint-plugin-vue@8      →    eslint-plugin-vue@10
@babel/core              →    (tidak perlu, Vite pakai esbuild)
core-js                  →    (tidak perlu, target browser modern)
babel.config.js          →    (dihapus)
vue.config.js            →    vite.config.js
public/index.html        →    index.html (root)
```

---

## ⚠️ Hal yang Perlu Diperhatikan

### 1. Proxy Backend
Konfigurasi proxy di `vue.config.js` perlu dipindahkan ke `vite.config.js`. Sudah tercakup di Langkah 2.3.

### 2. Environment Variable
- Vue CLI: `VUE_APP_*` + `process.env.VUE_APP_*`
- Vite: `VITE_*` + `import.meta.env.VITE_*`

### 3. Asset Handling
- Static assets di `public/` tetap di `public/` (tidak berubah)
- Asset yang di-import di komponen (`@/assets/...`) tetap bekerja — Vite mendukung alias `@` setelah dikonfigurasi di `vite.config.js`:

```js
import { resolve } from 'path'

export default defineConfig({
  // ... plugins, server ...
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src')
    }
  }
})
```

### 4. CSS
File `style.css` yang di-import di `App.vue` tetap bekerja tanpa perubahan.

### 5. Webpack Chunk Names
Comment `/* webpackChunkName: "about" */` di `router/index.js` tidak diperlukan di Vite (bisa dihapus). Vite melakukan automatic code splitting.

---

## ✅ Checklist Verifikasi Setelah Migrasi

- [ ] `npm run dev` berjalan tanpa error
- [ ] Semua 12 route dapat diakses
- [ ] Login page berfungsi (axios POST ke backend)
- [ ] Data dari API backend tampil (list dosen, publikasi, dll.)
- [ ] Navigasi antar halaman lancar (router-view)
- [ ] Header component muncul di semua halaman
- [ ] CSS/styling tidak berubah
- [ ] Proxy ke backend Laravel berfungsi
- [ ] `npm run build` berhasil (production build)
- [ ] Tidak ada error di browser console

---

*Dokumen ini berisi rencana migrasi lengkap. Tidak ada kode yang diubah — semua perubahan bersifat panduan.*
