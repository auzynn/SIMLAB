<template>
  <teleport to="body">
    <transition name="confirm-fade">
      <div v-if="store.confirmState" class="confirm-overlay" @click.self="cancel">
        <div class="confirm-card" :class="`variant-${store.confirmState.variant}`">
          <!-- Gaya 1: ikon lingkaran berwarna di tengah-atas (mengikuti jenis aksi). -->
          <span class="confirm-ic">
            <component :is="iconFor(store.confirmState.variant)" />
          </span>
          <h3 class="confirm-title">{{ store.confirmState.title }}</h3>
          <p class="confirm-msg">{{ store.confirmState.message }}</p>
          <div class="confirm-actions">
            <button class="btn btn-navy-border" style="width: auto; padding: 8px 22px" @click="cancel">
              {{ store.confirmState.cancelText }}
            </button>
            <button
              class="btn confirm-btn-go"
              style="width: auto; padding: 8px 22px; color: #fff"
              :class="`go-${store.confirmState.variant}`"
              @click="confirm"
            >
              {{ store.confirmState.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
// Dialog konfirmasi global — pengganti window.confirm(). Cukup dipasang SEKALI di App.vue.
// Dikendalikan lewat composable useFeedback().confirmDialog(...), lihat use-feedback.js.
// Gaya "ikon tengah": ikon lingkaran berwarna di atas, judul & pesan rata tengah.
import { h } from 'vue'
import { useFeedbackStore } from '@/stores/feedback'

const store = useFeedbackStore()

const svg = (children) =>
  h('svg', { viewBox: '0 0 24 24', width: 28, height: 28, fill: 'none', stroke: 'currentColor', 'stroke-width': 2.2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, children)

// Ikon per varian — selaras dengan toast-container.vue.
const icons = {
  danger: () => svg([h('path', { d: 'M3 6h18' }), h('path', { d: 'M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6' }), h('path', { d: 'M10 11v6' }), h('path', { d: 'M14 11v6' }), h('path', { d: 'M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2' })]),
  warning: () => svg([h('path', { d: 'M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z' }), h('path', { d: 'M12 9v4' }), h('path', { d: 'M12 17h.01' })]),
  info: () => svg([h('circle', { cx: 12, cy: 12, r: 10 }), h('path', { d: 'M12 16v-4' }), h('path', { d: 'M12 8h.01' })]),
  success: () => svg([h('path', { d: 'M22 11.08V12a10 10 0 1 1-5.93-9.14' }), h('polyline', { points: '22 4 12 14.01 9 11.01' })]),
}

function iconFor(variant) {
  return icons[variant] || icons.danger
}

function confirm() {
  store.resolveConfirm(true)
}
function cancel() {
  store.resolveConfirm(false)
}
</script>

<style scoped>
.confirm-overlay {
  position: fixed;
  inset: 0;
  z-index: 1001;
  background-color: rgba(20, 30, 39, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.confirm-card {
  width: 100%;
  max-width: 410px;
  background-color: #fff;
  border-radius: 10px;
  padding: 28px 24px 22px;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
  text-align: center;
}

/* Ikon lingkaran berwarna di tengah-atas. */
.confirm-ic {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  margin: 0 auto 16px;
  border-radius: 50%;
}
.variant-danger .confirm-ic {
  color: #c0392b;
  background-color: #fdecea;
}
.variant-warning .confirm-ic {
  color: #c47408;
  background-color: #fff6e9;
}
.variant-info .confirm-ic {
  color: var(--bs-navy);
  background-color: #eef1f7;
}
.variant-success .confirm-ic {
  color: #1e7e34;
  background-color: #e7f4ec;
}

.confirm-title {
  font-family: 'Poppins', sans-serif;
  color: var(--bs-navy);
  font-size: 1.15em;
  margin-bottom: 8px;
}

.confirm-msg {
  font-size: 0.92em;
  color: #374151;
  line-height: 1.55;
  margin-bottom: 24px;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 10px;
}

/* Tombol aksi utama — warna mengikuti varian. */
.confirm-btn-go {
  border: 2px solid transparent;
  transition: 0.18s ease-in;
}
.go-danger {
  background-color: #c0392b;
  border-color: #c0392b;
}
.go-danger:hover {
  background-color: #a83224;
  border-color: #a83224;
}
.go-warning {
  background-color: #c47408;
  border-color: #c47408;
}
.go-warning:hover {
  background-color: #a5620a;
  border-color: #a5620a;
}
.go-info {
  background-color: var(--bs-navy);
  border-color: var(--bs-navy);
}
.go-info:hover {
  background-color: #12294a;
  border-color: #12294a;
}
.go-success {
  background-color: #1e7e34;
  border-color: #1e7e34;
}
.go-success:hover {
  background-color: #19692b;
  border-color: #19692b;
}

.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.18s ease;
}
.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}
.confirm-fade-enter-active .confirm-card {
  animation: confirm-pop 0.2s ease;
}
@keyframes confirm-pop {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
</style>
