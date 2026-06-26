<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall title="Profil Saya" />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <!-- ---------- KARTU IDENTITAS ---------- -->
    <div class="main-container bg-grey">
      <div class="card-bio flex-h">
        <div class="flex-v avatar-block">
          <div class="profil-avatar">
            <img v-if="user?.avatar" :src="user.avatar" alt="Foto profil" referrerpolicy="no-referrer" />
            <span v-else>{{ initials }}</span>
          </div>

          <!-- Unggah/ganti foto avatar (file gambar, maks 2MB) -->
          <input
            ref="avatarInput"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            style="display: none"
            @change="onAvatarSelected"
          />
          <button type="button" class="btn-link-avatar" :disabled="avatarLoading" @click="pickAvatar">
            {{ avatarLoading ? 'Mengunggah...' : user?.avatar ? 'Ubah Foto' : 'Tambah Foto' }}
          </button>
          <p v-if="avatarError" class="avatar-msg" style="color: #c0392b">{{ avatarError }}</p>
          <p v-else-if="avatarSuccess" class="avatar-msg" style="color: #2e7d32">{{ avatarSuccess }}</p>
        </div>

        <div class="ml-30 side-table">
          <h2>{{ user?.name || '-' }}</h2>
          <table style="width: 100%; margin-top: 20px">
            <tbody>
              <tr style="height: 26px">
                <td style="width: 25%">&nbsp;Email</td>
                <td>&nbsp;: {{ user?.email || '-' }}</td>
              </tr>

              <!-- Mahasiswa: NPM, Angkatan, Program Studi sebelum Status -->
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

              <!-- Dosen: NIDN sebelum Status (Bidang Riset di akhir, di bawah No. Telp) -->
              <tr v-if="user?.dosen" style="height: 26px">
                <td>&nbsp;NIDN</td>
                <td>&nbsp;: {{ user.dosen.nidn || '-' }}</td>
              </tr>

              <tr style="height: 26px">
                <td>&nbsp;Status</td>
                <td>&nbsp;: {{ roleLabel }}</td>
              </tr>
              <tr style="height: 26px">
                <td>&nbsp;No. Telp</td>
                <td>&nbsp;: {{ user?.no_telp || '-' }}</td>
              </tr>

              <tr v-if="user?.dosen" style="height: 26px">
                <td>&nbsp;Bidang Riset</td>
                <td>&nbsp;: {{ dosenBidangLabel }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- ---------- KARTU IDENTITAS END ---------- -->

    <!-- ---------- EDIT PROFIL ---------- -->
    <div class="main-container">
      <div class="flex-h between">
        <div>
          <h1>Edit Profil</h1>
          <div class="profil-title"></div>
        </div>
        <button
          v-if="!showProfileForm"
          type="button"
          class="btn btn-navy-border"
          style="width: auto; padding: 8px 20px"
          @click="openProfileForm"
        >
          Edit Profil
        </button>
      </div>

      <p class="mt-30" style="max-width: 600px">
        {{ immutableNotice }}
      </p>

      <form v-if="showProfileForm" class="profile-form mt-30" @submit.prevent="submitProfile">
        <div class="form-row">
          <label>Nama</label>
          <input type="text" class="form-ctrl input-border" v-model="profileForm.name" required />
        </div>

        <div class="form-row">
          <label>No. Telp</label>
          <input
            type="tel"
            class="form-ctrl input-border"
            v-model="profileForm.no_telp"
            placeholder="mis. 0812xxxxxxxx"
            maxlength="32"
          />
        </div>

        <!-- Field khusus mahasiswa -->
        <template v-if="user?.role === 'mahasiswa'">
          <div class="form-row">
            <label>Program Studi</label>
            <select class="form-ctrl input-border" v-model="profileForm.prodi">
              <option value="">- pilih -</option>
              <option value="Informatika">Informatika</option>
            </select>
          </div>
        </template>

        <!-- Field khusus dosen -->
        <template v-if="user?.role === 'dosen'">
          <div class="form-row">
            <label>NIDN</label>
            <input type="text" class="form-ctrl input-border" v-model="profileForm.nidn" maxlength="32" />
          </div>

          <div class="form-row">
            <label>Bidang Riset (boleh lebih dari satu)</label>
            <div v-if="bidangLoading" style="color: #6b7280">Memuat daftar bidang riset...</div>
            <div v-else-if="!bidangOptions.length" style="color: #6b7280">-</div>
            <div v-else class="bidang-grid">
              <label v-for="b in bidangOptions" :key="b.id" class="bidang-check">
                <input type="checkbox" :value="b.id" v-model="profileForm.bidang_riset_ids" />
                {{ b.nama }}
              </label>
            </div>
          </div>
        </template>

        <p v-if="profileError" style="color: #c0392b">{{ profileError }}</p>
        <p v-if="profileSuccess" style="color: #2e7d32">{{ profileSuccess }}</p>

        <div class="flex-h mt-20" style="gap: 12px">
          <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="profileLoading">
            {{ profileLoading ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="cancelProfileForm">
            Batal
          </button>
        </div>
      </form>
    </div>
    <!-- ---------- EDIT PROFIL END ---------- -->

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
          Masukkan password baru.
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
import { bidangRisetService } from '@/services/bidang-riset'
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

// Peringatan field immutable disesuaikan dengan baris yang tampil per role.
const immutableNotice = computed(() => {
  if (user.value?.role === 'mahasiswa') {
    return 'Email, NPM, Angkatan dan Status tidak dapat diubah.'
  }
  return 'Email dan Status tidak dapat diubah.'
})

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

// State unggah avatar
const avatarInput = ref(null)
const avatarLoading = ref(false)
const avatarError = ref('')
const avatarSuccess = ref('')

// State edit profil
const showProfileForm = ref(false)
const profileForm = ref({ name: '', no_telp: '', prodi: '', nidn: '', bidang_riset_ids: [] })
const profileLoading = ref(false)
const profileError = ref('')
const profileSuccess = ref('')

// Master Bidang Riset (untuk dosen)
const bidangOptions = ref([])
const bidangLoading = ref(false)

// Label gabungan bidang riset di kartu identitas (sumber: relasi many-to-many).
// Array kosong → "-" (truthy di JS, jadi tak boleh diserahkan ke `|| '-'`).
const dosenBidangLabel = computed(() => {
  const v = user.value?.dosen?.bidang_riset
  if (Array.isArray(v)) return v.length ? v.map((b) => b.nama).join(', ') : '-'
  return v || '-'
})

async function loadBidangOptions() {
  bidangLoading.value = true
  try {
    const res = await bidangRisetService.list()
    bidangOptions.value = res.data.data
  } catch {
    bidangOptions.value = []
  } finally {
    bidangLoading.value = false
  }
}

function openProfileForm() {
  profileError.value = ''
  profileSuccess.value = ''
  const u = user.value || {}
  const d = u.dosen || {}
  const m = u.mahasiswa || {}
  // Pre-fill dari data user terkini
  profileForm.value = {
    name: u.name || '',
    no_telp: u.no_telp || '',
    prodi: m.prodi || '',
    nidn: d.nidn || '',
    bidang_riset_ids: Array.isArray(d.bidang_riset) ? d.bidang_riset.map((b) => b.id) : [],
  }
  showProfileForm.value = true
  if (u.role === 'dosen' && !bidangOptions.value.length) loadBidangOptions()
}

function cancelProfileForm() {
  showProfileForm.value = false
  profileError.value = ''
  profileSuccess.value = ''
}

async function submitProfile() {
  profileError.value = ''
  profileSuccess.value = ''
  profileLoading.value = true
  try {
    // Hanya kirim field yang relevan dengan role agar payload bersih
    const payload = {
      name: profileForm.value.name,
      no_telp: profileForm.value.no_telp || null,
    }
    if (user.value?.role === 'dosen') {
      payload.nidn = profileForm.value.nidn || null
      payload.bidang_riset_ids = profileForm.value.bidang_riset_ids || []
    }
    if (user.value?.role === 'mahasiswa') {
      payload.prodi = profileForm.value.prodi || null
    }
    const res = await authService.updateProfile(payload)
    profileSuccess.value = res.data?.message || 'Profil berhasil diperbarui.'
    await authStore.fetchUser()
    showProfileForm.value = false
  } catch (err) {
    profileError.value = extractError(err)
  } finally {
    profileLoading.value = false
  }
}

function pickAvatar() {
  avatarError.value = ''
  avatarSuccess.value = ''
  avatarInput.value?.click()
}

async function onAvatarSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  // Validasi ringan di klien (backend tetap memvalidasi ulang)
  if (file.size > 2 * 1024 * 1024) {
    avatarError.value = 'Ukuran foto maksimal 2MB.'
    event.target.value = ''
    return
  }

  avatarLoading.value = true
  avatarError.value = ''
  avatarSuccess.value = ''
  try {
    const res = await authService.updateAvatar(file)
    avatarSuccess.value = res.data?.message || 'Foto profil berhasil diperbarui.'
    await authStore.fetchUser() // segarkan avatar di kartu & navbar
  } catch (err) {
    avatarError.value = extractError(err)
  } finally {
    avatarLoading.value = false
    event.target.value = '' // reset agar file sama bisa dipilih ulang
  }
}

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
.avatar-block {
  flex-shrink: 0;
  align-items: center;
  gap: 8px;
}

.btn-link-avatar {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--bs-navy);
  font-weight: 600;
  font-size: 0.9em;
  padding: 2px 4px;
}

.btn-link-avatar:hover:not(:disabled) {
  text-decoration: underline;
}

.btn-link-avatar:disabled {
  color: #9aa0a6;
  cursor: default;
}

.avatar-msg {
  max-width: 140px;
  text-align: center;
  font-size: 0.8em;
}

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

.profile-form {
  max-width: 520px;
  padding: 24px;
  background-color: var(--bs-grey1);
  border-radius: 8px;
}

.profile-form .form-row {
  margin-bottom: 16px;
}

.profile-form .form-row label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
}

.profile-form .form-ctrl {
  width: 100%;
}

.bidang-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 6px 16px;
  margin-top: 4px;
}

.bidang-check {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 400 !important;
  cursor: pointer;
}

.bidang-check input {
  margin: 0;
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
