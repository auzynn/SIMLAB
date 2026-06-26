<template>
  <div>
    <JumbotronSmall title="Bidang Riset" />

    <div class="main-container flex-h between">
      <SidemenuAdmin />

      <div class="profil-container">
        <div class="flex-h between">
          <div>
            <h1>Bidang Riset</h1>
            <div class="profil-title"></div>
          </div>
          <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreate">
            + Tambah Bidang
          </button>
        </div>

        <p class="mt-20" style="max-width: 640px">
          Daftar bidang riset yang dapat dipilih dosen di menu Edit Profil. Bisa dipilih lebih dari satu per dosen.
        </p>

        <!-- Form tambah / edit -->
        <form v-if="showForm" class="bidang-form mt-30" @submit.prevent="submitForm">
          <h3 class="mb-20">{{ editingId ? 'Edit Bidang' : 'Tambah Bidang' }}</h3>
          <div class="form-row">
            <label>Nama Bidang</label>
            <input
              type="text"
              class="form-ctrl input-border"
              v-model="form.nama"
              required
              maxlength="100"
              placeholder="mis. Digital Forensik"
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

        <p v-if="loading" class="mt-30">Memuat data...</p>
        <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>

        <table v-else class="bidang-table mt-30">
          <thead>
            <tr>
              <th>Nama Bidang</th>
              <th style="text-align: right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in items" :key="b.id">
              <td>{{ b.nama }}</td>
              <td style="text-align: right">
                <button class="btn-link" @click="openEdit(b)">Edit</button>
                <button class="btn-link btn-link-danger" @click="removeItem(b)">Hapus</button>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="2" style="text-align: center; color: #9aa0a6">Belum ada bidang riset.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Panel Admin/Supervisor — kelola master Bidang Riset (Gate manage-bidang-riset).
import { ref, onMounted } from 'vue'
import { bidangRisetService } from '@/services/bidang-riset'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuAdmin from '@/components/sidemenu-admin.vue'
import FooterComponent from '@/components/footer-component.vue'

const items = ref([])
const loading = ref(false)
const listError = ref('')

const showForm = ref(false)
const editingId = ref(null)
const form = ref({ nama: '' })
const saving = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await bidangRisetService.list()
    items.value = res.data.data
  } catch (err) {
    listError.value = extractError(err)
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  form.value = { nama: '' }
  error.value = ''
  showForm.value = true
}

function openEdit(b) {
  editingId.value = b.id
  form.value = { nama: b.nama }
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
    if (editingId.value) {
      await bidangRisetService.update(editingId.value, form.value.nama)
    } else {
      await bidangRisetService.create(form.value.nama)
    }
    showForm.value = false
    await load()
  } catch (err) {
    error.value = extractError(err)
  } finally {
    saving.value = false
  }
}

async function removeItem(b) {
  if (!confirm(`Hapus bidang "${b.nama}"? Pilihan dosen pada bidang ini akan ikut terhapus.`)) return
  try {
    await bidangRisetService.remove(b.id)
    await load()
  } catch (err) {
    alert(extractError(err))
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

onMounted(load)
</script>

<style scoped>
.bidang-form {
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

.bidang-table {
  width: 100%;
  border-collapse: collapse;
}

.bidang-table th,
.bidang-table td {
  padding: 12px 10px;
  text-align: left;
  border-bottom: 1px solid var(--bs-grey2);
}

.bidang-table th {
  border-bottom: 3px solid var(--bs-grey2);
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
