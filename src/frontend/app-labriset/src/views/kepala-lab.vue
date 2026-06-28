<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container flex-h between">
      <!-- ---------- SIDE MENU ---------- -->
      <SidemenuProfile />
      <!-- ---------- SIDE MENU END ---------- -->

      <div class="flex-v profil-container">
        <div v-if="info?.gambar" class="flex-h around">
          <div class="dosen-photo-250">
            <img :src="info.gambar" alt="Foto Kepala Lab" referrerpolicy="no-referrer" />
          </div>
        </div>
        <div v-if="info?.judul" class="flex-h around mt-30">
          <h2>{{ info.judul }}</h2>
        </div>

        <MarkdownContent v-if="info" :source="info.konten" class="mt-30" />
        <p v-else-if="!loading" class="mt-30">Konten belum tersedia.</p>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman profil Kepala Lab — konten dinamis dari /api/info-lab/kepala_lab (T2.9)
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuProfile from '@/components/sidemenu-profile.vue'
import FooterComponent from '@/components/footer-component.vue'
import MarkdownContent from '@/components/markdown-content.vue'
import { useInfoLab } from '@/composables/use-info-lab'

const { data: info, loading } = useInfoLab('kepala_lab')
</script>

<style scoped>
/* Foto Kepala Lab sedikit lebih kecil dari default global (250px). */
.dosen-photo-250,
.dosen-photo-250 img {
  width: 190px;
  height: 190px;
}
</style>
