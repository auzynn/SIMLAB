<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container bg-grey">
      <div class="card-bio flex-h between">
        <div class="dosen-photo-250">
          <img :src="fotoUrl" :alt="dosen?.user?.name" referrerpolicy="no-referrer" />
        </div>

        <div class="side-table">
          <h2>{{ dosen?.user?.name }}</h2>
          <p v-if="bidangMinat" class="mt-20"><strong>Bidang Minat:</strong> {{ bidangMinat }}</p>
        </div>
      </div>
    </div>

    <div class="main-container flex-h between">
      <SidemenuDosen />

      <div class="profil-container">
        <div>
          <h1>Roadmap Penelitian Dosen</h1>
          <div class="profil-title"></div>
        </div>

        <div class="mt-30">
          <MarkdownContent v-if="dosen?.roadmap_riset" :source="dosen.roadmap_riset" />
          <p v-else-if="!loading">Roadmap penelitian belum tersedia.</p>
        </div>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Roadmap PENELITIAN DOSEN (per dosen) — dinamis dari kolom dosen.roadmap_riset.
// Berbeda dari Roadmap Laboratorium (info_lab roadmap_kk) yang bersifat tingkat KK.
// Id dosen dibawa lewat query ?dosen=<id> dari menu samping detail dosen.
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuDosen from '@/components/sidemenu-dosen.vue'
import FooterComponent from '@/components/footer-component.vue'
import MarkdownContent from '@/components/markdown-content.vue'
import { dosenService } from '@/services/dosen'
import fotoFallback from '@/assets/foto-dosen/nur-widiyasono.jpg'

const route = useRoute()
const dosen = ref(null)
const loading = ref(true)

const fotoUrl = computed(() => dosen.value?.foto || dosen.value?.user?.avatar || fotoFallback)

const bidangMinat = computed(() => {
  const v = dosen.value?.bidang_minat
  return Array.isArray(v) && v.length ? v.map((b) => b.nama).join(', ') : ''
})

onMounted(async () => {
  const id = route.query.dosen
  if (!id) {
    loading.value = false
    return
  }
  try {
    const res = await dosenService.get(id)
    dosen.value = res.data.data
  } catch {
    dosen.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
td {
  vertical-align: top;
}
</style>
