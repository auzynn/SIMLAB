// Service Portofolio Mahasiswa — endpoint /api/portofolio (3_SDD.md 5.14, PRD 3.7).
// Read terbuka untuk semua role login; CUD hanya pemilik (Mahasiswa).
import api from './api'

export const portofolioService = {
  // Tanpa userId → semua portofolio; dengan userId → milik satu mahasiswa.
  list(userId) {
    return api.get('/api/portofolio', { params: userId ? { user_id: userId } : {} })
  },
  create(payload) {
    return api.post('/api/portofolio', payload)
  },
  update(id, payload) {
    return api.patch(`/api/portofolio/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/api/portofolio/${id}`)
  },
}
