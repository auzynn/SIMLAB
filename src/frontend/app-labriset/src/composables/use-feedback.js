// Composable pengganti window.alert()/confirm(). Pakai ini di <script setup>, bukan store
// langsung, supaya pemanggilan di view tetap ringkas:
//
//   const { notify, confirmDialog } = useFeedback()
//   notify.success('Berhasil disimpan')
//   notify.error('Gagal: ' + pesan)
//   if (await confirmDialog({ message: `Hapus user "${u.name}"?` })) { ... }
//
import { useFeedbackStore } from '@/stores/feedback'

export function useFeedback() {
  const store = useFeedbackStore()

  const notify = {
    success: (message, opts = {}) => store.pushToast({ type: 'success', message, ...opts }),
    error: (message, opts = {}) => store.pushToast({ type: 'error', message, timeout: 6000, ...opts }),
    info: (message, opts = {}) => store.pushToast({ type: 'info', message, ...opts }),
    warning: (message, opts = {}) => store.pushToast({ type: 'warning', message, ...opts }),
  }

  // Pengganti window.confirm() — async, sehingga dipakai dengan `await`.
  function confirmDialog(options) {
    const opts = typeof options === 'string' ? { message: options } : options
    return store.askConfirm(opts)
  }

  return { notify, confirmDialog }
}
