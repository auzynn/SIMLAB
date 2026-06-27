<template>
  <div class="side-menu-container">
    <div class="menu-group">
      <router-link :to="biografiLink" class="menu" style="display: block" active-class="activemenu">Biografi</router-link>
    </div>
    <div class="menu-group">
      <router-link :to="subLink('/credential')" class="menu" style="display: block" active-class="activemenu">Credential</router-link>
    </div>
    <div class="menu-group">
      <router-link :to="subLink('/publikasi')" class="menu" style="display: block" active-class="activemenu">Penelitian dan Publikasi</router-link>
    </div>
    <div class="menu-group">
      <router-link :to="subLink('/buku')" class="menu" style="display: block" active-class="activemenu">Buku</router-link>
    </div>
    <div class="menu-group">
      <router-link :to="subLink('/roadmapdosen')" class="menu" style="display: block" active-class="activemenu">Roadmap Penelitian</router-link>
    </div>
  </div>
</template>

<script setup>
// Menu navigasi samping halaman detail dosen.
// Sub-halaman (Credential/Publikasi/Buku/Roadmap) berbagi konteks dosen yang sedang
// dibuka lewat query `?dosen=<id>` agar tautan "Biografi" tidak kehilangan id-nya
// (sub-route global tetap utuh, tidak perlu di-nest).
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

// Id dosen aktif: dari param route detail (/detaildosen/:id) atau query ?dosen= di sub-halaman
const dosenId = computed(() => route.params.id || route.query.dosen || '')

const biografiLink = computed(() =>
  dosenId.value ? `/detaildosen/${dosenId.value}` : '/listdosen',
)

// Bawa id dosen sebagai query agar konteks tetap saat berpindah antar sub-halaman
function subLink(path) {
  return dosenId.value ? { path, query: { dosen: dosenId.value } } : { path }
}
</script>

<style scoped>
</style>
