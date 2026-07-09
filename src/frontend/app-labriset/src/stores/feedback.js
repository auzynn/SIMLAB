// Store feedback UI (toast + dialog konfirmasi) — pengganti window.alert()/confirm() bawaan
// browser yang tampilannya tidak bisa dikustom dan menghentikan thread JS.
// Dipakai lewat composable useFeedback() (lihat composables/use-feedback.js), bukan store ini
// secara langsung, supaya API-nya ringkas di komponen (notify.success(...), confirmDialog(...)).
import { defineStore } from 'pinia'
import { ref } from 'vue'

let seqId = 0

export const useFeedbackStore = defineStore('feedback', () => {
  const toasts = ref([])
  // State dialog konfirmasi tunggal (hanya boleh 1 aktif di satu waktu, seperti confirm() asli).
  const confirmState = ref(null) // { title, message, confirmText, cancelText, variant, resolve }

  function pushToast({ type = 'info', message, timeout = 4000 }) {
    const id = ++seqId
    toasts.value.push({ id, type, message })
    if (timeout > 0) {
      setTimeout(() => dismissToast(id), timeout)
    }
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

  return { toasts, confirmState, pushToast, dismissToast, askConfirm, resolveConfirm }
})
