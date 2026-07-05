<template>
  <div id="app">
    <!-- ---------- NAVBAR (disembunyikan pada halaman dengan meta.hideHeader, mis. login) ---------- -->
    <HeaderComponent v-if="!route.meta.hideHeader" />
    <!-- ---------- NAVBAR END ---------- -->
    <router-view />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import HeaderComponent from '@/components/header-component.vue'

const route = useRoute()
</script>

<style>
@import 'assets/style/style.css';
</style>
