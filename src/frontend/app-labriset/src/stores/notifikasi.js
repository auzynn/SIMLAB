// Store notifikasi in-app (SRS UC-07). Menyimpan daftar + jumlah belum dibaca.
// unreadCount diseed dari GET /api/auth/me (badge tampil tanpa request tambahan),
// lalu disegarkan saat panel dibuka (fetch daftar penuh).
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notifikasiService } from '@/services/notifikasi'

export const useNotifikasiStore = defineStore('notifikasi', () => {
  const items = ref([])
  const unreadCount = ref(0)
  const loaded = ref(false)

  // Seed jumlah belum dibaca dari response me() (dipanggil saat data user tersedia).
  function seed(count) {
    unreadCount.value = count || 0
  }

  async function fetch() {
    const { data } = await notifikasiService.list()
    items.value = data.data
    unreadCount.value = data.unread_count
    loaded.value = true
  }

  async function markRead(id) {
    await notifikasiService.read(id)
    const n = items.value.find((i) => i.id === id)
    if (n && !n.is_read) {
      n.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }

  async function markAllRead() {
    await notifikasiService.readAll()
    items.value.forEach((i) => (i.is_read = true))
    unreadCount.value = 0
  }

  async function remove(id) {
    await notifikasiService.remove(id)
    const n = items.value.find((i) => i.id === id)
    if (n && !n.is_read) unreadCount.value = Math.max(0, unreadCount.value - 1)
    items.value = items.value.filter((i) => i.id !== id)
  }

  // Bersihkan state saat logout agar tak bocor ke sesi berikutnya.
  function reset() {
    items.value = []
    unreadCount.value = 0
    loaded.value = false
  }

  return { items, unreadCount, loaded, seed, fetch, markRead, markAllRead, remove, reset }
})
