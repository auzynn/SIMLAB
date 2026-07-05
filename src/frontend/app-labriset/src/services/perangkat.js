// Service Data Master Perangkat — endpoint /api/perangkat (3_SDD.md 5.9).
// Read terbuka untuk semua role login; CUD via Gate manage-master-data (Admin/Supervisor).
import api from './api'

export const perangkatService = {
  list() {
    return api.get('/api/perangkat')
  },
  create(payload) {
    return api.post('/api/perangkat', payload)
  },
  update(id, payload) {
    return api.patch(`/api/perangkat/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/api/perangkat/${id}`)
  },
}
