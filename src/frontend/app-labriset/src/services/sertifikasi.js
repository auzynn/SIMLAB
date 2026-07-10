// Service Katalog Sertifikasi — endpoint /api/sertifikasi (3_SDD.md 5.13, SRS UC-05).
// Read terbuka untuk semua role login. Create: Admin/Supervisor/Dosen; Update/Delete:
// Admin/Supervisor (semua) atau Dosen pemilik (created_by) — SertifikasiPolicy.
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
