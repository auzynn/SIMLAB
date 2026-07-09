<template>
  <!-- Stack toast pojok kanan-atas. Teleport ke body supaya z-index tidak kebentur elemen lain
       (mis. sidemenu / header yang sticky) di halaman manapun komponen ini dipanggil. -->
  <teleport to="body">
    <div class="toast-stack" role="status" aria-live="polite">
      <transition-group name="toast-pop">
        <div v-for="t in store.toasts" :key="t.id" class="toast-item" :class="`toast-${t.type}`">
          <span class="toast-ic">
            <component :is="iconFor(t.type)" />
          </span>
          <p class="toast-msg">{{ t.message }}</p>
          <button class="toast-close" title="Tutup" @click="store.dismissToast(t.id)">&times;</button>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup>
// Kontainer toast global — cukup dipasang SEKALI di App.vue. Isinya dikendalikan lewat
// composable `useFeedback()` (notify.success/error/info/warning), bukan lewat props di sini.
import { h } from 'vue'
import { useFeedbackStore } from '@/stores/feedback'

const store = useFeedbackStore()

const svg = (children) =>
  h('svg', { viewBox: '0 0 24 24', width: 16, height: 16, fill: 'none', stroke: 'currentColor', 'stroke-width': 2.4, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, children)

const icons = {
  success: () => svg([h('polyline', { points: '20 6 9 17 4 12' })]),
  error: () => svg([h('line', { x1: 18, y1: 6, x2: 6, y2: 18 }), h('line', { x1: 6, y1: 6, x2: 18, y2: 18 })]),
  warning: () => svg([h('path', { d: 'M12 9v4' }), h('path', { d: 'M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z' }), h('path', { d: 'M12 17h.01' })]),
  info: () => svg([h('circle', { cx: 12, cy: 12, r: 10 }), h('path', { d: 'M12 16v-4' }), h('path', { d: 'M12 8h.01' })]),
}

function iconFor(type) {
  return icons[type] || icons.info
}
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

.toast-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px 16px;
  background-color: #fff;
  border-radius: 8px;
  border-left: 4px solid var(--bs-navy);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
}

.toast-ic {
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 1px;
}

.toast-msg {
  flex: 1;
  font-size: 0.9em;
  color: var(--bs-black);
  line-height: 1.4;
  word-break: break-word;
}

.toast-close {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 1.2em;
  line-height: 1;
  cursor: pointer;
  flex-shrink: 0;
}
.toast-close:hover {
  color: var(--bs-black);
}

/* Palet identik dengan notification-bell.vue (ic-blue/green/red/amber) agar konsisten. */
.toast-success {
  border-left-color: #1e7e34;
}
.toast-success .toast-ic {
  color: #1e7e34;
  background-color: #e7f4ec;
}

.toast-error {
  border-left-color: #c0392b;
}
.toast-error .toast-ic {
  color: #c0392b;
  background-color: #fdecea;
}

.toast-warning {
  border-left-color: #c47408;
}
.toast-warning .toast-ic {
  color: #c47408;
  background-color: #fff6e9;
}

.toast-info .toast-ic {
  color: var(--bs-navy);
  background-color: #eef1f7;
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
