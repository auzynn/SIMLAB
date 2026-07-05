// Service Peminjaman Perangkat — endpoint /api/peminjaman-perangkat & /api/perpanjangan (SRS UC-03).
// Ajukan: Mahasiswa. Approve/reject/kembalikan & perpanjangan: Admin/Supervisor.
import api from './api'

export const peminjamanPerangkatService = {
  // List pengajuan (backend memfilter: milik sendiri vs semua sesuai role).
  list() {
    return api.get('/api/peminjaman-perangkat')
  },
  create(payload) {
    return api.post('/api/peminjaman-perangkat', payload)
  },
  // Batalkan pengajuan sendiri saat masih menunggu (pemilik) / hapus (Admin/Supervisor).
  remove(id) {
    return api.delete(`/api/peminjaman-perangkat/${id}`)
  },
  approve(id) {
    return api.patch(`/api/peminjaman-perangkat/${id}/approve`)
  },
  reject(id) {
    return api.patch(`/api/peminjaman-perangkat/${id}/reject`)
  },
  kembalikan(id) {
    return api.patch(`/api/peminjaman-perangkat/${id}/kembalikan`)
  },
  ajukanPerpanjangan(id, payload) {
    return api.post(`/api/peminjaman-perangkat/${id}/perpanjangan`, payload)
  },
  approvePerpanjangan(id) {
    return api.patch(`/api/perpanjangan/${id}/approve`)
  },
  rejectPerpanjangan(id) {
    return api.patch(`/api/perpanjangan/${id}/reject`)
  },
}
