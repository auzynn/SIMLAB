// Service Laporan/Report — endpoint /api/report (3_SDD.md 5.13, SRS UC-06).
// Akses Admin/Supervisor (Gate view-report). PDF diunduh sebagai blob (butuh Bearer token).
import api from './api'

export const reportService = {
  // Rekap JSON; params { from, to } opsional (default 30 hari terakhir di backend).
  rekap(params = {}) {
    return api.get('/api/report', { params })
  },
  // Unduh PDF sebagai blob agar header Authorization tetap terkirim (bukan navigasi langsung).
  pdf(params = {}) {
    return api.get('/api/report/pdf', { params, responseType: 'blob' })
  },
}
