<template>
  <header>
    <nav>
      <div class="logo flex-h">
        <img src="../assets/logo-unsil.png" class="h-40">
        <div class="ml-10">
          <h1>LAB RISET</h1>
        </div>
      </div>

      <ul>
        <li class="nav-hover" :class="{ activenav: activeMenu === 'home' }">
          <router-link to="/">Beranda</router-link>
        </li>

        <li class="nav-hover" :class="{ activenav: activeMenu === 'profil' }">
          <router-link to="/kepalalab">Profil</router-link>
        </li>

        <!-- Layanan Akademik: grup menu akademik (Jadwal, Kelas, Sertifikasi, Portofolio) —
             hanya tampil setelah login; muncul sebagai dropdown saat di-hover. -->
        <li
          v-if="auth.isAuthenticated"
          class="nav-hover nav-menu"
          :class="{ activenav: activeMenu === 'layanan' }"
          @mouseenter="layananOpen = true"
          @mouseleave="layananOpen = false"
        >
          <a class="nav-menu-toggle">
            Layanan Akademik
            <svg class="nav-caret" :class="{ open: layananOpen }" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 9l6 6 6-6" />
            </svg>
          </a>
          <!-- Dropdown dikontrol state (bukan :hover murni) agar bisa MENUTUP tepat
               setelah item diklik, walau kursor masih di atasnya. -->
          <ul v-show="layananOpen" class="nav-dropdown">
            <li><router-link to="/jadwallab" @click="layananOpen = false">Informasi Jadwal</router-link></li>
            <li><router-link to="/kelaslab" @click="layananOpen = false">Kelas Lab</router-link></li>
            <li><router-link to="/sertifikasi" @click="layananOpen = false">Sertifikasi</router-link></li>
            <li><router-link to="/portofolio" @click="layananOpen = false">Portofolio</router-link></li>
          </ul>
        </li>

        <!-- Laporan: khusus role Admin (dipertahankan sebagai menu tersendiri di navbar) -->
        <li v-if="isAdmin" class="nav-hover" :class="{ activenav: activeMenu === 'report' }">
          <router-link to="/report">Laporan</router-link>
        </li>

        <!-- Rekap Tugas: Admin/Supervisor/Dosen (Dosen di-scope ke kelasnya di backend) -->
        <li v-if="canRekapTugas" class="nav-hover" :class="{ activenav: activeMenu === 'rekap-tugas' }">
          <router-link to="/rekap-tugas">Rekap Tugas</router-link>
        </li>

        <!-- Perangkat kini diakses dari halaman Jadwal Lab (kartu "Perangkat Lab") — tidak lagi di navbar. -->

        <!-- Belum login: tombol Login -->
        <li v-if="!auth.isAuthenticated">
          <router-link to="/login" class="btn-nav-login">Login</router-link>
        </li>

        <!-- Sudah login: lonceng notifikasi -->
        <li v-if="auth.isAuthenticated" class="bell-nav">
          <NotificationBell />
        </li>

        <!-- Sudah login: avatar (klik → Profil Saya) dengan dropdown saat di-hover -->
        <li
          v-if="auth.isAuthenticated"
          class="user-menu"
          @mouseenter="profilOpen = true"
          @mouseleave="profilOpen = false"
        >
          <router-link to="/profil" class="user-avatar" title="Profil Saya" @click="profilOpen = false">
            <img v-if="auth.user?.avatar" :src="auth.user.avatar" referrerpolicy="no-referrer" alt="Profil" />
            <span v-else>{{ initials }}</span>
          </router-link>
          <!-- Dropdown dikontrol state agar MENUTUP tepat setelah item diklik. -->
          <ul v-show="profilOpen" class="user-dropdown">
            <!-- Akses Panel Admin hanya untuk role admin -->
            <li v-if="auth.user?.role === 'admin'"><router-link to="/admin" @click="profilOpen = false">Panel Admin</router-link></li>
            <!-- Persetujuan Peminjaman kini diakses dari halaman Jadwal Lab;
                 Kelola Kelas Lab dari halaman Kelas Lab — tidak lagi di dropdown ini. -->
            <!-- Peminjaman Saya — Mahasiswa: gabungan ruangan + perangkat (tab) -->
            <li v-if="auth.user?.role === 'mahasiswa'">
              <router-link to="/peminjaman-saya" @click="profilOpen = false">Peminjaman Saya</router-link>
            </li>
            <!-- Persetujuan Perangkat (Admin/Supervisor) kini diakses dari halaman Jadwal Lab,
                 di bawah kartu Persetujuan Peminjaman Ruangan — tidak lagi di dropdown ini. -->
            <li><router-link to="/profil" @click="profilOpen = false">Profil Saya</router-link></li>
            <li><a class="logout-link" @click="handleLogout">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>
</template>

<script setup>
// Komponen header navigasi utama
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotifikasiStore } from '@/stores/notifikasi'
import NotificationBell from '@/components/notification-bell.vue'

const auth = useAuthStore()
const notifikasi = useNotifikasiStore()
const route = useRoute()
const router = useRouter()

// State buka/tutup dropdown. Hover membuka; klik item menutup (lihat template).
const layananOpen = ref(false)
const profilOpen = ref(false)
// Jaga-jaga: setiap pindah halaman, pastikan kedua dropdown tertutup.
watch(() => route.path, () => {
  layananOpen.value = false
  profilOpen.value = false
})

// Laporan di navbar khusus role Admin (permintaan maintenance Fase 6–7).
const isAdmin = computed(() => auth.user?.role === 'admin')

// Rekap Tugas Kelas Lab: Admin/Supervisor/Dosen (Gate view-rekap-tugas).
const canRekapTugas = computed(() => ['admin', 'supervisor', 'dosen'].includes(auth.user?.role))

// Penanda menu aktif ditentukan dari route saat ini (bukan klik),
// agar highlight selalu sinkron termasuk saat refresh/akses langsung/navigasi lewat avatar.
const profilPaths = [
  '/kepalalab', '/visimisi', '/listdosen', '/roadmaplab',
  '/detaildosen', '/roadmapdosen', '/credential', '/publikasi', '/buku',
]
const activeMenu = computed(() => {
  const path = route.path
  if (path === '/') return 'home'
  // Semua modul akademik kini di bawah "Layanan Akademik" → sorot menu grup tersebut.
  // Perangkat bagian dari Jadwal Lab, jadi ikut ter-highlight di grup Layanan Akademik.
  if (path === '/jadwallab' || path === '/perangkat') return 'layanan'
  if (path === '/kelaslab' || path.startsWith('/kelaslab/')) return 'layanan'
  if (path === '/sertifikasi') return 'layanan'
  if (path === '/portofolio') return 'layanan'
  if (path === '/report') return 'report'
  if (path === '/rekap-tugas') return 'rekap-tugas'
  // startsWith agar path berparameter (mis. /detaildosen/2) tetap ter-highlight
  if (profilPaths.some((p) => path === p || path.startsWith(p + '/'))) return 'profil'
  return '' // /profil (Profil Saya), /login, dll → tidak ada highlight di nav atas
})

// Inisial nama untuk avatar fallback (saat user tidak punya foto Google)
const initials = computed(() => {
  const name = auth.user?.name || ''
  const parts = name.split(' ').filter(Boolean).slice(0, 2)
  return parts.map((w) => w[0]).join('').toUpperCase() || '?'
})

// Logout: hapus token & sesi, lalu kembali ke beranda
async function handleLogout() {
  profilOpen.value = false
  await auth.logout()
  notifikasi.reset()
  router.push('/')
}
</script>

<style scoped>
/* ===== Layanan Akademik: menu grup dengan dropdown saat hover ===== */
.nav-menu {
  position: relative;
}

/* Pemicu dropdown: teks + caret, disejajarkan dengan item navbar lain */
.nav-menu-toggle {
  display: flex;
  align-items: center;
  gap: 4px;
  color: var(--bs-navy);
  cursor: pointer;
  white-space: nowrap;
}

.nav-caret {
  transition: transform 0.18s ease;
}

.nav-caret.open {
  transform: rotate(180deg);
}

/* Dropdown menempel di bawah item (top: 100%) agar tak ada celah hover.
   Visibilitas dikontrol v-show (state layananOpen), jadi display default = flex. */
.nav-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  width: max-content;
  min-width: 180px;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  padding: 6px 0;
  display: flex;
  flex-direction: column;
  gap: 0;
  z-index: 20;
}

.nav-dropdown li {
  padding: 0;
}

.nav-dropdown li a {
  display: block;
  padding: 10px 16px;
  white-space: nowrap;
  color: var(--bs-navy);
}

.nav-dropdown li a:hover {
  background-color: var(--bs-grey2);
}

/* Tombol Login navbar — pil navy dengan isian saat hover + sedikit terangkat. */
.btn-nav-login {
  display: inline-block;
  padding: 8px 24px;
  border: 2px solid var(--bs-navy);
  border-radius: 999px;
  color: var(--bs-navy);
  font-weight: 700;
  line-height: 1;
  background-color: transparent;
  transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}

.btn-nav-login:hover {
  color: #fff;
  background-color: var(--bs-navy);
  transform: translateY(-1px);
  box-shadow: 0 6px 14px rgba(24, 56, 97, 0.25);
}

.btn-nav-login:active {
  transform: translateY(0);
  box-shadow: 0 3px 8px rgba(24, 56, 97, 0.22);
}

.bell-nav {
  display: flex;
  align-items: center;
}

.user-menu {
  position: relative;
  display: flex;
  align-items: center;
}

.user-avatar {
  width: 40px;
  height: 40px;
  padding: 0;
  border-radius: 50%;
  overflow: hidden;
  background-color: var(--bs-navy);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-avatar span {
  color: #fff;
  font-weight: 600;
  font-size: 0.95em;
}

/* Dropdown menempel (top: 100%) agar tak ada celah hover. Visibilitas dikontrol
   v-show (state profilOpen) supaya bisa menutup tepat setelah item diklik. */
.user-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  width: max-content;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  padding: 6px 0;
  display: flex;
  flex-direction: column;
  z-index: 20;
}

.user-dropdown li {
  padding: 0;
}

.user-dropdown li a {
  display: block;
  padding: 10px 14px;
  white-space: nowrap;
  color: var(--bs-navy);
  cursor: pointer;
}

.user-dropdown li a:hover {
  background-color: var(--bs-grey2);
}
</style>
