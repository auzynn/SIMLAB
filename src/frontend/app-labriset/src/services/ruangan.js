// Service Data Master Ruangan — endpoint /api/ruangan.
// Read terbuka untuk semua role login; CUD via Gate manage-master-data (Admin/Supervisor).
import api from './api'

export const ruanganService = {
  list() {
    return api.get('/api/ruangan')
  },
  create(payload) {
    return api.post('/api/ruangan', payload)
  },
  update(id, payload) {
    return api.patch(`/api/ruangan/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/api/ruangan/${id}`)
  },
}
