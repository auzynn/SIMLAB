<template>
  <!-- Stack toast pojok kanan-atas. Teleport ke body supaya z-index tidak kebentur elemen lain
       (mis. sidemenu / header yang sticky) di halaman manapun komponen ini dipanggil. -->
  <teleport to="body">
    <div class="toast-stack" role="status" aria-live="polite">
      <transition-group name="toast-pop">
        <ToastItem v-for="t in store.toasts" :key="t.id" :toast="t" />
      </transition-group>
    </div>
  </teleport>
</template>

<script setup>
// Kontainer toast global — cukup dipasang SEKALI di App.vue. Isinya dikendalikan lewat
// composable `useFeedback()` (notify.success/error/info/warning). Tiap item (auto-dismiss,
// progress bar, jeda saat hover) diurus oleh toast-item.vue.
import { useFeedbackStore } from '@/stores/feedback'
import ToastItem from '@/components/toast-item.vue'

const store = useFeedbackStore()
</script>

<style scoped>
.toast-stack {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 360px;
}

/* Animasi masuk/keluar */
.toast-pop-enter-active,
.toast-pop-leave-active {
  transition: all 0.25s ease;
}
.toast-pop-enter-from {
  opacity: 0;
  transform: translateX(30px);
}
.toast-pop-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
