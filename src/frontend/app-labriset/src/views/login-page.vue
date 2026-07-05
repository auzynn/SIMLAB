<template>
  <div id="login-page" class="flex-h">
    <div class="login-container flex-v center">
      <!-- ---------- LOGO & JUDUL ---------- -->
      <div class="login-brand">
        <img src="../assets/logo-unsil.png" class="login-logo" alt="Logo" />
        <h1 class="login-heading">Masuk SIM Lab</h1>
        <p class="login-subtitle">Sistem Informasi Laboratorium Riset</p>
      </div>

      <form class="login-form" @submit.prevent="handleLogin">
        <!-- Email -->
        <div class="field">
          <label class="field-label">Email</label>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z" /></svg>
            </span>
            <input class="input-field" placeholder="Email" type="email" v-model="email" required autocomplete="username" />
          </div>
        </div>

        <!-- Kata sandi -->
        <div class="field">
          <label class="field-label">Kata sandi</label>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 1a5 5 0 00-5 5v3H6a2 2 0 00-2 2v9a2 2 0 002 2h12a2 2 0 002-2v-9a2 2 0 00-2-2h-1V6a5 5 0 00-5-5zm3 8H9V6a3 3 0 016 0v3z" /></svg>
            </span>
            <input class="input-field has-eye" :placeholder="'Kata sandi'" :type="showPassword ? 'text' : 'password'" v-model="password" required autocomplete="current-password" />
            <button type="button" class="input-eye" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
              <svg v-if="!showPassword" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" /></svg>
              <svg v-else viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" /></svg>
            </button>
          </div>
        </div>

        <p v-if="error" class="login-error" style="color: #c0392b">{{ error }}</p>

        <button type="submit" class="btn-masuk mt-20" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Masuk' }}
        </button>
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
const showPassword = ref(false)

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
/* ---------- Brand: logo + judul ---------- */
.login-brand {
  text-align: center;
  margin-bottom: 28px;
}

.login-logo {
  height: 56px;
  width: auto;
  margin-bottom: 14px;
}

.login-heading {
  color: var(--bs-navy);
  font-size: 1.6em;
  line-height: 1.2;
}

.login-subtitle {
  margin-top: 4px;
  color: #6b7280;
  font-size: 0.9em;
}

/* ---------- Form ---------- */
.login-form {
  width: 350px;
  height: auto;
}

.field {
  margin-bottom: 18px;
}

.field-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 700;
  color: var(--bs-navy);
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  display: flex;
  align-items: center;
  color: #9aa0a6;
  pointer-events: none;
}

.input-field {
  width: 100%;
  padding: 12px 14px 12px 42px;
  background-color: var(--bs-grey1);
  border: 1px solid var(--bs-grey2);
  border-radius: 8px;
  color: #3c4043;
  font-size: 0.95em;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.input-field.has-eye {
  padding-right: 46px;
}

.input-field::placeholder {
  color: #9aa0a6;
}

.input-field:focus {
  border-color: var(--bs-navy);
  box-shadow: 0 0 0 3px rgba(24, 56, 97, 0.1);
}

.input-eye {
  position: absolute;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  border: none;
  background: none;
  color: #9aa0a6;
  cursor: pointer;
  border-radius: 6px;
}

.input-eye:hover {
  color: var(--bs-navy);
}

.login-error {
  margin-bottom: 4px;
  font-size: 0.9em;
}

/* ---------- Tombol Masuk ---------- */
.btn-masuk {
  width: 100%;
  padding: 13px 0;
  border: none;
  border-radius: 10px;
  background-image: linear-gradient(180deg, #1e4573 0%, var(--bs-navy) 100%);
  color: #fff;
  font-weight: 700;
  font-size: 1.02em;
  letter-spacing: 0.02em;
  cursor: pointer;
  box-shadow: 0 6px 16px rgba(24, 56, 97, 0.28);
  transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
}

.btn-masuk:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(24, 56, 97, 0.34);
}

.btn-masuk:active {
  transform: translateY(0);
  box-shadow: 0 4px 10px rgba(24, 56, 97, 0.28);
}

.btn-masuk:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
  filter: none;
}

.btn-google {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 8px 20px;
  font-size: 14px;
  font-weight: 500;
  color: #3c4043;
  background-color: #fff;
  border: 1px solid #dadce0;
  border-radius: 999px;
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
