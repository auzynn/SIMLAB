<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <DosenIdentityCard :dosen="dosen" />

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
// Kartu identitas seragam dengan sub-halaman dosen lain (DosenIdentityCard).
import { useRoute } from 'vue-router'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuDosen from '@/components/sidemenu-dosen.vue'
import FooterComponent from '@/components/footer-component.vue'
import MarkdownContent from '@/components/markdown-content.vue'
import DosenIdentityCard from '@/components/dosen-identity-card.vue'
import { useDosen } from '@/composables/use-dosen'

const route = useRoute()
const { dosen, loading } = useDosen(route.query.dosen)
</script>
