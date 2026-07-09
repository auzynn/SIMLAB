// Service Rekap Tugas Kelas Lab — endpoint /api/rekap-tugas (3_SDD.md 5.15, SRS UC-06).
// Akses Admin/Supervisor/Dosen (Gate view-rekap-tugas; Dosen di-scope ke kelasnya di backend).
// PDF & Excel diunduh sebagai blob agar header Authorization tetap terkirim.
import api from './api'

export const rekapTugasService = {
  // Rekap JSON lengkap: ringkasan semua kelas + detail matriks per kelas. Selalu data terkini.
  rekap() {
    return api.get('/api/rekap-tugas')
  },
  // Unduh PDF (landscape) sebagai blob.
  pdf() {
    return api.get('/api/rekap-tugas/pdf', { responseType: 'blob' })
  },
  // Unduh Excel .xlsx berformat sebagai blob.
  excel() {
    return api.get('/api/rekap-tugas/excel', { responseType: 'blob' })
  },
}
