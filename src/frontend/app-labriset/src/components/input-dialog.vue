<template>
  <teleport to="body">
    <transition name="input-fade">
      <div v-if="store.promptState" class="input-overlay" @click.self="cancel">
        <div class="input-card">
          <span class="input-ic">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 20h9" />
              <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
            </svg>
          </span>
          <h3 class="input-title">{{ store.promptState.title }}</h3>
          <p v-if="store.promptState.message" class="input-msg">{{ store.promptState.message }}</p>
          <input
            ref="fieldRef"
            v-model="value"
            class="input-field"
            :placeholder="store.promptState.placeholder"
            @keyup.enter="confirm"
            @keyup.esc="cancel"
          />
          <div class="input-actions">
            <button class="btn btn-navy-border" style="width: auto; padding: 8px 22px" @click="cancel">
              {{ store.promptState.cancelText }}
            </button>
            <button class="btn btn-navy-solid" style="width: auto; padding: 8px 22px" @click="confirm">
              {{ store.promptState.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
// Dialog input global — pengganti window.prompt(). Cukup dipasang SEKALI di App.vue.
// Dikendalikan lewat composable useFeedback().promptDialog(...), lihat use-feedback.js.
import { nextTick, ref, watch } from 'vue'
import { useFeedbackStore } from '@/stores/feedback'

const store = useFeedbackStore()
const value = ref('')
const fieldRef = ref(null)

// Saat dialog dibuka, isi nilai awal & fokus ke input.
watch(
  () => store.promptState,
  (state) => {
    if (state) {
      value.value = state.defaultValue || ''
      nextTick(() => fieldRef.value?.focus())
    }
  }
)

function confirm() {
  store.resolvePrompt(value.value.trim())
}
function cancel() {
  store.resolvePrompt(null)
}
</script>

<style scoped>
.input-overlay {
  position: fixed;
  inset: 0;
  z-index: 1002;
  background-color: rgba(20, 30, 39, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.input-card {
  width: 100%;
  max-width: 410px;
  background-color: #fff;
  border-radius: 10px;
  padding: 28px 24px 22px;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
  text-align: center;
}

.input-ic {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  margin: 0 auto 16px;
  border-radius: 50%;
  color: var(--bs-navy);
  background-color: #eef1f7;
}

.input-title {
  font-family: 'Poppins', sans-serif;
  color: var(--bs-navy);
  font-size: 1.15em;
  margin-bottom: 8px;
}

.input-msg {
  font-size: 0.92em;
  color: #374151;
  line-height: 1.55;
  margin-bottom: 4px;
}

.input-field {
  width: 100%;
  margin: 14px 0 4px;
  padding: 11px 12px;
  border: 1px solid var(--bs-grey2);
  border-radius: 5px;
  font-family: 'Source Sans Pro', sans-serif;
  font-size: 0.95em;
}
.input-field:focus {
  outline: none;
  border-color: var(--bs-navy);
}

.input-actions {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 20px;
}

.input-fade-enter-active,
.input-fade-leave-active {
  transition: opacity 0.18s ease;
}
.input-fade-enter-from,
.input-fade-leave-to {
  opacity: 0;
}
.input-fade-enter-active .input-card {
  animation: input-pop 0.2s ease;
}
@keyframes input-pop {
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
