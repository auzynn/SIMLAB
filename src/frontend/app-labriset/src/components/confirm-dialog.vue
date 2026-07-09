<template>
  <teleport to="body">
    <transition name="confirm-fade">
      <div v-if="store.confirmState" class="confirm-overlay" @click.self="cancel">
        <div class="confirm-card">
          <h3 class="confirm-title">{{ store.confirmState.title }}</h3>
          <p class="confirm-msg">{{ store.confirmState.message }}</p>
          <div class="confirm-actions">
            <button class="btn btn-navy-border" style="width: auto; padding: 8px 20px" @click="cancel">
              {{ store.confirmState.cancelText }}
            </button>
            <button
              class="btn"
              style="width: auto; padding: 8px 20px; color: #fff"
              :class="store.confirmState.variant === 'danger' ? 'confirm-btn-danger' : 'btn-navy-solid'"
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
import { useFeedbackStore } from '@/stores/feedback'

const store = useFeedbackStore()

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
  max-width: 400px;
  background-color: #fff;
  border-radius: 10px;
  padding: 24px;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
}

.confirm-title {
  font-family: 'Poppins', sans-serif;
  color: var(--bs-navy);
  font-size: 1.1em;
  margin-bottom: 8px;
}

.confirm-msg {
  font-size: 0.92em;
  color: #374151;
  line-height: 1.5;
  margin-bottom: 22px;
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.confirm-btn-danger {
  background-color: #c0392b;
  border: 2px solid #c0392b;
}
.confirm-btn-danger:hover {
  background-color: #a83224;
  border-color: #a83224;
}

.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.18s ease;
}
.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}
</style>
