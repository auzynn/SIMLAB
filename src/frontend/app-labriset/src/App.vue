<template>
  <div id="app">
    <!-- ---------- NAVBAR (disembunyikan pada halaman dengan meta.hideHeader, mis. login) ---------- -->
    <HeaderComponent v-if="!route.meta.hideHeader" />
    <!-- ---------- NAVBAR END ---------- -->
    <router-view />

    <!-- ---------- FEEDBACK UI GLOBAL: toast + dialog konfirmasi + input (ganti alert()/confirm()/prompt()) ---------- -->
    <ToastContainer />
    <ConfirmDialog />
    <InputDialog />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import HeaderComponent from '@/components/header-component.vue'
import ToastContainer from '@/components/toast-container.vue'
import ConfirmDialog from '@/components/confirm-dialog.vue'
import InputDialog from '@/components/input-dialog.vue'

const route = useRoute()
</script>

<style>
@import 'assets/style/style.css';
</style>
