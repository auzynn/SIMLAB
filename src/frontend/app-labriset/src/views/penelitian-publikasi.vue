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
          <h1>Penelitian dan Publikasi</h1>
          <div class="profil-title"></div>
        </div>

        <div class="mt-30">
          <p>Hasil penelitian dan publikasi ilmiah dosen, meliputi artikel jurnal, prosiding konferensi, serta luaran riset di bidang jaringan, keamanan siber, dan forensik digital. Bagian ini dapat menjadi rujukan mahasiswa dalam menentukan topik tugas akhir.</p>
        </div>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman Penelitian dan Publikasi dosen — kartu identitas seragam (DosenIdentityCard).
import { useRoute } from 'vue-router'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuDosen from '@/components/sidemenu-dosen.vue'
import FooterComponent from '@/components/footer-component.vue'
import DosenIdentityCard from '@/components/dosen-identity-card.vue'
import { useDosen } from '@/composables/use-dosen'

const route = useRoute()
const { dosen } = useDosen(route.query.dosen)
</script>
