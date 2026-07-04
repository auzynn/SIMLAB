// Service Kelas Lab/Praktikum — endpoint /api/kelas-lab (SRS UC-02a).
// Buka/kelola: Dosen (milik sendiri)/Supervisor. Daftar peserta: Mahasiswa.
import api from './api'

export const kelasLabService = {
  // List sesi; opsional filter { mata_kuliah_id }.
  list(params = {}) {
    return api.get('/api/kelas-lab', { params })
  },
  show(id) {
    return api.get(`/api/kelas-lab/${id}`)
  },
  create(payload) {
    return api.post('/api/kelas-lab', payload)
  },
  update(id, payload) {
    return api.patch(`/api/kelas-lab/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/api/kelas-lab/${id}`)
  },
  // Mahasiswa daftar / batalkan pendaftaran sesi.
  daftar(id) {
    return api.post(`/api/kelas-lab/${id}/daftar`)
  },
  batalDaftar(id) {
    return api.delete(`/api/kelas-lab/${id}/daftar`)
  },
  peserta(id) {
    return api.get(`/api/kelas-lab/${id}/peserta`)
  },
  // Persetujuan pendaftaran (Dosen/Supervisor): list + approve/reject.
  pendaftaran(params = {}) {
    return api.get('/api/kelas-lab/pendaftaran', { params })
  },
  approvePendaftaran(pesertaId) {
    return api.patch(`/api/kelas-lab/pendaftaran/${pesertaId}/approve`)
  },
  rejectPendaftaran(pesertaId) {
    return api.patch(`/api/kelas-lab/pendaftaran/${pesertaId}/reject`)
  },
  // Keluarkan peserta dari kelas (Dosen pemilik / Supervisor) — mis. salah daftar.
  hapusPeserta(pesertaId) {
    return api.delete(`/api/kelas-lab/pendaftaran/${pesertaId}`)
  },
}
