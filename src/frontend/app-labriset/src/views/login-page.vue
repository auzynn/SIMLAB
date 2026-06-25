<template>
  <div id="login-page" class="flex-h">
    <div class="login-container flex-v center">
      <div class="login-title">
        <h1>Login</h1>
      </div>
      <form class="login-form" @submit.prevent="handleLogin">
        <div class="mb-30">
          <input class="form-ctrl input-solid login-input" placeholder="Email" type="email" v-model="email" required />
        </div>
        <div class="mb-30">
          <input class="form-ctrl input-solid login-input" placeholder="Password" type="password" v-model="password" required />
        </div>
        <p v-if="error" class="login-error" style="color: #c0392b">{{ error }}</p>
        <div class="mt-50">
          <button type="submit" class="btn btn-navy-solid btn-login" :disabled="loading">
            {{ loading ? 'Memproses...' : 'Masuk' }}
          </button>
        </div>
      </form>
      <div class="login-unsil">
        <div>
          <p align="center">Atau login dengan</p>
        </div>
        <div class="flex-h center" style="margin-top: 15px">
          <button type="button" class="btn-google" @click="loginUnsil">
            <img src="../assets/google.png" class="btn-google__icon" alt="" />
            <span>Login dengan UNSIL Mail</span>
          </button>
        </div>
      </div>
    </div>
    <div class="jumbotron-login"></div>
  </div>
</template>

<script setup>
// Halaman login pengguna
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

// Pesan ramah untuk kode error yang dikirim backend/callback Google OAuth
const oauthErrors = {
  invalid_domain: 'Gunakan email institusi UNSIL (@unsil.ac.id atau @student.unsil.ac.id).',
  oauth_failed: 'Login Google gagal. Silakan coba lagi.',
  session_failed: 'Gagal menyiapkan sesi. Silakan coba lagi.'
}

// Tampilkan error dari query string saat datang dari redirect OAuth
onMounted(() => {
  const code = route.query.error
  if (code) {
    error.value = oauthErrors[code] || 'Terjadi kesalahan saat login.'
  }
})

// Login manual: minta token ke backend lalu arahkan ke beranda
async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    router.push(route.query.redirect || '/')
  } catch {
    error.value = 'Email atau password salah.'
  } finally {
    loading.value = false
  }
}

// Login Google UNSIL: arahkan ke endpoint redirect OAuth backend
function loginUnsil() {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
  window.location.href = `${baseUrl}/api/auth/google/redirect`
}
</script>

<style scoped>
.btn-google {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 10px 16px;
  font-size: 14px;
  font-weight: 500;
  color: #3c4043;
  background-color: #fff;
  border: 1px solid #dadce0;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.btn-google:hover {
  background-color: #f7f8f8;
  border-color: #d2d3d4;
  box-shadow: 0 1px 3px rgba(60, 64, 67, 0.15);
}

.btn-google:active {
  background-color: #eef0f1;
}

.btn-google__icon {
  width: 18px;
  height: 18px;
}
</style>
