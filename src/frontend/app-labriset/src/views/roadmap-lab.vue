<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container flex-h between">
      <!-- ---------- SIDE MENU ---------- -->
      <SidemenuProfile />
      <!-- ---------- SIDE MENU END ---------- -->

      <div class="profil-container">
        <div>
          <h1>{{ info?.judul || 'Roadmap Laboratorium' }}</h1>
          <div class="profil-title"></div>
        </div>

        <div v-if="info?.gambar" class="roadmap-container mt-30">
          <img :src="info.gambar" alt="Roadmap Laboratorium" style="width: 100%; height: 100%; object-fit: contain" />
        </div>

        <MarkdownContent v-if="info?.konten" :source="info.konten" class="mt-30" />
        <p v-else-if="!loading && !info" class="mt-30">Konten belum tersedia.</p>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman roadmap laboratorium — konten dinamis dari /api/info-lab/roadmap_kk (T2.11)
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuProfile from '@/components/sidemenu-profile.vue'
import FooterComponent from '@/components/footer-component.vue'
import MarkdownContent from '@/components/markdown-content.vue'
import { useInfoLab } from '@/composables/use-info-lab'

const { data: info, loading } = useInfoLab('roadmap_kk')
</script>
