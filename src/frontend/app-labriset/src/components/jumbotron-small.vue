<template>
  <div class="jumbotron-small">
    <div>
      <h1>{{ title }}</h1>
      <router-link to="/">Beranda</router-link><span> / </span>
      <span>{{ title }}</span>
    </div>
  </div>
</template>

<script setup>
// Jumbotron kecil untuk header sub-halaman.
// Judul & breadcrumb mengikuti prop `title` (default "Profil" agar halaman profil lab tak berubah).
defineProps({
  title: { type: String, default: 'Profil' },
})
</script>
