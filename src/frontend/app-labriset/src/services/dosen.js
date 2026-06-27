// Service profil dosen — endpoint /api/dosen (3_SDD.md 5.3)
import api from './api'

export const dosenService = {
  // Daftar semua dosen (publik) — halaman Daftar Dosen
  getAll() {
    return api.get('/api/dosen')
  },

  // Detail satu dosen (publik) — halaman Biografi/Detail Dosen
  get(id) {
    return api.get(`/api/dosen/${id}`)
  },

  // Update profil dosen (pemilik atau Admin/Supervisor)
  update(id, payload) {
    return api.patch(`/api/dosen/${id}`, payload)
  },
}
