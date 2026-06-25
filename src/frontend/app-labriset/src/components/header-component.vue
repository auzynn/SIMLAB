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
        <li class="nav-hover" :class="{ activenav: menu === 'home' }" @click="menu = 'home'">
          <router-link to="/">Beranda</router-link>
        </li>

        <li class="nav-hover" :class="{ activenav: menu === 'profil' }" @click="menu = 'profil'">
          <router-link to="/kepalalab">Profil</router-link>
        </li>

        <li class="nav-hover" :class="{ activenav: menu === 'jadwal' }" @click="menu = 'jadwal'">
          <router-link to="/jadwallab">Jadwal Lab</router-link>
        </li>

        <li v-if="!auth.isAuthenticated">
          <router-link to="/login" class="btn btn-navy-border btn-width-80" style="font-weight: bold">Login</router-link>
        </li>
        <li v-else>
          <a class="btn btn-navy-border btn-width-80" style="font-weight: bold; cursor: pointer" @click="handleLogout">Logout</a>
        </li>
      </ul>
    </nav>
  </header>
</template>

<script setup>
// Komponen header navigasi utama
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const menu = ref('home')
const auth = useAuthStore()
const router = useRouter()

// Logout: hapus token & sesi, lalu kembali ke beranda
async function handleLogout() {
  await auth.logout()
  router.push('/')
}
</script>
