// Store autentikasi global menggunakan Pinia
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { authService } from '@/services/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const isAuthenticated = ref(false)

  // Login: minta token ke backend, simpan token, lalu ambil data user
  async function login(email, password) {
    const response = await authService.login(email, password)
    localStorage.setItem('token', response.data.token)
    isAuthenticated.value = true
    await fetchUser()
  }

  // Login via token jadi (mis. hasil callback Google OAuth): simpan token lalu ambil data user
  async function loginWithToken(token) {
    localStorage.setItem('token', token)
    isAuthenticated.value = true
    await fetchUser()
  }

  // Ambil data user dari token yang aktif
  async function fetchUser() {
    try {
      const response = await authService.getUser()
      user.value = response.data
      isAuthenticated.value = true
    } catch {
      user.value = null
      isAuthenticated.value = false
      localStorage.removeItem('token')
    }
  }

  // Logout: batalkan token di backend dan bersihkan state lokal
  async function logout() {
    try {
      await authService.logout()
    } finally {
      user.value = null
      isAuthenticated.value = false
      localStorage.removeItem('token')
    }
  }

  return { user, isAuthenticated, login, loginWithToken, fetchUser, logout }
})
