<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall title="Kelola User" />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container flex-h between">
      <!-- ---------- SIDE MENU ---------- -->
      <SidemenuAdmin />
      <!-- ---------- SIDE MENU END ---------- -->

      <div class="profil-container">
        <div class="flex-h between">
          <div>
            <h1>Kelola User &amp; Role</h1>
            <div class="profil-title"></div>
          </div>
          <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreate">
            + Tambah User
          </button>
        </div>

        <!-- ---------- FORM TAMBAH / EDIT ---------- -->
        <form v-if="showForm" class="user-form mt-30" @submit.prevent="submitForm">
          <h3 class="mb-20">{{ editingId ? 'Edit User' : 'Tambah User' }}</h3>

          <div class="form-row">
            <label>Nama</label>
            <input type="text" class="form-ctrl input-border" v-model="form.name" required />
          </div>

          <div class="form-row">
            <label>Email</label>
            <input type="email" class="form-ctrl input-border" v-model="form.email" required />
          </div>

          <div class="form-row">
            <label>Role</label>
            <select class="form-ctrl input-border" v-model="form.role" required>
              <option v-for="r in roleOptions" :key="r" :value="r">{{ roleLabels[r] }}</option>
            </select>
          </div>

          <div class="form-row">
            <label>Password {{ editingId ? '(kosongkan jika tidak diubah)' : '' }}</label>
            <input
              type="password"
              class="form-ctrl input-border"
              v-model="form.password"
              autocomplete="new-password"
              minlength="8"
              :required="!editingId"
            />
          </div>

          <p v-if="error" style="color: #c0392b">{{ error }}</p>

          <div class="flex-h mt-20" style="gap: 12px">
            <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
            <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="closeForm">
              Batal
            </button>
          </div>
        </form>
        <!-- ---------- FORM TAMBAH / EDIT END ---------- -->

        <!-- ---------- FILTER ---------- -->
        <div class="flex-h mt-30" style="gap: 10px; align-items: center">
          <label>Filter role:</label>
          <select class="form-ctrl input-border" style="width: auto; padding: 8px" v-model="filterRole" @change="loadUsers">
            <option value="">Semua</option>
            <option v-for="r in allRoles" :key="r" :value="r">{{ roleLabels[r] }}</option>
          </select>
        </div>

        <!-- ---------- TABEL USER ---------- -->
        <p v-if="loading" class="mt-30">Memuat data...</p>
        <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>

        <table v-else class="user-table mt-20">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Role</th>
              <th style="text-align: right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td>{{ u.name }}</td>
              <td>{{ u.email }}</td>
              <td><span class="role-badge">{{ roleLabels[u.role] || u.role }}</span></td>
              <td style="text-align: right">
                <button class="btn-link" @click="openEdit(u)">Edit</button>
                <button v-if="u.id !== currentUserId" class="btn-link btn-link-danger" @click="removeUser(u)">
                  Hapus
                </button>
              </td>
            </tr>
            <tr v-if="!users.length">
              <td colspan="4" style="text-align: center; color: #9aa0a6">Belum ada user.</td>
            </tr>
          </tbody>
        </table>
        <!-- ---------- TABEL USER END ---------- -->
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman Kelola User (Admin only) — list, tambah, edit role/data, hapus.
// Terhubung ke /api/users (3_SDD.md 5.2). Otorisasi backend lewat Gate manage-users.
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { userService } from '@/services/users'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuAdmin from '@/components/sidemenu-admin.vue'
import FooterComponent from '@/components/footer-component.vue'

const auth = useAuthStore()
const currentUserId = auth.user?.id

const roleLabels = {
  admin: 'Administrator',
  supervisor: 'Supervisor',
  dosen: 'Dosen',
  mahasiswa: 'Mahasiswa',
}
const allRoles = ['admin', 'supervisor', 'dosen', 'mahasiswa']
// Mahasiswa hanya lahir lewat Google OAuth → tak bisa dibuat manual (3_SDD.md 5.2)
const createRoles = ['admin', 'supervisor', 'dosen']

const users = ref([])
const loading = ref(false)
const listError = ref('')
const filterRole = ref('')

// State form tambah/edit (satu form dipakai untuk keduanya)
const showForm = ref(false)
const editingId = ref(null)
const form = ref({ name: '', email: '', role: 'dosen', password: '' })
const roleOptions = ref(createRoles)
const saving = ref(false)
const error = ref('')

async function loadUsers() {
  loading.value = true
  listError.value = ''
  try {
    const res = await userService.list(filterRole.value)
    users.value = res.data.data
  } catch (err) {
    listError.value = extractError(err)
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  form.value = { name: '', email: '', role: 'dosen', password: '' }
  roleOptions.value = createRoles
  error.value = ''
  showForm.value = true
}

function openEdit(u) {
  editingId.value = u.id
  form.value = { name: u.name, email: u.email, role: u.role, password: '' }
  roleOptions.value = allRoles // saat edit, semua role boleh (termasuk mahasiswa)
  error.value = ''
  showForm.value = true
}

function closeForm() {
  showForm.value = false
}

async function submitForm() {
  saving.value = true
  error.value = ''
  try {
    const payload = { ...form.value }
    if (editingId.value && !payload.password) delete payload.password // kosong = tak diubah

    if (editingId.value) {
      await userService.update(editingId.value, payload)
    } else {
      await userService.create(payload)
    }

    showForm.value = false
    await loadUsers()
  } catch (err) {
    error.value = extractError(err)
  } finally {
    saving.value = false
  }
}

async function removeUser(u) {
  if (!confirm(`Hapus user "${u.name}"? Tindakan ini tidak bisa dibatalkan.`)) return
  try {
    await userService.remove(u.id)
    await loadUsers()
  } catch (err) {
    alert(extractError(err))
  }
}

// Ambil pesan error ramah dari respons Laravel (422 ValidationException / 403)
function extractError(err) {
  const res = err.response?.data
  if (res?.errors) {
    const first = Object.values(res.errors)[0]
    if (Array.isArray(first) && first.length) return first[0]
  }
  return res?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

onMounted(loadUsers)
</script>

<style scoped>
.user-form {
  max-width: 480px;
  padding: 24px;
  background-color: var(--bs-grey1);
  border-radius: 8px;
}

.form-row {
  margin-bottom: 16px;
}

.form-row label {
  display: block;
  margin-bottom: 6px;
}

.form-row .form-ctrl {
  width: 100%;
}

.user-table {
  width: 100%;
  border-collapse: collapse;
}

.user-table th,
.user-table td {
  padding: 12px 10px;
  text-align: left;
  border-bottom: 1px solid var(--bs-grey2);
}

.user-table th {
  border-bottom: 3px solid var(--bs-grey2);
}

.role-badge {
  display: inline-block;
  padding: 2px 12px;
  font-size: 0.85em;
  background-color: var(--bs-grey2);
  border-radius: 20px;
}

.btn-link {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--bs-navy);
  font-weight: 600;
  padding: 4px 8px;
}

.btn-link:hover {
  text-decoration: underline;
}

.btn-link-danger {
  color: #c0392b;
}
</style>
