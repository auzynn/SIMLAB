// Service konten info lab — endpoint /api/info-lab/{tipe} (3_SDD.md 5.12)
import api from './api'

export const infoLabService = {
  // Ambil konten satu tipe (publik)
  get(tipe) {
    return api.get(`/api/info-lab/${tipe}`)
  },

  // Update konten satu tipe (Admin)
  update(tipe, payload) {
    return api.patch(`/api/info-lab/${tipe}`, payload)
  },
}
