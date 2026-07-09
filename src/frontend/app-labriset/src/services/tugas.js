// Service Pengumpulan Tugas — endpoint /api/tugas (menggantikan modul Presensi).
// Kirim: Mahasiswa (kelas yang diikuti); rekap sesuai role; hapus: pemilik / Admin / Supervisor.
import api from './api'

export const tugasService = {
  list() {
    return api.get('/api/tugas')
  },
  // payload: { kelas_lab_id, judul, tautan }
  create(payload) {
    return api.post('/api/tugas', payload)
  },
  remove(id) {
    return api.delete(`/api/tugas/${id}`)
  },
}
