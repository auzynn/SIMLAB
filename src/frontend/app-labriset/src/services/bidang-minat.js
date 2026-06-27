// Service master Bidang Minat — endpoint /api/bidang-minat.
// Read terbuka untuk semua role login (dipakai dropdown Edit Profil Dosen);
// CUD via Gate manage-bidang-minat (Admin/Supervisor).
import api from './api'

export const bidangMinatService = {
  list() {
    return api.get('/api/bidang-minat')
  },
  create(nama) {
    return api.post('/api/bidang-minat', { nama })
  },
  update(id, nama) {
    return api.patch(`/api/bidang-minat/${id}`, { nama })
  },
  remove(id) {
    return api.delete(`/api/bidang-minat/${id}`)
  },
}
