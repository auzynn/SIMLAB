<template>
  <div>
    <JumbotronSmall title="Katalog Sertifikasi" />

    <div class="main-container flex-h between">
      <SidemenuAdmin />

      <div class="profil-container">
        <div>
          <h1>Katalog Sertifikasi</h1>
          <div class="profil-title"></div>
        </div>

        <p class="mt-30" style="max-width: 640px">
          Kelola katalog sertifikasi/pelatihan eksternal yang ditampilkan ke seluruh pengguna sebagai
          referensi. Modul ini bersifat informasional — SIM Lab. Riset tidak menangani pendaftaran.
        </p>

        <div class="flex-h between mt-30">
          <h3>Daftar Sertifikasi</h3>
          <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreate">
            + Tambah Sertifikasi
          </button>
        </div>

        <form v-if="showForm" class="master-form mt-20" @submit.prevent="submit">
          <h3 class="mb-20">{{ form.id ? 'Edit Sertifikasi' : 'Tambah Sertifikasi' }}</h3>
          <div class="form-row">
            <label>Nama Sertifikasi</label>
            <input type="text" class="form-ctrl input-border" v-model="form.nama_sertifikasi" required maxlength="255" placeholder="mis. Mikrotik Certified Network Associate" />
          </div>
          <div class="form-row">
            <label>Penyelenggara</label>
            <input type="text" class="form-ctrl input-border" v-model="form.penyelenggara" required maxlength="255" placeholder="mis. Mikrotik" />
          </div>
          <div class="form-row">
            <label>Jadwal (opsional)</label>
            <input type="text" class="form-ctrl input-border" v-model="form.jadwal" maxlength="255" placeholder="mis. Batch berkala / rentang tanggal" />
          </div>
          <div class="form-row">
            <label>Persyaratan (opsional)</label>
            <textarea class="form-ctrl input-border" v-model="form.persyaratan" rows="3" placeholder="mis. Pemahaman dasar jaringan TCP/IP"></textarea>
          </div>
          <div class="form-row">
            <label>Tautan Pendaftaran (opsional)</label>
            <input type="text" class="form-ctrl input-border" v-model="form.tautan_pendaftaran" maxlength="255" placeholder="https://..." />
          </div>

          <p v-if="formError" style="color: #c0392b">{{ formError }}</p>

          <div class="flex-h mt-20" style="gap: 12px">
            <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
            <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="showForm = false">Batal</button>
          </div>
        </form>

        <p v-if="loading" class="mt-30">Memuat data...</p>
        <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>
        <table v-else class="master-table mt-20">
          <thead>
            <tr>
              <th>Nama Sertifikasi</th>
              <th>Penyelenggara</th>
              <th>Jadwal</th>
              <th style="text-align: right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in pagedItems" :key="s.id">
              <td>{{ s.nama_sertifikasi }}</td>
              <td>{{ s.penyelenggara }}</td>
              <td>{{ s.jadwal ?? '—' }}</td>
              <td style="text-align: right">
                <button class="btn-link" @click="openEdit(s)">Edit</button>
                <button class="btn-link btn-link-danger" @click="remove(s)">Hapus</button>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="4" style="text-align: center; color: #9aa0a6">Belum ada sertifikasi.</td>
            </tr>
          </tbody>
        </table>
        <PaginationBar v-model:page="page" :total-pages="totalPages" />
      </div>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Panel Admin/Supervisor — kelola Katalog Sertifikasi (SertifikasiPolicy; SRS UC-05).
// Dosen mengelola referensi miliknya dari halaman publik Sertifikasi (/sertifikasi).
import { ref, onMounted } from 'vue'
import { sertifikasiService } from '@/services/sertifikasi'
import { usePagination } from '@/composables/use-pagination'
import { useFeedback } from '@/composables/use-feedback'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuAdmin from '@/components/sidemenu-admin.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const items = ref([])
const { notify, confirmDialog } = useFeedback()
const { page, totalPages, pagedItems } = usePagination(items, 10)
const loading = ref(false)
const listError = ref('')
const showForm = ref(false)
const form = ref({ id: null, nama_sertifikasi: '', penyelenggara: '', jadwal: '', persyaratan: '', tautan_pendaftaran: '' })
const saving = ref(false)
const formError = ref('')

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await sertifikasiService.list()
    items.value = res.data.data
  } catch (err) {
    listError.value = extractError(err)
  } finally {
    loading.value = false
  }
}

function openCreate() {
  form.value = { id: null, nama_sertifikasi: '', penyelenggara: '', jadwal: '', persyaratan: '', tautan_pendaftaran: '' }
  formError.value = ''
  showForm.value = true
}

function openEdit(s) {
  form.value = {
    id: s.id,
    nama_sertifikasi: s.nama_sertifikasi,
    penyelenggara: s.penyelenggara,
    jadwal: s.jadwal ?? '',
    persyaratan: s.persyaratan ?? '',
    tautan_pendaftaran: s.tautan_pendaftaran ?? '',
  }
  formError.value = ''
  showForm.value = true
}

async function submit() {
  saving.value = true
  formError.value = ''
  const payload = {
    nama_sertifikasi: form.value.nama_sertifikasi,
    penyelenggara: form.value.penyelenggara,
    jadwal: form.value.jadwal === '' ? null : form.value.jadwal,
    persyaratan: form.value.persyaratan === '' ? null : form.value.persyaratan,
    tautan_pendaftaran: form.value.tautan_pendaftaran === '' ? null : form.value.tautan_pendaftaran,
  }
  try {
    if (form.value.id) {
      await sertifikasiService.update(form.value.id, payload)
    } else {
      await sertifikasiService.create(payload)
    }
    showForm.value = false
    await load()
  } catch (err) {
    formError.value = extractError(err)
  } finally {
    saving.value = false
  }
}

async function remove(s) {
  if (!(await confirmDialog(`Hapus sertifikasi "${s.nama_sertifikasi}"?`))) return
  try {
    await sertifikasiService.remove(s.id)
    await load()
    notify.success(`Sertifikasi "${s.nama_sertifikasi}" dihapus`)
  } catch (err) {
    notify.error(extractError(err))
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
.master-form {
  max-width: 520px;
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

.master-table {
  width: 100%;
  border-collapse: collapse;
}

.master-table th,
.master-table td {
  padding: 12px 10px;
  text-align: left;
  border-bottom: 1px solid var(--bs-grey2);
}

.master-table th {
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
