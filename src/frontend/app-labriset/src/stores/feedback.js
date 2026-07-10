// Store feedback UI (toast + dialog konfirmasi + dialog input) — pengganti window.alert()/
// confirm()/prompt() bawaan browser yang tampilannya tidak bisa dikustom dan menghentikan
// thread JS. Dipakai lewat composable useFeedback() (lihat composables/use-feedback.js),
// bukan store ini secara langsung, supaya API-nya ringkas di komponen
// (notify.success(...), confirmDialog(...), promptDialog(...)).
import { defineStore } from 'pinia'
import { ref } from 'vue'

let seqId = 0

export const useFeedbackStore = defineStore('feedback', () => {
  const toasts = ref([])
  // State dialog konfirmasi tunggal (hanya boleh 1 aktif di satu waktu, seperti confirm() asli).
  const confirmState = ref(null) // { title, message, confirmText, cancelText, variant, resolve }
  // State dialog input tunggal (pengganti prompt()).
  const promptState = ref(null) // { title, message, placeholder, defaultValue, confirmText, cancelText, resolve }

  // Auto-dismiss + progress bar + jeda hover diurus oleh toast-item.vue; store hanya menyimpan
  // datanya (termasuk `timeout` agar durasi progress bar konsisten).
  function pushToast({ type = 'info', message, timeout = 4000 }) {
    const id = ++seqId
    toasts.value.push({ id, type, message, timeout })
    return id
  }

  function dismissToast(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  // Mengembalikan Promise<boolean> — resolve(true) jika user klik konfirmasi, false jika batal.
  function askConfirm({ title = 'Konfirmasi', message, confirmText = 'Ya, lanjutkan', cancelText = 'Batal', variant = 'danger' }) {
    return new Promise((resolve) => {
      confirmState.value = { title, message, confirmText, cancelText, variant, resolve }
    })
  }

  function resolveConfirm(result) {
    if (!confirmState.value) return
    confirmState.value.resolve(result)
    confirmState.value = null
  }

  // Mengembalikan Promise<string|null> — resolve(nilai) jika user klik simpan, null jika batal.
  function askPrompt({ title = 'Masukkan Data', message = '', placeholder = '', defaultValue = '', confirmText = 'Simpan', cancelText = 'Batal' }) {
    return new Promise((resolve) => {
      promptState.value = { title, message, placeholder, defaultValue, confirmText, cancelText, resolve }
    })
  }

  function resolvePrompt(result) {
    if (!promptState.value) return
    promptState.value.resolve(result)
    promptState.value = null
  }

  return {
    toasts, confirmState, promptState,
    pushToast, dismissToast,
    askConfirm, resolveConfirm,
    askPrompt, resolvePrompt,
  }
})
