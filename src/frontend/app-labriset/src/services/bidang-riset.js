// Service master Bidang Riset — endpoint /api/bidang-riset.
// Read terbuka untuk semua role login (dipakai dropdown Edit Profil Dosen);
// CUD via Gate manage-bidang-riset (Admin/Supervisor).
import api from './api'

export const bidangRisetService = {
  list() {
    return api.get('/api/bidang-riset')
  },
  create(nama) {
    return api.post('/api/bidang-riset', { nama })
  },
  update(id, nama) {
    return api.patch(`/api/bidang-riset/${id}`, { nama })
  },
  remove(id) {
    return api.delete(`/api/bidang-riset/${id}`)
  },
}
