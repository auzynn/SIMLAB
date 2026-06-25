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

        <li>
          <router-link to="/login" class="btn btn-navy-border btn-width-80" style="font-weight: bold">Login</router-link>
        </li>
      </ul>
    </nav>
  </header>
</template>

<script setup>
// Komponen header navigasi utama
import { ref } from 'vue'

const menu = ref('home')
</script>
