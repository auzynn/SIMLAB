<template>
  <div class="side-menu-container">
    <div class="menu-group">
      <router-link to="/kepalalab" class="menu" style="display: block" :class="{ activemenu: menu === 'kalab' }" @click="menu = 'kalab'">Kepala Lab</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/visimisi" class="menu" style="display: block" :class="{ activemenu: menu === 'visi' }" @click="menu = 'visi'">Visi dan Misi</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/listdosen" class="menu" style="display: block" :class="{ activemenu: menu === 'list' }" @click="menu = 'list'">Profil Dosen</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/roadmaplab" class="menu" style="display: block" :class="{ activemenu: menu === 'rmlab' }" @click="menu = 'rmlab'">Roadmap</router-link>
    </div>
  </div>
</template>

<script setup>
// Menu navigasi samping halaman profil
import { ref } from 'vue'

const menu = ref('kalab')
</script>
