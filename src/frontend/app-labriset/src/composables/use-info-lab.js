// Muat konten satu tipe info lab untuk halaman publik (3_SDD.md 5.12).
// Dipakai bersama beberapa halaman (beranda, visi_misi, kepala_lab, roadmap_kk).
import { ref, onMounted } from 'vue'
import { infoLabService } from '@/services/info-lab'

export function useInfoLab(tipe) {
  const data = ref(null)
  const loading = ref(true)

  onMounted(async () => {
    try {
      const res = await infoLabService.get(tipe)
      data.value = res.data.data
    } catch {
      // Belum ada konten / gagal muat → halaman tampil minimal tanpa error mengganggu
      data.value = null
    } finally {
      loading.value = false
    }
  })

  return { data, loading }
}
