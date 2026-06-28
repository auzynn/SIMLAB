// Pagination sisi-klien sederhana untuk daftar (mis. Bidang Minat, Kelola User).
// Menerima ref array, mengembalikan potongan per halaman + kontrol halaman.
import { ref, computed, watch } from 'vue'

export function usePagination(itemsRef, perPage = 10) {
  const page = ref(1)

  const totalPages = computed(() =>
    Math.max(1, Math.ceil((itemsRef.value?.length || 0) / perPage)),
  )

  const pagedItems = computed(() => {
    const start = (page.value - 1) * perPage
    return (itemsRef.value || []).slice(start, start + perPage)
  })

  // Jaga halaman tetap valid saat data berubah (mis. setelah hapus/filter)
  watch(totalPages, (tp) => {
    if (page.value > tp) page.value = tp
  })

  return { page, totalPages, pagedItems }
}
