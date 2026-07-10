<template>
  <header>
    <nav>
      <div class="logo flex-h">
        <img src="../assets/logo-unsil.png" class="h-40">
        <div class="ml-10">
          <h1>LAB RISET</h1>
        </div>
      </div>

      <!-- Nav desktop: disembunyikan di bawah breakpoint mobile (lihat CSS), digantikan panel hamburger -->
      <ul class="nav-desktop">
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
            <!-- Panel kelola: Admin (semua modul) & Supervisor (subset sesuai hak) -->
            <li v-if="bisaPanel"><router-link to="/admin" @click="profilOpen = false">Panel Kelola</router-link></li>
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

      <!-- Cluster kanan khusus mobile: lonceng notifikasi (tetap terjangkau) + tombol hamburger.
           Nav utama disembunyikan di mobile dan dipindah ke panel di bawah. -->
      <div class="nav-mobile-actions">
        <NotificationBell v-if="auth.isAuthenticated" />
        <button class="hamburger-btn" :aria-expanded="mobileOpen" aria-label="Menu navigasi" @click="mobileOpen = !mobileOpen">
          <X v-if="mobileOpen" :size="26" />
          <Menu v-else :size="26" />
        </button>
      </div>
    </nav>

    <!-- Panel nav mobile — muncul/collapse saat hamburger diklik. Isinya mereplikasi seluruh
         menu desktop (dengan kondisi RBAC yang sama), disusun vertikal ramah-jari. -->
    <transition name="mobile-nav-slide">
      <ul v-if="mobileOpen" class="nav-mobile">
        <li :class="{ activenav: activeMenu === 'home' }"><router-link to="/" @click="mobileOpen = false">Beranda</router-link></li>
        <li :class="{ activenav: activeMenu === 'profil' }"><router-link to="/kepalalab" @click="mobileOpen = false">Profil</router-link></li>

        <!-- Layanan Akademik: sub-item ditampilkan langsung (flatten) di mobile, tanpa hover -->
        <template v-if="auth.isAuthenticated">
          <li class="nav-mobile-group">Layanan Akademik</li>
          <li class="nav-mobile-sub"><router-link to="/jadwallab" @click="mobileOpen = false">Informasi Jadwal</router-link></li>
          <li class="nav-mobile-sub"><router-link to="/kelaslab" @click="mobileOpen = false">Kelas Lab</router-link></li>
          <li class="nav-mobile-sub"><router-link to="/sertifikasi" @click="mobileOpen = false">Sertifikasi</router-link></li>
          <li class="nav-mobile-sub"><router-link to="/portofolio" @click="mobileOpen = false">Portofolio</router-link></li>
        </template>

        <li v-if="isAdmin" :class="{ activenav: activeMenu === 'report' }"><router-link to="/report" @click="mobileOpen = false">Laporan</router-link></li>
        <li v-if="canRekapTugas" :class="{ activenav: activeMenu === 'rekap-tugas' }"><router-link to="/rekap-tugas" @click="mobileOpen = false">Rekap Tugas</router-link></li>

        <li class="nav-mobile-divider"></li>

        <li v-if="!auth.isAuthenticated"><router-link to="/login" @click="mobileOpen = false">Login</router-link></li>
        <template v-if="auth.isAuthenticated">
          <li v-if="bisaPanel"><router-link to="/admin" @click="mobileOpen = false">Panel Kelola</router-link></li>
          <li v-if="auth.user?.role === 'mahasiswa'"><router-link to="/peminjaman-saya" @click="mobileOpen = false">Peminjaman Saya</router-link></li>
          <li><router-link to="/profil" @click="mobileOpen = false">Profil Saya</router-link></li>
          <li><a class="logout-link" @click="handleLogout">Logout</a></li>
        </template>
      </ul>
    </transition>
  </header>
</template>

<script setup>
// Komponen header navigasi utama
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Menu, X } from '@lucide/vue'
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
// State panel navigasi mobile (dibuka lewat tombol hamburger).
const mobileOpen = ref(false)
// Jaga-jaga: setiap pindah halaman, pastikan semua menu (dropdown & panel mobile) tertutup.
watch(() => route.path, () => {
  layananOpen.value = false
  profilOpen.value = false
  mobileOpen.value = false
})

// Laporan di navbar khusus role Admin (permintaan maintenance Fase 6–7).
const isAdmin = computed(() => auth.user?.role === 'admin')

// Panel Kelola: Admin & Supervisor (Supervisor melihat subset area, difilter di dalam panel).
const bisaPanel = computed(() => ['admin', 'supervisor'].includes(auth.user?.role))

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
  mobileOpen.value = false
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

/* ===== Navigasi mobile (hamburger) — pola baru, tidak ada di versi desktop ===== */
/* Cluster kanan (lonceng + hamburger) & panel mobile disembunyikan default (tampilan desktop). */
.nav-mobile-actions {
  display: none;
  align-items: center;
  gap: 14px;
}

.hamburger-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  color: var(--bs-navy);
  cursor: pointer;
  padding: 4px;
}

.nav-mobile {
  display: none;
  flex-direction: column;
  background-color: #fff;
  padding: 8px 20px 16px;
  box-shadow: 0 8px 12px -4px rgba(0, 0, 0, 0.12);
}

.nav-mobile li a {
  display: block;
  padding: 12px 4px;
  color: var(--bs-navy);
  font-weight: 600;
}

.nav-mobile li.activenav a {
  color: var(--bs-yellow);
}

/* Judul grup "Layanan Akademik" di panel mobile (bukan tautan) */
.nav-mobile-group {
  padding: 12px 4px 4px;
  font-size: 0.78em;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #9aa0a6;
}

.nav-mobile-sub a {
  padding-left: 16px !important;
  font-weight: 500 !important;
}

.nav-mobile-divider {
  border-top: 1px solid var(--bs-grey2);
  margin: 8px 0;
}

.logout-link {
  cursor: pointer;
}

.mobile-nav-slide-enter-active,
.mobile-nav-slide-leave-active {
  transition: all 0.2s ease;
}

.mobile-nav-slide-enter-from,
.mobile-nav-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Navbar bisa memuat hingga ~7 item (admin) + lonceng + avatar, jadi collapse cukup dini
   di 980px (breakpoint yang sudah dipakai home-page.vue) agar tidak berdesakan. */
@media (max-width: 980px) {
  .nav-desktop {
    display: none;
  }
  .nav-mobile-actions {
    display: flex;
  }
  .nav-mobile {
    display: flex;
  }
}

/* Padding nav sedikit dikecilkan di layar sangat sempit agar logo + aksi muat rapi. */
@media (max-width: 480px) {
  nav {
    padding: 16px 20px;
  }
  nav .logo h1 {
    font-size: 1.6em;
  }
}
</style>
