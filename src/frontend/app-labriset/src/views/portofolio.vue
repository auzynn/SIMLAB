<template>
  <div>
    <JumbotronSmall title="Portofolio Mahasiswa" />

    <div class="main-container">
      <div>
        <h1>Portofolio Mahasiswa</h1>
        <div class="profil-title"></div>
      </div>

      <p class="mt-30" style="max-width: 680px">
        Kumpulan hasil riset, proyek, dan publikasi mahasiswa Lab. Riset. Mahasiswa dapat mengelola
        portofolionya sendiri; seluruh pengguna dapat menelusuri portofolio sebagai referensi.
      </p>

      <div class="tab-bar mt-30">
        <button
          v-if="isMahasiswa"
          :class="['tab', { active: tab === 'saya' }]"
          @click="tab = 'saya'"
        >
          Portofolio Saya
        </button>
        <button :class="['tab', { active: tab === 'semua' }]" @click="tab = 'semua'">
          Jelajah Semua
        </button>
      </div>

      <!-- ============ TAB PORTOFOLIO SAYA (Mahasiswa) ============ -->
      <section v-if="isMahasiswa" v-show="tab === 'saya'">
        <div class="flex-h between mt-30">
          <h3>Portofolio Saya</h3>
          <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreate">
            + Tambah Portofolio
          </button>
        </div>

        <form v-if="showForm" class="master-form mt-20" @submit.prevent="submit">
          <h3 class="mb-20">{{ form.id ? 'Edit Portofolio' : 'Tambah Portofolio' }}</h3>
          <div class="form-row">
            <label>Judul</label>
            <input type="text" class="form-ctrl input-border" v-model="form.judul" required maxlength="255" placeholder="mis. Sistem Deteksi Intrusi Berbasis ML" />
          </div>
          <div class="form-row">
            <label>Deskripsi (opsional)</label>
            <textarea class="form-ctrl input-border" v-model="form.deskripsi" rows="4" placeholder="Ringkasan singkat proyek/riset/publikasi"></textarea>
          </div>
          <div class="form-row">
            <label>Tautan (opsional)</label>
            <input type="text" class="form-ctrl input-border" v-model="form.tautan" maxlength="255" placeholder="https://github.com/... atau tautan dokumen/demo" />
          </div>
          <div class="form-row">
            <label>Tanggal (opsional)</label>
            <input type="date" class="form-ctrl input-border" v-model="form.tanggal" />
          </div>

          <p v-if="formError" style="color: #c0392b">{{ formError }}</p>

          <div class="flex-h mt-20" style="gap: 12px">
            <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
            <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="showForm = false">Batal</button>
          </div>
        </form>

        <p v-if="loadingMine" class="mt-30">Memuat data...</p>
        <p v-else-if="mineError" class="mt-30" style="color: #c0392b">{{ mineError }}</p>
        <div v-else-if="!mine.length" class="mt-30" style="color: #9aa0a6">
          Anda belum menambahkan portofolio.
        </div>
        <div v-else class="porto-grid mt-20">
          <article v-for="p in mine" :key="p.id" class="porto-card">
            <h4>{{ p.judul }}</h4>
            <p v-if="p.tanggal" class="porto-date">{{ formatTanggalId(p.tanggal) }}</p>
            <p v-if="p.deskripsi" class="porto-desc">{{ p.deskripsi }}</p>
            <a v-if="p.tautan" :href="p.tautan" target="_blank" rel="noopener noreferrer" class="porto-tautan">
              {{ p.tautan }} ↗
            </a>
            <div class="flex-h mt-10" style="gap: 8px">
              <button class="btn-link" @click="openEdit(p)">Edit</button>
              <button class="btn-link btn-link-danger" @click="remove(p)">Hapus</button>
            </div>
          </article>
        </div>
      </section>

      <!-- ============ TAB JELAJAH SEMUA ============ -->
      <section v-show="tab === 'semua'">
        <p v-if="loadingAll" class="mt-30">Memuat data...</p>
        <p v-else-if="allError" class="mt-30" style="color: #c0392b">{{ allError }}</p>
        <div v-else-if="!all.length" class="mt-30" style="color: #9aa0a6">
          Belum ada portofolio yang dibagikan.
        </div>
        <div v-else class="porto-grid mt-30">
          <article v-for="p in all" :key="p.id" class="porto-card">
            <h4>{{ p.judul }}</h4>
            <p class="porto-owner">oleh {{ p.user?.name ?? 'Mahasiswa' }}</p>
            <p v-if="p.tanggal" class="porto-date">{{ formatTanggalId(p.tanggal) }}</p>
            <p v-if="p.deskripsi" class="porto-desc">{{ p.deskripsi }}</p>
            <a v-if="p.tautan" :href="p.tautan" target="_blank" rel="noopener noreferrer" class="porto-tautan">
              {{ p.tautan }} ↗
            </a>
          </article>
        </div>
      </section>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Portofolio Mahasiswa (PRD 3.7, SRS UC-... matriks). Tab "Saya": CRUD milik sendiri (Mahasiswa);
// tab "Jelajah Semua": baca portofolio semua mahasiswa (semua role login).
import { ref, computed, onMounted } from 'vue'
import { portofolioService } from '@/services/portofolio'
import { useAuthStore } from '@/stores/auth'
import { formatTanggalId } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const auth = useAuthStore()
const isMahasiswa = computed(() => auth.user?.role === 'mahasiswa')

const tab = ref(isMahasiswa.value ? 'saya' : 'semua')

// ---------- Portofolio Saya ----------
const mine = ref([])
const loadingMine = ref(false)
const mineError = ref('')
const showForm = ref(false)
const form = ref({ id: null, judul: '', deskripsi: '', tautan: '', tanggal: '' })
const saving = ref(false)
const formError = ref('')

async function loadMine() {
  if (!isMahasiswa.value) return
  loadingMine.value = true
  mineError.value = ''
  try {
    const res = await portofolioService.list(auth.user.id)
    mine.value = res.data.data
  } catch (err) {
    mineError.value = extractError(err)
  } finally {
    loadingMine.value = false
  }
}

function openCreate() {
  form.value = { id: null, judul: '', deskripsi: '', tautan: '', tanggal: '' }
  formError.value = ''
  showForm.value = true
}

function openEdit(p) {
  form.value = {
    id: p.id,
    judul: p.judul,
    deskripsi: p.deskripsi ?? '',
    tautan: p.tautan ?? '',
    tanggal: p.tanggal ? String(p.tanggal).slice(0, 10) : '',
  }
  formError.value = ''
  showForm.value = true
}

async function submit() {
  saving.value = true
  formError.value = ''
  const payload = {
    judul: form.value.judul,
    deskripsi: form.value.deskripsi === '' ? null : form.value.deskripsi,
    tautan: form.value.tautan === '' ? null : form.value.tautan,
    tanggal: form.value.tanggal === '' ? null : form.value.tanggal,
  }
  try {
    if (form.value.id) {
      await portofolioService.update(form.value.id, payload)
    } else {
      await portofolioService.create(payload)
    }
    showForm.value = false
    await Promise.all([loadMine(), loadAll()])
  } catch (err) {
    formError.value = extractError(err)
  } finally {
    saving.value = false
  }
}

async function remove(p) {
  if (!confirm(`Hapus portofolio "${p.judul}"?`)) return
  try {
    await portofolioService.remove(p.id)
    await Promise.all([loadMine(), loadAll()])
  } catch (err) {
    alert(extractError(err))
  }
}

// ---------- Jelajah Semua ----------
const all = ref([])
const loadingAll = ref(false)
const allError = ref('')

async function loadAll() {
  loadingAll.value = true
  allError.value = ''
  try {
    const res = await portofolioService.list()
    all.value = res.data.data
  } catch (err) {
    allError.value = extractError(err)
  } finally {
    loadingAll.value = false
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
  loadMine()
  loadAll()
})
</script>

<style scoped>
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
  padding: 10px 20px;
  cursor: pointer;
  font-weight: 600;
  color: #9aa0a6;
}

.tab.active {
  color: var(--bs-navy);
  border-bottom-color: var(--bs-navy);
}

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

.porto-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.porto-card {
  padding: 20px;
  background-color: white;
  border-radius: 8px;
  border-left: 6px solid var(--bs-navy);
  box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 0.1);
}

.porto-card h4 {
  color: var(--bs-navy);
  margin-bottom: 4px;
}

.porto-owner {
  font-weight: 600;
  color: var(--bs-yellow);
  margin-bottom: 8px;
}

.porto-date {
  font-size: 0.85em;
  color: #5f6368;
  margin-bottom: 8px;
}

.porto-desc {
  line-height: 1.5em;
  margin-bottom: 10px;
  white-space: pre-line;
}

.porto-tautan {
  display: inline-block;
  word-break: break-all;
  color: var(--bs-navy);
  font-weight: 600;
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
