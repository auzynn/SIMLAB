<template>
  <div>
    <JumbotronSmall title="Persetujuan Pendaftaran" />

    <div class="main-container">
      <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
        <div>
          <h1>Persetujuan Pendaftaran Kelas Lab</h1>
          <div class="profil-title"></div>
        </div>
        <router-link to="/kelaslab" class="btn btn-navy-border" style="width: auto; padding: 8px 20px">
          &larr; Kembali
        </router-link>
      </div>
      <p class="mt-30" style="max-width: 680px">
        Terima atau tolak mahasiswa yang mendaftar pada Kelas Lab/Praktikum
        <span v-if="lihatSemua">(seluruh kelas)</span><span v-else>(kelas yang Anda ampu)</span>.
      </p>

      <div class="tab-bar mt-30">
        <button :class="['tab', { active: filter === 'menunggu' }]" @click="filter = 'menunggu'">Menunggu ({{ countByStatus.menunggu }})</button>
        <button :class="['tab', { active: filter === 'semua' }]" @click="filter = 'semua'">Semua</button>
      </div>

      <p v-if="loading" class="mt-30">Memuat data...</p>
      <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>
      <table v-else class="data-table mt-20">
        <thead>
          <tr>
            <th>Mahasiswa</th>
            <th>Mata Kuliah</th>
            <th>Sesi</th>
            <th>Jadwal</th>
            <th>Status</th>
            <th style="text-align: right">Aksi</th>
          </tr>
          <tr class="filter-row">
            <th>
              <div class="filter-pengaju">
                <select v-model="filters.mhsField" class="filter-select">
                  <option value="nama">Nama</option>
                  <option value="npm">NPM</option>
                </select>
                <input v-model="filters.mhs" class="filter-input" :placeholder="filters.mhsField === 'npm' ? 'Cari NPM' : 'Cari nama'" />
              </div>
            </th>
            <th><input v-model="filters.mk" class="filter-input" placeholder="Cari mata kuliah" /></th>
            <th><input v-model="filters.sesi" class="filter-input" placeholder="Cari sesi" /></th>
            <th><input v-model="filters.jadwal" class="filter-input" placeholder="Cari jadwal" /></th>
            <th></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in pagedItems" :key="p.id">
            <td>
              <div>{{ p.mahasiswa?.user?.name ?? '-' }}</div>
              <div v-if="p.mahasiswa?.npm" class="sub">{{ p.mahasiswa.npm }}</div>
            </td>
            <td>{{ p.kelas_lab?.mata_kuliah?.nama_mk ?? '-' }}</td>
            <td>{{ p.kelas_lab?.nama_sesi ?? '-' }}</td>
            <td>{{ hariLabel(p.kelas_lab?.hari) }} {{ formatJam(p.kelas_lab?.jam_mulai) }}–{{ formatJam(p.kelas_lab?.jam_selesai) }}</td>
            <td><span :class="['status-badge', `status-${p.status}`]">{{ statusLabel(p.status) }}</span></td>
            <td style="text-align: right; white-space: nowrap">
              <template v-if="p.status === 'menunggu'">
                <button class="btn-link" :disabled="busyId === p.id" @click="terima(p)">Terima</button>
                <button class="btn-link btn-link-danger" :disabled="busyId === p.id" @click="tolak(p)">Tolak</button>
              </template>
              <span v-else style="color: #9aa0a6">—</span>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="6" style="text-align: center; color: #9aa0a6">Tidak ada pendaftaran.</td>
          </tr>
        </tbody>
      </table>
      <PaginationBar v-model:page="page" :total-pages="totalPages" />
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Persetujuan pendaftaran Kelas Lab — Dosen (kelas miliknya) / Admin & Supervisor (semua).
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { kelasLabService } from '@/services/kelas-lab'
import { usePagination } from '@/composables/use-pagination'
import { formatJam, hariLabel, statusLabel } from '@/utils/format'
import { useFeedback } from '@/composables/use-feedback'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const { notify, confirmDialog } = useFeedback()
const auth = useAuthStore()
// Admin & Supervisor melihat pendaftaran seluruh kelas; Dosen hanya kelas ampuannya.
const lihatSemua = computed(() => ['admin', 'supervisor'].includes(auth.user?.role))

const items = ref([])
const loading = ref(false)
const listError = ref('')
const busyId = ref(null)
const filter = ref('menunggu')
const filters = ref({ mhsField: 'nama', mhs: '', mk: '', sesi: '', jadwal: '' })

const cocok = (val, q) => !q || String(val ?? '').toLowerCase().includes(q.toLowerCase())

const countByStatus = computed(() => ({ menunggu: items.value.filter((p) => p.status === 'menunggu').length }))
const filtered = computed(() => {
  const base = filter.value === 'menunggu' ? items.value.filter((p) => p.status === 'menunggu') : items.value
  const f = filters.value
  return base.filter((p) => {
    const mhsVal = f.mhsField === 'npm' ? p.mahasiswa?.npm : p.mahasiswa?.user?.name
    return (
      cocok(mhsVal, f.mhs) &&
      cocok(p.kelas_lab?.mata_kuliah?.nama_mk, f.mk) &&
      cocok(p.kelas_lab?.nama_sesi, f.sesi) &&
      cocok(`${hariLabel(p.kelas_lab?.hari)} ${formatJam(p.kelas_lab?.jam_mulai)} ${formatJam(p.kelas_lab?.jam_selesai)}`, f.jadwal)
    )
  })
})
const { page, totalPages, pagedItems } = usePagination(filtered, 10)

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await kelasLabService.pendaftaran()
    items.value = res.data.data
  } catch (err) {
    listError.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

async function terima(p) {
  busyId.value = p.id
  try {
    await kelasLabService.approvePendaftaran(p.id)
    await load()
    notify.success('Pendaftaran disetujui.')
  } catch (err) {
    notify.error(err.response?.data?.message || 'Gagal menyetujui.')
  } finally {
    busyId.value = null
  }
}

async function tolak(p) {
  if (!(await confirmDialog('Tolak pendaftaran mahasiswa ini?'))) return
  busyId.value = p.id
  try {
    await kelasLabService.rejectPendaftaran(p.id)
    await load()
    notify.success('Pendaftaran ditolak.')
  } catch (err) {
    notify.error(err.response?.data?.message || 'Gagal menolak.')
  } finally {
    busyId.value = null
  }
}

onMounted(load)
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
.filter-input,
.filter-select {
  padding: 5px 8px;
  border: 1px solid var(--bs-grey2);
  border-radius: 6px;
  font-size: 0.85em;
  font-family: inherit;
}
.filter-input {
  width: 100%;
}
.filter-pengaju {
  display: flex;
  gap: 6px;
}
.filter-pengaju .filter-input {
  flex: 1;
  min-width: 0;
}
.sub {
  font-size: 0.85em;
  color: #5f6368;
  margin-top: 2px;
}
.status-badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 0.85em;
  font-weight: 600;
}
.status-menunggu {
  color: #856404;
  background-color: #fff3cd;
}
.status-disetujui {
  color: #1e7e34;
  background-color: #d4edda;
}
.status-ditolak {
  color: #c0392b;
  background-color: #f8d7da;
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
