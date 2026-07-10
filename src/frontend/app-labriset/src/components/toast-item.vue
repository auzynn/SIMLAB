<template>
  <div class="toast-item" :class="`toast-${toast.type}`" @mouseenter="pause" @mouseleave="resume">
    <span class="toast-ic"><component :is="icon" /></span>
    <p class="toast-msg">{{ toast.message }}</p>
    <button class="toast-close" title="Tutup" @click="close">&times;</button>
    <span
      v-if="toast.timeout > 0"
      class="toast-bar"
      :style="{ animationDuration: toast.timeout + 'ms', animationPlayState: paused ? 'paused' : 'running' }"
    ></span>
  </div>
</template>

<script setup>
// Satu item toast — mengelola auto-dismiss + progress bar + jeda saat hover sendiri,
// supaya store tetap sederhana (hanya menyimpan daftar). Dipakai oleh toast-container.vue.
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue'
import { useFeedbackStore } from '@/stores/feedback'

const props = defineProps({ toast: { type: Object, required: true } })
const store = useFeedbackStore()

const svg = (children) =>
  h('svg', { viewBox: '0 0 24 24', width: 15, height: 15, fill: 'none', stroke: 'currentColor', 'stroke-width': 2.4, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, children)

const icons = {
  success: () => svg([h('polyline', { points: '20 6 9 17 4 12' })]),
  error: () => svg([h('line', { x1: 18, y1: 6, x2: 6, y2: 18 }), h('line', { x1: 6, y1: 6, x2: 18, y2: 18 })]),
  warning: () => svg([h('path', { d: 'M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z' }), h('path', { d: 'M12 9v4' }), h('path', { d: 'M12 17h.01' })]),
  info: () => svg([h('circle', { cx: 12, cy: 12, r: 10 }), h('path', { d: 'M12 16v-4' }), h('path', { d: 'M12 8h.01' })]),
}
const icon = computed(() => icons[props.toast.type] || icons.info)

// --- Timer auto-dismiss dengan jeda saat hover ---
const paused = ref(false)
let timer = null
let remaining = props.toast.timeout
let startedAt = 0

function start() {
  if (remaining <= 0) return
  startedAt = performance.now()
  timer = setTimeout(close, remaining)
}
function pause() {
  if (!timer) return
  clearTimeout(timer)
  timer = null
  paused.value = true
  remaining -= performance.now() - startedAt
}
function resume() {
  if (props.toast.timeout <= 0) return
  paused.value = false
  start()
}
function close() {
  if (timer) clearTimeout(timer)
  store.dismissToast(props.toast.id)
}

onMounted(start)
onBeforeUnmount(() => { if (timer) clearTimeout(timer) })
</script>

<style scoped>
.toast-item {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px 16px;
  background-color: #fff;
  border-radius: 8px;
  border-left: 4px solid var(--bs-navy);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
  overflow: hidden;
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

/* Progress bar auto-dismiss */
.toast-bar {
  position: absolute;
  left: 0;
  bottom: 0;
  height: 3px;
  width: 100%;
  transform-origin: left;
  background-color: var(--bs-navy);
  animation-name: toast-bar-shrink;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}
@keyframes toast-bar-shrink {
  from { transform: scaleX(1); }
  to { transform: scaleX(0); }
}

/* Palet identik dengan notification-bell.vue (ic-blue/green/red/amber) agar konsisten. */
.toast-success { border-left-color: #1e7e34; }
.toast-success .toast-ic { color: #1e7e34; background-color: #e7f4ec; }
.toast-success .toast-bar { background-color: #1e7e34; }

.toast-error { border-left-color: #c0392b; }
.toast-error .toast-ic { color: #c0392b; background-color: #fdecea; }
.toast-error .toast-bar { background-color: #c0392b; }

.toast-warning { border-left-color: #c47408; }
.toast-warning .toast-ic { color: #c47408; background-color: #fff6e9; }
.toast-warning .toast-bar { background-color: #c47408; }

.toast-info .toast-ic { color: var(--bs-navy); background-color: #eef1f7; }
.toast-info .toast-bar { background-color: var(--bs-navy); }
</style>
