<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall title="Profil Saya" />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <!-- ---------- KARTU IDENTITAS ---------- -->
    <div class="main-container bg-grey">
      <div class="card-bio flex-h">
        <div class="profil-avatar">
          <img v-if="user?.avatar" :src="user.avatar" alt="Foto profil" referrerpolicy="no-referrer" />
          <span v-else>{{ initials }}</span>
        </div>

        <div class="ml-30 side-table">
          <h2>{{ user?.name || '-' }}</h2>
          <table style="width: 100%; margin-top: 20px">
            <tbody>
              <tr style="height: 26px">
                <td style="width: 25%">&nbsp;Email</td>
                <td>&nbsp;: {{ user?.email || '-' }}</td>
              </tr>
              <tr style="height: 26px">
                <td>&nbsp;Status</td>
                <td>&nbsp;: {{ roleLabel }}</td>
              </tr>

              <!-- Data khusus mahasiswa -->
              <template v-if="user?.mahasiswa">
                <tr style="height: 26px">
                  <td>&nbsp;NPM</td>
                  <td>&nbsp;: {{ user.mahasiswa.npm || '-' }}</td>
                </tr>
                <tr style="height: 26px">
                  <td>&nbsp;Angkatan</td>
                  <td>&nbsp;: {{ user.mahasiswa.angkatan || '-' }}</td>
                </tr>
                <tr style="height: 26px">
                  <td>&nbsp;Program Studi</td>
                  <td>&nbsp;: {{ user.mahasiswa.prodi || '-' }}</td>
                </tr>
              </template>

              <!-- Data khusus dosen -->
              <template v-if="user?.dosen">
                <tr style="height: 26px">
                  <td>&nbsp;NIDN</td>
                  <td>&nbsp;: {{ user.dosen.nidn || '-' }}</td>
                </tr>
                <tr style="height: 26px">
                  <td>&nbsp;Bidang Riset</td>
                  <td>&nbsp;: {{ user.dosen.bidang_riset || '-' }}</td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- ---------- KARTU IDENTITAS END ---------- -->

    <!-- ---------- FORM PASSWORD ---------- -->
    <div class="main-container">
      <div>
        <h1>{{ hasPassword ? 'Ubah Password' : 'Atur Password Login' }}</h1>
        <div class="profil-title"></div>
      </div>

      <p class="mt-30" style="max-width: 600px">
        <template v-if="hasPassword">
          Ubah password login manual Anda. Masukkan password lama untuk konfirmasi.
        </template>
        <template v-else>
          Atur password agar bisa login manual (email + password) sebagai alternatif Login UNSIL.
          Tidak perlu password lama karena akun ini belum pernah mengaturnya.
        </template>
      </p>

      <form class="password-form mt-30" @submit.prevent="submitPassword">
        <div v-if="hasPassword" class="mb-20">
          <label>Password Lama</label>
          <input
            type="password"
            class="form-ctrl input-border password-input"
            v-model="currentPassword"
            autocomplete="current-password"
            required
          />
        </div>

        <div class="mb-20">
          <label>Password Baru</label>
          <input
            type="password"
            class="form-ctrl input-border password-input"
            v-model="newPassword"
            autocomplete="new-password"
            minlength="8"
            required
          />
        </div>

        <div class="mb-20">
          <label>Konfirmasi Password Baru</label>
          <input
            type="password"
            class="form-ctrl input-border password-input"
            v-model="confirmPassword"
            autocomplete="new-password"
            minlength="8"
            required
          />
        </div>

        <p v-if="error" style="color: #c0392b">{{ error }}</p>
        <p v-if="success" style="color: #2e7d32">{{ success }}</p>

        <button
          type="submit"
          class="btn btn-navy-solid mt-30"
          style="width: auto; padding: 8px 28px"
          :disabled="loading"
        >
          {{ loading ? 'Menyimpan...' : hasPassword ? 'Ubah Password' : 'Atur Password' }}
        </button>
      </form>
    </div>
    <!-- ---------- FORM PASSWORD END ---------- -->

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman akun pribadi: menampilkan data diri + form Atur/Ubah Password.
// Form tampil kondisional sesuai apakah user sudah punya password (3_SDD.md 2.1, UC-01b).
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authService } from '@/services/auth'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const authStore = useAuthStore()
const user = computed(() => authStore.user)

// Apakah login manual sudah aktif → menentukan "Atur" vs "Ubah" password
const hasPassword = computed(() => !!user.value?.has_password)

// Label peran yang ramah dibaca
const roleLabels = {
  admin: 'Administrator',
  supervisor: 'Supervisor',
  dosen: 'Dosen',
  mahasiswa: 'Mahasiswa',
}
const roleLabel = computed(() => roleLabels[user.value?.role] || user.value?.role || '-')

// Inisial nama untuk avatar fallback (saat tidak ada foto Google)
const initials = computed(() => {
  const name = user.value?.name || ''
  const parts = name.split(' ').filter(Boolean).slice(0, 2)
  return parts.map((w) => w[0]).join('').toUpperCase() || '?'
})

// State form password
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')

async function submitPassword() {
  error.value = ''
  success.value = ''

  // Validasi ringan di sisi klien sebelum kirim
  if (newPassword.value !== confirmPassword.value) {
    error.value = 'Konfirmasi password tidak cocok.'
    return
  }

  loading.value = true
  try {
    const res = hasPassword.value
      ? await authService.changePassword(currentPassword.value, newPassword.value, confirmPassword.value)
      : await authService.setPassword(newPassword.value, confirmPassword.value)

    success.value = res.data?.message || 'Password berhasil disimpan.'

    // Bersihkan form & segarkan data user (flag has_password ikut diperbarui)
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
    await authStore.fetchUser()
  } catch (err) {
    error.value = extractError(err)
  } finally {
    loading.value = false
  }
}

// Ambil pesan error yang ramah dari respons Laravel (422 ValidationException)
function extractError(err) {
  const res = err.response?.data
  if (res?.errors) {
    const first = Object.values(res.errors)[0]
    if (Array.isArray(first) && first.length) return first[0]
  }
  return res?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}
</script>

<style scoped>
.profil-avatar {
  flex-shrink: 0;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  background-color: var(--bs-navy);
  display: flex;
  align-items: center;
  justify-content: center;
}

.profil-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profil-avatar span {
  color: white;
  font-size: 2.5em;
  font-weight: 600;
}

.password-form {
  max-width: 420px;
}

.password-form label {
  display: block;
  margin-bottom: 6px;
}

.password-input {
  width: 100%;
}

td {
  vertical-align: top;
}
</style>
