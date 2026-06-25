<template>
  <div>
    <!-- ---------- JUMBOTRON ---------- -->
    <JumbotronDefault />
    <!-- ---------- JUMBOTRON END ---------- -->

    <!-- ---------- PARTNER ---------- -->
    <PartnerComponent />
    <!-- ---------- PARTNER END ---------- -->

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman utama / beranda
import JumbotronDefault from '@/components/jumbotron-default.vue'
import PartnerComponent from '@/components/partner.vue'
import FooterComponent from '@/components/footer-component.vue'
</script>
