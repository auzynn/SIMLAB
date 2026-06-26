<template>
  <div>
    <!-- ---------- JUMBOTRON ---------- -->
    <JumbotronDefault />
    <!-- ---------- JUMBOTRON END ---------- -->

    <!-- ---------- KONTEN BERANDA (dikelola Admin via panel Konten Info Lab) ---------- -->
    <div v-if="info" class="main-container">
      <div v-if="info.judul">
        <h1>{{ info.judul }}</h1>
        <div class="profil-title"></div>
      </div>
      <img v-if="info.gambar" :src="info.gambar" class="beranda-img mt-30" alt="" />
      <MarkdownContent :source="info.konten" class="mt-30" />
    </div>
    <!-- ---------- KONTEN BERANDA END ---------- -->

    <!-- ---------- PARTNER ---------- -->
    <PartnerComponent />
    <!-- ---------- PARTNER END ---------- -->

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman utama / beranda — konten dinamis dari /api/info-lab/beranda (T2.7)
import JumbotronDefault from '@/components/jumbotron-default.vue'
import PartnerComponent from '@/components/partner.vue'
import FooterComponent from '@/components/footer-component.vue'
import MarkdownContent from '@/components/markdown-content.vue'
import { useInfoLab } from '@/composables/use-info-lab'

const { data: info } = useInfoLab('beranda')
</script>

<style scoped>
.beranda-img {
  max-width: 100%;
  border-radius: 8px;
}
</style>
