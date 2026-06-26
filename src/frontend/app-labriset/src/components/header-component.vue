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

        <!-- Jadwal Lab hanya tampil setelah login -->
        <li v-if="auth.isAuthenticated" class="nav-hover" :class="{ activenav: activeMenu === 'jadwal' }">
          <router-link to="/jadwallab">Jadwal Lab</router-link>
        </li>

        <!-- Belum login: tombol Login -->
        <li v-if="!auth.isAuthenticated">
          <router-link to="/login" class="btn btn-navy-border btn-width-80" style="font-weight: bold">Login</router-link>
        </li>

        <!-- Sudah login: avatar (klik → Profil Saya) dengan dropdown saat di-hover -->
        <li v-else class="user-menu">
          <router-link to="/profil" class="user-avatar" title="Profil Saya">
            <img v-if="auth.user?.avatar" :src="auth.user.avatar" referrerpolicy="no-referrer" alt="Profil" />
            <span v-else>{{ initials }}</span>
          </router-link>
          <ul class="user-dropdown">
            <!-- Akses Panel Admin hanya untuk role admin -->
            <li v-if="auth.user?.role === 'admin'"><router-link to="/admin">Panel Admin</router-link></li>
            <li><router-link to="/profil">Profil Saya</router-link></li>
            <li><a class="logout-link" @click="handleLogout">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>
</template>

<script setup>
// Komponen header navigasi utama
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

// Penanda menu aktif ditentukan dari route saat ini (bukan klik),
// agar highlight selalu sinkron termasuk saat refresh/akses langsung/navigasi lewat avatar.
const profilPaths = [
  '/kepalalab', '/visimisi', '/listdosen', '/roadmaplab',
  '/detaildosen', '/roadmapdosen', '/credential', '/publikasi', '/buku',
]
const activeMenu = computed(() => {
  const path = route.path
  if (path === '/') return 'home'
  if (path === '/jadwallab') return 'jadwal'
  if (profilPaths.includes(path)) return 'profil'
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
  await auth.logout()
  router.push('/')
}
</script>

<style scoped>
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

/* Dropdown muncul saat hover avatar; menempel (top: 100%) agar tak ada celah hover */
.user-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  width: max-content;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  padding: 6px 0;
  display: none;
  flex-direction: column;
  z-index: 20;
}

.user-menu:hover .user-dropdown {
  display: flex;
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
