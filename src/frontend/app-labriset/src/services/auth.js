// Service autentikasi menggunakan Laravel Sanctum (Bearer token)
import api from './api'

export const authService = {
  // Login manual (email + password), backend mengembalikan token + data user
  async login(email, password) {
    return api.post('/api/auth/login', { email, password })
  },

  // Ambil data user yang sedang login (token dilampirkan otomatis via interceptor)
  async getUser() {
    return api.get('/api/auth/me')
  },

  // Logout: batalkan token Sanctum aktif di backend
  async logout() {
    return api.post('/api/auth/logout')
  },

  // Atur password pertama kali (akun Google yang belum punya password manual)
  async setPassword(password, passwordConfirmation) {
    return api.post('/api/auth/set-password', {
      password,
      password_confirmation: passwordConfirmation,
    })
  },

  // Ubah password yang sudah ada (wajib password lama)
  async changePassword(currentPassword, password, passwordConfirmation) {
    return api.patch('/api/auth/change-password', {
      current_password: currentPassword,
      password,
      password_confirmation: passwordConfirmation,
    })
  },

  // Edit profil sendiri (name, no_telp; +nidn & bidang_riset_ids[] khusus dosen).
  // Email & status tidak boleh diubah (immutable di backend).
  async updateProfile(payload) {
    return api.patch('/api/auth/profile', payload)
  },

  // Unggah/ganti foto avatar akun sendiri (multipart). Backend balas URL baru.
  async updateAvatar(file) {
    const formData = new FormData()
    formData.append('avatar', file)
    return api.post('/api/auth/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
}
