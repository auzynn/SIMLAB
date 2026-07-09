// Service Katalog Sertifikasi — endpoint /api/sertifikasi (3_SDD.md 5.13, SRS UC-05).
// Read terbuka untuk semua role login; CUD via Gate manage-master-data (Admin/Supervisor).
import api from './api'

export const sertifikasiService = {
  list() {
    return api.get('/api/sertifikasi')
  },
  create(payload) {
    return api.post('/api/sertifikasi', payload)
  },
  update(id, payload) {
    return api.patch(`/api/sertifikasi/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/api/sertifikasi/${id}`)
  },
}
