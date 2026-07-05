<template>
  <div>
    <JumbotronSmall title="Profil Saya" />

    <div class="main-container">
      <!-- Kartu identitas ringkas -->
      <div class="id-head">
        <div class="profil-avatar">
          <img v-if="user?.avatar" :src="user.avatar" alt="Foto profil" referrerpolicy="no-referrer" />
          <span v-else>{{ initials }}</span>
        </div>
        <div>
          <h2 class="id-name">{{ user?.name || '-' }}</h2>
          <span class="role-badge">{{ roleLabel }}</span>
        </div>
      </div>

      <!-- Tab -->
      <div class="tab-bar mt-30">
        <button :class="['tab', { active: tab === 'akun' }]" @click="tab = 'akun'">Akun</button>
        <button :class="['tab', { active: tab === 'pribadi' }]" @click="tab = 'pribadi'">Data Pribadi</button>
        <button :class="['tab', { active: tab === 'akademik' }]" @click="tab = 'akademik'">Data Akademik</button>
      </div>

      <!-- ============ TAB AKUN ============ -->
      <section v-show="tab === 'akun'" class="tab-panel akun-layout mt-30">
        <div class="akun-main">
          <h3 class="panel-title">Informasi Akun</h3>
          <div class="info-row">
            <span class="info-label">Email Universitas</span>
            <span class="info-value">{{ user?.email || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Email Pribadi</span>
            <div class="info-value" style="flex: 1">
              <div class="flex-h" style="gap: 8px; align-items: center; flex-wrap: wrap">
                <input v-model="form.email_pribadi" type="email" class="form-ctrl input-border" style="max-width: 320px; flex: 1" placeholder="email cadangan (opsional)" />
                <button class="btn btn-navy-solid" style="width: auto; padding: 7px 18px" :disabled="savingAkun" @click="saveAkun">
                  {{ savingAkun ? '...' : 'Simpan' }}
                </button>
              </div>
              <p class="hint">Email cadangan untuk kontak — <strong>tidak dapat digunakan untuk login</strong>.</p>
              <p v-if="akunMsg" :style="{ color: akunErr ? '#c0392b' : '#2e7d32' }">{{ akunMsg }}</p>
            </div>
          </div>

          <h3 class="panel-title mt-30">{{ passwordPanelTitle }}</h3>
          <p class="hint">
            <template v-if="resetMode">Anda masuk lewat akun Google UNSIL, jadi bisa mengatur password baru tanpa password lama.</template>
            <template v-else-if="hasPassword">Masukkan password lama untuk mengubah password login.</template>
            <template v-else>Atur password agar bisa login manual (email + password).</template>
          </p>
          <form class="password-form mt-10" @submit.prevent="submitPassword">
            <div v-if="hasPassword && !resetMode" class="mb-20">
              <label>Password Lama</label>
              <input type="password" class="form-ctrl input-border password-input" v-model="currentPassword" autocomplete="current-password" required />
              <p v-if="canSelfReset" class="hint mt-10">
                Lupa password lama? <a class="reset-link" @click="enterResetMode">Atur ulang tanpa password lama</a>
              </p>
            </div>
            <div class="mb-20">
              <label>Password Baru</label>
              <input type="password" class="form-ctrl input-border password-input" v-model="newPassword" autocomplete="new-password" minlength="8" required />
            </div>
            <div class="mb-20">
              <label>Konfirmasi Password Baru</label>
              <input type="password" class="form-ctrl input-border password-input" v-model="confirmPassword" autocomplete="new-password" minlength="8" required />
            </div>
            <p v-if="error" style="color: #c0392b">{{ error }}</p>
            <p v-if="success" style="color: #2e7d32">{{ success }}</p>
            <div class="flex-h" style="gap: 10px; align-items: center">
              <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="loading">
                {{ loading ? 'Menyimpan...' : passwordSubmitLabel }}
              </button>
              <a v-if="resetMode" class="reset-link" @click="exitResetMode">Batal</a>
            </div>
          </form>
        </div>

        <div class="akun-side">
          <h4>Foto Profil</h4>
          <div class="profil-avatar big">
            <img v-if="user?.avatar" :src="user.avatar" alt="Foto profil" referrerpolicy="no-referrer" />
            <span v-else>{{ initials }}</span>
          </div>
          <input ref="avatarInput" type="file" accept="image/png,image/jpeg,image/webp" style="display: none" @change="onAvatarSelected" />
          <button type="button" class="btn btn-navy-solid" style="width: auto; padding: 8px 18px" :disabled="avatarLoading" @click="pickAvatar">
            {{ avatarLoading ? 'Mengunggah...' : 'Upload Foto Baru' }}
          </button>
          <p class="hint" style="text-align: center">Maks 2MB. JPG/PNG/WEBP.</p>
          <p v-if="avatarError" class="avatar-msg" style="color: #c0392b">{{ avatarError }}</p>
          <p v-else-if="avatarSuccess" class="avatar-msg" style="color: #2e7d32">{{ avatarSuccess }}</p>
        </div>
      </section>

      <!-- ============ TAB DATA PRIBADI ============ -->
      <section v-show="tab === 'pribadi'" class="tab-panel mt-30">
        <h3 class="panel-title">Data Pribadi</h3>
        <p class="hint">{{ immutableNotice }}</p>
        <form class="profile-form mt-20" @submit.prevent="savePribadi">
          <div class="form-row">
            <label>Nama</label>
            <input type="text" class="form-ctrl input-border" v-model="form.name" required />
          </div>

          <template v-if="user?.role === 'dosen'">
            <div class="form-row">
              <label>NIDN</label>
              <input type="text" class="form-ctrl input-border" v-model="form.nidn" maxlength="32" />
            </div>
            <div class="form-row">
              <label>Jabatan Fungsional</label>
              <input type="text" class="form-ctrl input-border" v-model="form.jabatan_fungsional" maxlength="100" placeholder="mis. Lektor" />
            </div>
            <div class="form-row">
              <label>Tempat Lahir</label>
              <input type="text" class="form-ctrl input-border" v-model="form.tempat_lahir" maxlength="100" />
            </div>
            <div class="form-row">
              <label>Tanggal Lahir</label>
              <input type="date" class="form-ctrl input-border" v-model="form.tanggal_lahir" />
            </div>
            <div class="form-row">
              <label>Bidang Minat (boleh lebih dari satu)</label>
              <div v-if="bidangLoading" style="color: #6b7280">Memuat...</div>
              <MultiSelectDropdown v-else v-model="form.bidang_minat_ids" :options="bidangOptions" placeholder="Pilih bidang minat" />
            </div>
          </template>

          <template v-else-if="user?.role === 'mahasiswa'">
            <div class="form-row">
              <label>Program Studi</label>
              <select class="form-ctrl input-border" v-model="form.prodi">
                <option value="">- pilih -</option>
                <option value="Informatika">Informatika</option>
              </select>
            </div>
          </template>

          <div class="form-row">
            <label>No. Telepon</label>
            <input type="tel" class="form-ctrl input-border" v-model="form.no_telp" placeholder="mis. 0812xxxxxxxx" maxlength="32" />
          </div>

          <p v-if="pribadiMsg" :style="{ color: pribadiErr ? '#c0392b' : '#2e7d32' }">{{ pribadiMsg }}</p>
          <button type="submit" class="btn btn-navy-solid mt-10" style="width: auto; padding: 8px 24px" :disabled="savingPribadi">
            {{ savingPribadi ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
        </form>
      </section>

      <!-- ============ TAB DATA AKADEMIK ============ -->
      <section v-show="tab === 'akademik'" class="tab-panel mt-30">
        <!-- Dosen: editable -->
        <template v-if="user?.role === 'dosen'">
          <h3 class="panel-title">Data Akademik</h3>
          <p class="hint">Ditampilkan di halaman Detail Dosen (Biografi, Credential, Penelitian, Buku, Roadmap).</p>
          <form class="profile-form mt-20" @submit.prevent="saveAkademik">
            <div class="form-row">
              <label>Biografi</label>
              <textarea class="form-ctrl input-border" rows="4" v-model="form.biografi"></textarea>
            </div>
            <div class="form-row">
              <label>Credential</label>
              <textarea class="form-ctrl input-border" rows="3" v-model="form.credential" placeholder="Sertifikasi/keahlian, mis. CEH, CHFI, ..."></textarea>
            </div>
            <div class="form-row">
              <label>Penelitian & Publikasi</label>
              <textarea class="form-ctrl input-border" rows="4" v-model="form.publikasi"></textarea>
            </div>
            <div class="form-row">
              <label>Buku</label>
              <textarea class="form-ctrl input-border" rows="3" v-model="form.buku"></textarea>
            </div>
            <div class="form-row">
              <label>Roadmap Penelitian</label>
              <textarea class="form-ctrl input-border" rows="4" v-model="form.roadmap_riset"></textarea>
            </div>

            <p v-if="akademikMsg" :style="{ color: akademikErr ? '#c0392b' : '#2e7d32' }">{{ akademikMsg }}</p>
            <button type="submit" class="btn btn-navy-solid mt-10" style="width: auto; padding: 8px 24px" :disabled="savingAkademik">
              {{ savingAkademik ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </form>
        </template>

        <!-- Mahasiswa: read-only -->
        <template v-else-if="user?.role === 'mahasiswa'">
          <h3 class="panel-title">Data Akademik</h3>
          <div class="info-row"><span class="info-label">NPM</span><span class="info-value">{{ user.mahasiswa?.npm || '-' }}</span></div>
          <div class="info-row"><span class="info-label">Angkatan</span><span class="info-value">{{ user.mahasiswa?.angkatan || '-' }}</span></div>
          <div class="info-row"><span class="info-label">Program Studi</span><span class="info-value">{{ user.mahasiswa?.prodi || '-' }}</span></div>
          <div class="info-row"><span class="info-label">Status</span><span class="info-value">Aktif</span></div>
          <p class="hint">NPM & angkatan diturunkan otomatis dan tidak dapat diubah.</p>
        </template>

        <template v-else>
          <h3 class="panel-title">Data Akademik</h3>
          <p class="hint">Tidak ada data akademik untuk peran ini.</p>
        </template>
      </section>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Halaman Profil — 3 tab: Akun (email universitas/pribadi + password + foto),
// Data Pribadi (identitas), Data Akademik (Dosen: editable; Mahasiswa: read-only).
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authService } from '@/services/auth'
import { bidangMinatService } from '@/services/bidang-minat'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import MultiSelectDropdown from '@/components/multi-select-dropdown.vue'

const authStore = useAuthStore()
const user = computed(() => authStore.user)
const hasPassword = computed(() => !!user.value?.has_password)

// "Lupa password lama": reset tanpa password lama, hanya untuk akun tertaut Google UNSIL.
const resetMode = ref(false)
const canSelfReset = computed(() => hasPassword.value && !!user.value?.google_id)
const passwordPanelTitle = computed(() =>
  resetMode.value ? 'Atur Ulang Password' : hasPassword.value ? 'Ubah Password' : 'Atur Password Login',
)
const passwordSubmitLabel = computed(() =>
  resetMode.value ? 'Atur Ulang Password' : hasPassword.value ? 'Ubah Password' : 'Atur Password',
)

const tab = ref('akun')

const roleLabels = { admin: 'Administrator', supervisor: 'Supervisor', dosen: 'Dosen', mahasiswa: 'Mahasiswa' }
const roleLabel = computed(() => roleLabels[user.value?.role] || user.value?.role || '-')
const immutableNotice = computed(() =>
  user.value?.role === 'mahasiswa'
    ? 'Email, NPM, dan angkatan tidak dapat diubah.'
    : 'Email dan peran tidak dapat diubah.',
)
const initials = computed(() => {
  const parts = (user.value?.name || '').split(' ').filter(Boolean).slice(0, 2)
  return parts.map((w) => w[0]).join('').toUpperCase() || '?'
})

// --- Form terpadu (di-prefill dari user, disimpan per tab) ---
const form = ref({
  name: '', no_telp: '', email_pribadi: '', prodi: '',
  nidn: '', jabatan_fungsional: '', tempat_lahir: '', tanggal_lahir: '', bidang_minat_ids: [],
  biografi: '', credential: '', publikasi: '', buku: '', roadmap_riset: '',
})

function syncForm() {
  const u = user.value || {}
  const d = u.dosen || {}
  const m = u.mahasiswa || {}
  form.value = {
    name: u.name || '',
    no_telp: u.no_telp || '',
    email_pribadi: u.email_pribadi || '',
    prodi: m.prodi || '',
    nidn: d.nidn || '',
    jabatan_fungsional: d.jabatan_fungsional || '',
    tempat_lahir: d.tempat_lahir || '',
    tanggal_lahir: d.tanggal_lahir ? String(d.tanggal_lahir).slice(0, 10) : '',
    bidang_minat_ids: Array.isArray(d.bidang_minat) ? d.bidang_minat.map((b) => b.id) : [],
    biografi: d.biografi || '',
    credential: d.credential || '',
    publikasi: d.publikasi || '',
    buku: d.buku || '',
    roadmap_riset: d.roadmap_riset || '',
  }
}

watch(user, syncForm, { immediate: true })

// --- Bidang Minat options (dosen) ---
const bidangOptions = ref([])
const bidangLoading = ref(false)
async function loadBidangOptions() {
  bidangLoading.value = true
  try {
    const res = await bidangMinatService.list()
    bidangOptions.value = res.data.data
  } catch {
    bidangOptions.value = []
  } finally {
    bidangLoading.value = false
  }
}

// --- Simpan Akun (email pribadi) ---
const savingAkun = ref(false)
const akunMsg = ref('')
const akunErr = ref(false)
async function saveAkun() {
  savingAkun.value = true
  akunMsg.value = ''
  try {
    const res = await authService.updateProfile({ email_pribadi: form.value.email_pribadi || null })
    akunErr.value = false
    akunMsg.value = res.data?.message || 'Email pribadi disimpan.'
    await authStore.fetchUser()
  } catch (err) {
    akunErr.value = true
    akunMsg.value = extractError(err)
  } finally {
    savingAkun.value = false
  }
}

// --- Simpan Data Pribadi ---
const savingPribadi = ref(false)
const pribadiMsg = ref('')
const pribadiErr = ref(false)
async function savePribadi() {
  savingPribadi.value = true
  pribadiMsg.value = ''
  try {
    const payload = { name: form.value.name, no_telp: form.value.no_telp || null }
    if (user.value?.role === 'dosen') {
      payload.nidn = form.value.nidn || null
      payload.jabatan_fungsional = form.value.jabatan_fungsional || null
      payload.tempat_lahir = form.value.tempat_lahir || null
      payload.tanggal_lahir = form.value.tanggal_lahir || null
      payload.bidang_minat_ids = form.value.bidang_minat_ids || []
    }
    if (user.value?.role === 'mahasiswa') payload.prodi = form.value.prodi || null
    const res = await authService.updateProfile(payload)
    pribadiErr.value = false
    pribadiMsg.value = res.data?.message || 'Profil berhasil diperbarui.'
    await authStore.fetchUser()
  } catch (err) {
    pribadiErr.value = true
    pribadiMsg.value = extractError(err)
  } finally {
    savingPribadi.value = false
  }
}

// --- Simpan Data Akademik (dosen) ---
const savingAkademik = ref(false)
const akademikMsg = ref('')
const akademikErr = ref(false)
async function saveAkademik() {
  savingAkademik.value = true
  akademikMsg.value = ''
  try {
    const res = await authService.updateProfile({
      biografi: form.value.biografi || null,
      credential: form.value.credential || null,
      publikasi: form.value.publikasi || null,
      buku: form.value.buku || null,
      roadmap_riset: form.value.roadmap_riset || null,
    })
    akademikErr.value = false
    akademikMsg.value = res.data?.message || 'Data akademik disimpan.'
    await authStore.fetchUser()
  } catch (err) {
    akademikErr.value = true
    akademikMsg.value = extractError(err)
  } finally {
    savingAkademik.value = false
  }
}

// --- Avatar ---
const avatarInput = ref(null)
const avatarLoading = ref(false)
const avatarError = ref('')
const avatarSuccess = ref('')
function pickAvatar() {
  avatarError.value = ''
  avatarSuccess.value = ''
  avatarInput.value?.click()
}
async function onAvatarSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return
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
    await authStore.fetchUser()
  } catch (err) {
    avatarError.value = extractError(err)
  } finally {
    avatarLoading.value = false
    event.target.value = ''
  }
}

// --- Password ---
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')
function enterResetMode() {
  resetMode.value = true
  error.value = ''
  success.value = ''
  currentPassword.value = ''
}

function exitResetMode() {
  resetMode.value = false
  error.value = ''
  success.value = ''
}

async function submitPassword() {
  error.value = ''
  success.value = ''
  if (newPassword.value !== confirmPassword.value) {
    error.value = 'Konfirmasi password tidak cocok.'
    return
  }
  loading.value = true
  try {
    let res
    if (resetMode.value) {
      res = await authService.resetPassword(newPassword.value, confirmPassword.value)
    } else if (hasPassword.value) {
      res = await authService.changePassword(currentPassword.value, newPassword.value, confirmPassword.value)
    } else {
      res = await authService.setPassword(newPassword.value, confirmPassword.value)
    }
    success.value = res.data?.message || 'Password berhasil disimpan.'
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
    resetMode.value = false
    await authStore.fetchUser()
  } catch (err) {
    error.value = extractError(err)
  } finally {
    loading.value = false
  }
}

function extractError(err) {
  const res = err.response?.data
  if (res?.errors) {
    const first = Object.values(res.errors)[0]
    if (Array.isArray(first) && first.length) return first[0]
  }
  return res?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

onMounted(() => {
  if (user.value?.role === 'dosen') loadBidangOptions()
})
</script>

<style scoped>
.id-head {
  display: flex;
  align-items: center;
  gap: 18px;
}
.id-name {
  color: var(--bs-navy);
}
.role-badge {
  display: inline-block;
  margin-top: 4px;
  padding: 2px 12px;
  border-radius: 20px;
  background-color: #eef1f7;
  color: var(--bs-navy);
  font-size: 0.78em;
  font-weight: 700;
}
.profil-avatar {
  flex-shrink: 0;
  width: 88px;
  height: 88px;
  border-radius: 50%;
  overflow: hidden;
  background-color: var(--bs-navy);
  display: flex;
  align-items: center;
  justify-content: center;
}
.profil-avatar.big {
  width: 140px;
  height: 140px;
  margin: 8px 0;
}
.profil-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.profil-avatar span {
  color: white;
  font-size: 2em;
  font-weight: 600;
}

.tab-bar {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid var(--bs-grey2);
}
.tab {
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  padding: 10px 22px;
  cursor: pointer;
  font-weight: 600;
  color: #9aa0a6;
}
.tab.active {
  color: var(--bs-navy);
  border-bottom-color: var(--bs-navy);
}

.panel-title {
  color: var(--bs-navy);
}
.hint {
  margin-top: 6px;
  font-size: 0.85em;
  color: #5f6368;
}

.reset-link {
  color: var(--bs-navy);
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}

.akun-layout {
  display: grid;
  grid-template-columns: 1.7fr 1fr;
  gap: 30px;
  align-items: start;
}
@media (max-width: 860px) {
  .akun-layout {
    grid-template-columns: 1fr;
  }
}
.akun-side {
  background-color: var(--bs-grey1);
  border: 1px solid var(--bs-grey2);
  border-radius: 10px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.akun-side h4 {
  color: var(--bs-navy);
}

.info-row {
  display: flex;
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid var(--bs-grey2);
  align-items: flex-start;
}
.info-label {
  flex-shrink: 0;
  width: 170px;
  color: #5f6368;
  font-size: 0.95em;
}
.info-value {
  color: var(--bs-black);
  font-weight: 600;
}

.profile-form {
  max-width: 560px;
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
.avatar-msg {
  max-width: 180px;
  text-align: center;
  font-size: 0.8em;
}
</style>
