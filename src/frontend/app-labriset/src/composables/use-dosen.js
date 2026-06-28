// Muat data satu dosen (GET /api/dosen/{id}) dengan cache antar-halaman.
// Dipakai semua sub-halaman dosen (Biografi/Credential/Penelitian/Buku/Roadmap)
// agar berpindah menu tidak memicu loading ulang untuk dosen yang sama.
import { ref } from 'vue'
import { dosenService } from '@/services/dosen'

// Cache sederhana per sesi SPA, dikunci by id dosen
const cache = new Map()

export function useDosen(id) {
  const key = id != null ? String(id) : ''
  const dosen = ref(cache.get(key) || null)
  const loading = ref(!dosen.value)

  if (key && !cache.has(key)) {
    dosenService
      .get(key)
      .then((res) => {
        cache.set(key, res.data.data)
        dosen.value = res.data.data
      })
      .catch(() => {
        dosen.value = null
      })
      .finally(() => {
        loading.value = false
      })
  } else {
    loading.value = false
  }

  return { dosen, loading }
}
