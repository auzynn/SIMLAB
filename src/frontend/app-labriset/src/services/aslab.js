// Service Delegasi Aslab — endpoint /api/aslab (Admin only).
// Menetapkan mahasiswa jadi Supervisor (Aslab) & mengembalikannya.
import api from './api'

export const aslabService = {
  // { kandidat: [...mahasiswa], aslab: [...supervisor-dari-mahasiswa] }
  list() {
    return api.get('/api/aslab')
  },
  promote(userId) {
    return api.post(`/api/aslab/${userId}`)
  },
  demote(userId) {
    return api.delete(`/api/aslab/${userId}`)
  },
}
