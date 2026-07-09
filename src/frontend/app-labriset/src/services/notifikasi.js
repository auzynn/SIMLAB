// Service Notifikasi In-App — endpoint /api/notifikasi (3_SDD.md 5.14, SRS UC-07).
// Semua aksi hanya menyentuh notifikasi milik user yang login.
import api from './api'

export const notifikasiService = {
  list() {
    return api.get('/api/notifikasi')
  },
  read(id) {
    return api.patch(`/api/notifikasi/${id}/read`)
  },
  readAll() {
    return api.patch('/api/notifikasi/read-all')
  },
  remove(id) {
    return api.delete(`/api/notifikasi/${id}`)
  },
}
