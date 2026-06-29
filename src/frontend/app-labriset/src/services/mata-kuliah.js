// Service Data Master Mata Kuliah — endpoint /api/mata-kuliah.
// Read terbuka untuk semua role login (dipakai Dosen saat membuka Kelas Lab);
// CUD via Gate manage-master-data (Admin/Supervisor).
import api from './api'

export const mataKuliahService = {
  list() {
    return api.get('/api/mata-kuliah')
  },
  create(payload) {
    return api.post('/api/mata-kuliah', payload)
  },
  update(id, payload) {
    return api.patch(`/api/mata-kuliah/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/api/mata-kuliah/${id}`)
  },
}
