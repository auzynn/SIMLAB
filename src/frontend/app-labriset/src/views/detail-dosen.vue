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
          <h1>Biografi</h1>
          <div class="profil-title"></div>
        </div>

        <div class="mt-30">
          <p v-if="dosen?.biografi">{{ dosen.biografi }}</p>
          <p v-else-if="!loading">Biografi belum tersedia.</p>
        </div>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman Biografi/Detail dosen — kartu identitas dipakai bersama (DosenIdentityCard),
// data dimuat & di-cache lewat useDosen agar konsisten dengan sub-halaman lain.
import { useRoute } from 'vue-router'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuDosen from '@/components/sidemenu-dosen.vue'
import FooterComponent from '@/components/footer-component.vue'
import DosenIdentityCard from '@/components/dosen-identity-card.vue'
import { useDosen } from '@/composables/use-dosen'

const route = useRoute()
const { dosen, loading } = useDosen(route.params.id)
</script>
