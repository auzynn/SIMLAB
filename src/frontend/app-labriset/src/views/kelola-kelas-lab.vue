<template>
  <div>
    <JumbotronSmall title="Kelola Kelas Lab" />

    <div class="main-container">
      <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
        <div>
          <h1>Kelola Kelas Lab</h1>
          <div class="profil-title"></div>
        </div>
        <div class="flex-h" style="gap: 12px">
          <router-link to="/kelaslab" class="btn btn-navy-border" style="width: auto; padding: 8px 20px">
            &larr; Kembali
          </router-link>
          <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreate">+ Buka Kelas</button>
        </div>
      </div>

      <!-- Form buka / edit kelas -->
      <form v-if="showForm" class="kelas-form mt-30" @submit.prevent="submit">
        <h3 class="mb-20">{{ form.id ? 'Edit Kelas' : 'Buka Kelas Baru' }}</h3>
        <div class="grid-2">
          <div class="form-row">
            <label>Mata Kuliah</label>
            <select v-model="form.mata_kuliah_id" class="form-ctrl input-border" required>
              <option value="" disabled>-- Pilih mata kuliah --</option>
              <option v-for="m in mataKuliah" :key="m.id" :value="m.id">{{ m.nama_mk }}</option>
            </select>
          </div>
          <div v-if="kelolaSemua" class="form-row">
            <label>Dosen Pengampu</label>
            <select v-model="form.dosen_id" class="form-ctrl input-border" required>
              <option value="" disabled>-- Pilih dosen --</option>
              <option v-for="d in dosenList" :key="d.id" :value="d.id">{{ d.user?.name }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>Nama Sesi</label>
            <select v-model="form.nama_sesi" class="form-ctrl input-border" required>
              <option value="" disabled>-- Pilih sesi --</option>
              <option v-for="s in SESI" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>Ruangan</label>
            <select v-model="form.ruangan_id" class="form-ctrl input-border" required>
              <option value="" disabled>-- Pilih ruangan --</option>
              <option v-for="r in ruangan" :key="r.id" :value="r.id">{{ r.nama_ruangan }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>Hari</label>
            <select v-model="form.hari" class="form-ctrl input-border" required>
              <option value="" disabled>-- Pilih hari --</option>
              <option v-for="h in HARI" :key="h" :value="h">{{ hariLabel(h) }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>Kuota (1–40)</label>
            <input v-model="form.kuota" type="number" min="1" max="40" class="form-ctrl input-border" required />
          </div>
          <div class="form-row">
            <label>Jam Mulai</label>
            <input v-model="form.jam_mulai" type="time" min="07:00" max="17:00" class="form-ctrl input-border" required />
          </div>
          <div class="form-row">
            <label>Jam Selesai</label>
            <input v-model="form.jam_selesai" type="time" min="07:00" max="17:00" class="form-ctrl input-border" required />
          </div>
          <div class="form-row">
            <label>Mulai Semester</label>
            <input v-model="form.tanggal_mulai_semester" type="date" class="form-ctrl input-border" required />
          </div>
          <div class="form-row">
            <label>Selesai Semester</label>
            <input v-model="form.tanggal_selesai_semester" type="date" class="form-ctrl input-border" required />
          </div>
        </div>

        <div class="form-row mt-20">
          <label>Tautan Pengumpulan Dokumen</label>
          <input
            v-model="form.tautan_pengumpulan"
            type="url"
            class="form-ctrl input-border"
            style="width: 100%"
            placeholder="https://forms.gle/... atau folder Google Drive"
            required
          />
          <p class="field-hint">Tautan tempat mahasiswa mengunggah dokumen laporan (PDF/DOCX). Wajib diisi.</p>
        </div>

        <p class="jam-note">Jam operasional lab: 07.00–17.00 WIB.</p>
        <p v-if="formError" style="color: #c0392b">{{ formError }}</p>

        <div class="flex-h mt-20" style="gap: 12px">
          <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="showForm = false">Batal</button>
        </div>
      </form>

      <p v-if="loading" class="mt-30">Memuat data...</p>
      <table v-else class="data-table mt-30">
        <thead>
          <tr>
            <th>Mata Kuliah / Kelas</th>
            <th>Jadwal</th>
            <th>Ruangan</th>
            <th>Peserta</th>
            <th style="text-align: right">Aksi</th>
          </tr>
          <tr class="filter-row">
            <th><input v-model="filters.mk" class="filter-input" placeholder="Cari mata kuliah" /></th>
            <th><input v-model="filters.jadwal" class="filter-input" placeholder="Cari jadwal" /></th>
            <th><input v-model="filters.ruangan" class="filter-input" placeholder="Cari ruangan" /></th>
            <th></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <!-- Dikelompokkan per mata kuliah (baris judul abu), diurut Nama MK A→Z lalu Hari/Jam. -->
          <template v-for="g in grupTampil" :key="g.namaMk">
            <tr class="grup-head">
              <td colspan="5">{{ g.namaMk }} <span class="grup-count">· {{ g.items.length }} kelas</span></td>
            </tr>
            <tr v-for="k in g.items" :key="k.id">
              <td class="sesi-cell">{{ k.nama_sesi }}</td>
              <td>{{ hariLabel(k.hari) }} {{ formatJam(k.jam_mulai) }}–{{ formatJam(k.jam_selesai) }}</td>
              <td>{{ k.ruangan?.nama_ruangan }}</td>
              <td>{{ k.peserta_count ?? (k.kuota - k.sisa_kuota) }}/{{ k.kuota }}</td>
              <td style="text-align: right">
                <router-link class="btn-link" :to="`/kelaslab/${k.id}/peserta`">Peserta</router-link>
                <button class="btn-link" @click="openEdit(k)">Edit</button>
                <button class="btn-link btn-link-danger" @click="hapus(k)">Hapus</button>
              </td>
            </tr>
          </template>
          <tr v-if="!grupTampil.length">
            <td colspan="5" style="text-align: center; color: #9aa0a6">Belum ada kelas.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Buka & kelola Kelas Lab (Dosen: milik sendiri; Admin/Supervisor: semua kelas, menunjuk dosen).
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { kelasLabService } from '@/services/kelas-lab'
import { ruanganService } from '@/services/ruangan'
import { mataKuliahService } from '@/services/mata-kuliah'
import { dosenService } from '@/services/dosen'
import { formatJam, hariLabel } from '@/utils/format'
import { useFeedback } from '@/composables/use-feedback'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const { notify, confirmDialog } = useFeedback()
const HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']
const SESI = ['Kelas A', 'Kelas B', 'Kelas C', 'Kelas D', 'Kelas E', 'Kelas F']

const auth = useAuthStore()
// Admin & Supervisor mengelola SEMUA kelas dan menunjuk dosen pengampu;
// Dosen hanya kelas miliknya (dosen_id dipaksa dirinya di backend).
const kelolaSemua = computed(() => ['admin', 'supervisor'].includes(auth.user?.role))

const items = ref([])
const ruangan = ref([])
const mataKuliah = ref([])
const dosenList = ref([])
const loading = ref(false)

const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const blankForm = () => ({
  id: null,
  mata_kuliah_id: '',
  dosen_id: '',
  ruangan_id: '',
  nama_sesi: '',
  hari: '',
  jam_mulai: '',
  jam_selesai: '',
  tanggal_mulai_semester: '',
  tanggal_selesai_semester: '',
  kuota: 30,
  tautan_pengumpulan: '',
})
const form = ref(blankForm())

// Dosen hanya melihat kelas miliknya; Admin/Supervisor melihat semua.
const milikSaya = computed(() => {
  if (kelolaSemua.value) return items.value
  const dosenId = auth.user?.dosen?.id
  return items.value.filter((k) => k.dosen_id === dosenId)
})

// Filter kolom tabel kelola.
const filters = ref({ mk: '', jadwal: '', ruangan: '' })
const cocok = (val, q) => !q || String(val ?? '').toLowerCase().includes(q.toLowerCase())
const kelasTampil = computed(() => {
  const f = filters.value
  return milikSaya.value.filter(
    (k) =>
      cocok(k.mata_kuliah?.nama_mk, f.mk) &&
      cocok(`${hariLabel(k.hari)} ${formatJam(k.jam_mulai)} ${formatJam(k.jam_selesai)}`, f.jadwal) &&
      cocok(k.ruangan?.nama_ruangan, f.ruangan),
  )
})

// Urutan hari untuk pengurutan dalam grup (Senin → Sabtu).
const URUT_HARI = { senin: 1, selasa: 2, rabu: 3, kamis: 4, jumat: 5, sabtu: 6, minggu: 7 }

// Kelompokkan per mata kuliah, urut Nama MK A→Z, di dalamnya urut Hari lalu Jam mulai.
const grupTampil = computed(() => {
  const peta = new Map()
  for (const k of kelasTampil.value) {
    const nama = k.mata_kuliah?.nama_mk ?? '(Tanpa mata kuliah)'
    if (!peta.has(nama)) peta.set(nama, [])
    peta.get(nama).push(k)
  }
  const grup = [...peta.entries()].map(([namaMk, items]) => {
    items.sort((a, b) => {
      const h = (URUT_HARI[a.hari] ?? 99) - (URUT_HARI[b.hari] ?? 99)
      if (h !== 0) return h
      return String(a.jam_mulai ?? '').localeCompare(String(b.jam_mulai ?? ''))
    })
    return { namaMk, items }
  })
  grup.sort((a, b) => a.namaMk.localeCompare(b.namaMk, 'id'))
  return grup
})

async function load() {
  loading.value = true
  try {
    const res = await kelasLabService.list()
    items.value = res.data.data
  } finally {
    loading.value = false
  }
}

async function loadRefs() {
  const [r, m] = await Promise.all([ruanganService.list(), mataKuliahService.list()])
  ruangan.value = r.data.data
  mataKuliah.value = m.data.data
  if (kelolaSemua.value) {
    const d = await dosenService.getAll()
    dosenList.value = d.data.data
  }
}

function openCreate() {
  form.value = blankForm()
  formError.value = ''
  showForm.value = true
}

function openEdit(k) {
  form.value = {
    id: k.id,
    mata_kuliah_id: k.mata_kuliah_id,
    dosen_id: k.dosen_id,
    ruangan_id: k.ruangan_id,
    nama_sesi: k.nama_sesi,
    hari: k.hari,
    jam_mulai: formatJam(k.jam_mulai),
    jam_selesai: formatJam(k.jam_selesai),
    tanggal_mulai_semester: k.tanggal_mulai_semester,
    tanggal_selesai_semester: k.tanggal_selesai_semester,
    kuota: k.kuota,
    tautan_pengumpulan: k.tautan_pengumpulan ?? '',
  }
  formError.value = ''
  showForm.value = true
}

async function submit() {
  saving.value = true
  formError.value = ''
  const payload = { ...form.value, kuota: Number(form.value.kuota) }
  // Dosen tidak mengirim dosen_id (backend memaksa miliknya sendiri);
  // Admin/Supervisor wajib mengirim dosen_id pilihan.
  if (!kelolaSemua.value) delete payload.dosen_id
  delete payload.id
  try {
    if (form.value.id) {
      await kelasLabService.update(form.value.id, payload)
    } else {
      await kelasLabService.create(payload)
    }
    showForm.value = false
    await load()
  } catch (err) {
    formError.value = extractError(err)
  } finally {
    saving.value = false
  }
}

async function hapus(k) {
  if (!(await confirmDialog(`Hapus kelas "${k.mata_kuliah?.nama_mk} — ${k.nama_sesi}"?`))) return
  try {
    await kelasLabService.remove(k.id)
    await load()
    notify.success('Kelas lab dihapus.')
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

onMounted(() => {
  load()
  loadRefs()
})
</script>

<style scoped>
.kelas-form {
  padding: 24px;
  background-color: var(--bs-grey1);
  border-radius: 8px;
}
.grid-2 {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}
.form-row label {
  display: block;
  margin-bottom: 6px;
}
.form-row .form-ctrl {
  width: 100%;
}
.jam-note {
  margin-top: 28px;
  margin-bottom: 4px;
  font-size: 0.85em;
  color: #5f6368;
}
.field-hint {
  margin-top: 6px;
  font-size: 0.82em;
  color: #9aa0a6;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th,
.data-table td {
  padding: 12px 10px;
  text-align: left;
  border-bottom: 1px solid var(--bs-grey2);
}
.data-table th {
  border-bottom: 3px solid var(--bs-grey2);
}
.filter-row th {
  padding: 6px 10px;
  border-bottom: 1px solid var(--bs-grey2);
  font-weight: normal;
}
/* Baris judul kelompok mata kuliah (Bentuk B). */
.grup-head td {
  background-color: var(--bs-grey1);
  border-left: 4px solid var(--bs-navy);
  border-bottom: 2px solid var(--bs-grey2);
  font-weight: 700;
  color: var(--bs-navy);
  padding: 10px 12px;
}
.grup-count {
  font-weight: 500;
  color: #5f6368;
  font-size: 0.85em;
}
/* Sel sesi menjorok agar terlihat "anak" dari judul mata kuliah di atasnya. */
.sesi-cell {
  padding-left: 24px;
  font-weight: 600;
  color: var(--bs-navy);
}
.filter-input {
  width: 100%;
  padding: 5px 8px;
  border: 1px solid var(--bs-grey2);
  border-radius: 6px;
  font-size: 0.85em;
  font-family: inherit;
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
