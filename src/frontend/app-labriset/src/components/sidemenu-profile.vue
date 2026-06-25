<template>
  <div class="side-menu-container">
    <div class="menu-group">
      <router-link to="/kepalalab" class="menu" style="display: block" active-class="activemenu">Kepala Lab</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/visimisi" class="menu" style="display: block" active-class="activemenu">Visi dan Misi</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/listdosen" class="menu" style="display: block" active-class="activemenu">Profil Dosen</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/roadmaplab" class="menu" style="display: block" active-class="activemenu">Roadmap</router-link>
    </div>
  </div>
</template>

<script setup>
// Menu navigasi samping halaman profil.
// Penanda aktif (garis kuning) memakai active-class bawaan router-link agar
// selalu sinkron dengan route saat ini, termasuk saat refresh/redirect/akses langsung.
</script>
