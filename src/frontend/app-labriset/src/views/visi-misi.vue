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
          <h1>{{ info?.judul || 'Visi dan Misi' }}</h1>
          <div class="profil-title"></div>
        </div>

        <MarkdownContent v-if="info" :source="info.konten" class="mt-30" style="text-align: justify" />
        <p v-else-if="!loading" class="mt-30">Konten belum tersedia.</p>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman visi dan misi laboratorium — konten dinamis dari /api/info-lab/visi_misi (T2.8)
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuProfile from '@/components/sidemenu-profile.vue'
import FooterComponent from '@/components/footer-component.vue'
import MarkdownContent from '@/components/markdown-content.vue'
import { useInfoLab } from '@/composables/use-info-lab'

const { data: info, loading } = useInfoLab('visi_misi')
</script>
