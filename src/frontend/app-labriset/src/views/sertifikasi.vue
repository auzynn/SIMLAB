<template>
  <div>
    <JumbotronSmall title="Katalog Sertifikasi" />

    <div class="main-container">
      <div>
        <h1>Katalog Sertifikasi</h1>
        <div class="profil-title"></div>
      </div>

      <div class="flex-h between mt-30" style="gap: 12px; flex-wrap: wrap; align-items: flex-start">
        <p style="max-width: 680px; margin: 0">
          Informasi sertifikasi &amp; pelatihan eksternal (Mikrotik, Cisco, Oracle, EC-Council, dll)
          yang relevan sebagai referensi mahasiswa. SIM Lab. Riset hanya menampilkan informasi —
          pendaftaran dilakukan langsung ke pihak penyelenggara melalui tautan yang tersedia.
        </p>
        <button v-if="bisaTambah" class="btn btn-navy-solid" style="width: auto; padding: 8px 20px; flex-shrink: 0" @click="openCreate">
          + Tambah Sertifikasi
        </button>
      </div>

      <p v-if="bisaTambah" class="field-hint">
        Dosen dapat menambah referensi sertifikasi sendiri dan hanya dapat mengubah/menghapus miliknya.
      </p>

      <!-- Form tambah/edit — Admin/Supervisor (semua entri) atau Dosen (miliknya) -->
      <form v-if="showForm" class="cert-form mt-20" @submit.prevent="submit">
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

      <template v-else>
        <div v-if="!items.length" class="mt-30" style="color: #9aa0a6">
          Belum ada sertifikasi yang tercatat.
        </div>

        <div v-else class="cert-grid mt-30">
          <article v-for="s in items" :key="s.id" class="cert-card">
            <h3>{{ s.nama_sertifikasi }}</h3>
            <p class="penyelenggara">{{ s.penyelenggara }}</p>

            <dl class="cert-meta">
              <template v-if="s.jadwal">
                <dt>Jadwal</dt>
                <dd>{{ s.jadwal }}</dd>
              </template>
              <template v-if="s.persyaratan">
                <dt>Persyaratan</dt>
                <dd>{{ s.persyaratan }}</dd>
              </template>
            </dl>

            <div class="cert-actions">
              <a
                v-if="s.tautan_pendaftaran"
                :href="s.tautan_pendaftaran"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-navy-solid cert-link"
              >
                Info Pendaftaran ↗
              </a>
              <div v-if="bisaKelola(s)" class="cert-manage">
                <button class="btn-link" @click="openEdit(s)">Edit</button>
                <button class="btn-link btn-link-danger" @click="remove(s)">Hapus</button>
              </div>
            </div>
          </article>
        </div>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Katalog sertifikasi eksternal (semua role login) — informasional (SRS UC-05).
// Kelola: Admin/Supervisor (semua entri) atau Dosen (referensi miliknya, created_by) — SertifikasiPolicy.
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { sertifikasiService } from '@/services/sertifikasi'
import { useFeedback } from '@/composables/use-feedback'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const { notify, confirmDialog } = useFeedback()
const auth = useAuthStore()
const items = ref([])
const loading = ref(false)
const listError = ref('')

// Create: Admin/Supervisor/Dosen. Update/Delete per entri lewat bisaKelola().
const bisaTambah = computed(() => ['admin', 'supervisor', 'dosen'].includes(auth.user?.role))
function bisaKelola(s) {
  const role = auth.user?.role
  if (role === 'admin' || role === 'supervisor') return true
  return role === 'dosen' && s.created_by === auth.user?.id
}

const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const blankForm = () => ({ id: null, nama_sertifikasi: '', penyelenggara: '', jadwal: '', persyaratan: '', tautan_pendaftaran: '' })
const form = ref(blankForm())

function openCreate() {
  form.value = blankForm()
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
    notify.success(`Sertifikasi "${s.nama_sertifikasi}" dihapus.`)
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

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await sertifikasiService.list()
    items.value = res.data.data
  } catch (err) {
    listError.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.cert-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.cert-card {
  display: flex;
  flex-direction: column;
  padding: 24px;
  background-color: white;
  border-radius: 8px;
  border-left: 6px solid var(--bs-navy);
  box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 0.1);
}

.cert-card h3 {
  color: var(--bs-navy);
  margin-bottom: 4px;
}

.penyelenggara {
  font-weight: 600;
  color: var(--bs-yellow);
  margin-bottom: 12px;
}

.cert-meta {
  margin: 0 0 16px;
  flex: 1;
}

.cert-meta dt {
  font-weight: 700;
  font-size: 0.85em;
  color: #5f6368;
  margin-top: 10px;
}

.cert-meta dd {
  margin: 2px 0 0;
  line-height: 1.4em;
}

.cert-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
}

.cert-link {
  width: auto;
  align-self: flex-start;
  padding: 8px 20px;
}

.cert-manage {
  display: flex;
  gap: 4px;
  margin-left: auto;
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

.cert-form {
  max-width: 520px;
  padding: 24px;
  background-color: var(--bs-grey1);
  border-radius: 8px;
}

.cert-form .form-row {
  margin-bottom: 16px;
}

.cert-form .form-row label {
  display: block;
  margin-bottom: 6px;
}

.cert-form .form-ctrl {
  width: 100%;
}

.field-hint {
  margin-top: 8px;
  font-size: 0.85em;
  color: #9aa0a6;
}
</style>
