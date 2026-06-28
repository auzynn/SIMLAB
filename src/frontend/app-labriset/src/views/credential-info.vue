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
          <h1>Credential</h1>
          <div class="profil-title"></div>
        </div>

        <div class="mt-30">
          <p>Sertifikasi dan kredensial profesional yang dimiliki dosen, antara lain CEH (Certified Ethical Hacker), CHFI (Computer Hacking Forensic Investigator), CITAP, dan MCE. Kredensial ini menunjang keahlian di bidang jaringan, keamanan, dan forensik digital.</p>
        </div>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman Credential dosen — kartu identitas seragam (DosenIdentityCard) dari data dinamis.
import { useRoute } from 'vue-router'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuDosen from '@/components/sidemenu-dosen.vue'
import FooterComponent from '@/components/footer-component.vue'
import DosenIdentityCard from '@/components/dosen-identity-card.vue'
import { useDosen } from '@/composables/use-dosen'

const route = useRoute()
const { dosen } = useDosen(route.query.dosen)
</script>
