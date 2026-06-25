<template>
  <div id="login-page" class="flex-h">
    <div class="login-container flex-v center" style="width: 100%">
      <div class="login-title">
        <h1>{{ error ? 'Login Gagal' : 'Memproses Login' }}</h1>
      </div>
      <p v-if="!error" align="center">Mohon tunggu, sedang menyiapkan sesi Anda...</p>
      <p v-else class="login-error" align="center" style="color: #c0392b">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
// Halaman callback Google OAuth: menerima token dari redirect backend,
// menyimpannya, memuat data user, lalu mengarahkan ke tujuan semula.
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const error = ref('')

onMounted(async () => {
  const token = route.query.token

  // Tidak ada token → kemungkinan akses langsung atau gagal di sisi backend
  if (!token) {
    redirectToLogin('oauth_failed')
    return
  }

  try {
    await authStore.loginWithToken(token)
    // Bersihkan token dari URL & arahkan ke tujuan (replace agar tak bisa di-back)
    router.replace(route.query.redirect || '/')
  } catch {
    redirectToLogin('session_failed')
  }
})

// Arahkan balik ke login dengan kode error agar ditampilkan di sana
function redirectToLogin(code) {
  error.value = 'Mengarahkan Anda kembali ke halaman login...'
  router.replace({ name: 'login', query: { error: code } })
}
</script>
