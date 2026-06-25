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
          <img src="../assets/google.png" style="width: 20px; height: 20px; margin-right: 5px" />
          <button @click="loginUnsil">UNSIL Mail</button>
        </div>
      </div>
    </div>
    <div class="jumbotron-login"></div>
  </div>
</template>

<script setup>
// Halaman login pengguna
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

// Login manual: minta token ke backend lalu arahkan ke beranda
async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    router.push('/')
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
