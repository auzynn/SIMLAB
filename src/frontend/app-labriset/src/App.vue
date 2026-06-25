<template>
  <div id="app">
    <!-- ---------- NAVBAR ---------- -->
    <HeaderComponent />
    <!-- ---------- NAVBAR END ---------- -->
    <router-view />
  </div>
</template>

<script setup>
import HeaderComponent from '@/components/header-component.vue'
</script>

<style>
@import 'assets/style/style.css';
</style>
