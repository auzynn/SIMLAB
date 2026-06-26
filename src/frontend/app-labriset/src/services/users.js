// Service kelola user (Admin only) — endpoint /api/users (3_SDD.md 5.2)
import api from './api'

export const userService = {
  // List user, opsional filter by role
  list(role) {
    return api.get('/api/users', { params: role ? { role } : {} })
  },

  // Buat user baru (Admin/Supervisor/Dosen)
  create(payload) {
    return api.post('/api/users', payload)
  },

  // Update data/role user
  update(id, payload) {
    return api.patch(`/api/users/${id}`, payload)
  },

  // Hapus user
  remove(id) {
    return api.delete(`/api/users/${id}`)
  },
}
