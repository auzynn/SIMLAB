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
          <h1>Buku</h1>
          <div class="profil-title"></div>
        </div>

        <div class="mt-30">
          <p>Daftar karya buku yang ditulis dan diterbitkan oleh dosen, mencakup judul, tahun terbit, serta penerbit. Bagian ini menjadi rujukan publikasi akademik dosen di Laboratorium Riset KK JKF.</p>
        </div>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman Buku dosen — kartu identitas seragam (DosenIdentityCard).
import { useRoute } from 'vue-router'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuDosen from '@/components/sidemenu-dosen.vue'
import FooterComponent from '@/components/footer-component.vue'
import DosenIdentityCard from '@/components/dosen-identity-card.vue'
import { useDosen } from '@/composables/use-dosen'

const route = useRoute()
const { dosen } = useDosen(route.query.dosen)
</script>
