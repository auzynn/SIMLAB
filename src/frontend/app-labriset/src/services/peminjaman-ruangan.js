// Service Peminjaman Ruangan — endpoint /api/peminjaman-ruangan (SRS UC-02).
// Ajukan: Mahasiswa/Dosen. Approve/reject: Admin/Supervisor.
import api from './api'

export const peminjamanRuanganService = {
  // List pengajuan (backend memfilter: milik sendiri vs semua sesuai role).
  list() {
    return api.get('/api/peminjaman-ruangan')
  },
  // Data kalender ketersediaan: { peminjaman: [...], kelas_lab: [...] }.
  kalender() {
    return api.get('/api/peminjaman-ruangan/kalender')
  },
  create(payload) {
    return api.post('/api/peminjaman-ruangan', payload)
  },
  approve(id) {
    return api.patch(`/api/peminjaman-ruangan/${id}/approve`)
  },
  reject(id) {
    return api.patch(`/api/peminjaman-ruangan/${id}/reject`)
  },
  remove(id) {
    return api.delete(`/api/peminjaman-ruangan/${id}`)
  },
}
