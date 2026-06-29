<template>
  <div>
    <JumbotronSmall title="Persetujuan Peminjaman" />

    <div class="main-container">
      <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
        <div>
          <h1>Persetujuan Peminjaman Ruangan</h1>
          <div class="profil-title"></div>
        </div>
        <router-link to="/jadwallab" class="btn btn-navy-border" style="width: auto; padding: 8px 20px">
          &larr; Kembali ke Jadwal Lab
        </router-link>
      </div>
      <p class="mt-30" style="max-width: 640px">
        Tinjau pengajuan peminjaman ruangan. Saat menyetujui, sistem memeriksa ulang bentrok jadwal &amp; status ruangan.
      </p>

      <div class="tab-bar mt-30">
        <button :class="['tab', { active: filter === 'menunggu' }]" @click="filter = 'menunggu'">
          Menunggu ({{ countByStatus.menunggu }})
        </button>
        <button :class="['tab', { active: filter === 'semua' }]" @click="filter = 'semua'">Semua</button>
      </div>

      <p v-if="loading" class="mt-30">Memuat data...</p>
      <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>
      <table v-else class="data-table mt-20">
        <thead>
          <tr>
            <th>Pengaju</th>
            <th>Ruangan</th>
            <th>Tanggal</th>
            <th>Waktu</th>
            <th>Keperluan</th>
            <th>Status</th>
            <th style="text-align: right">Aksi</th>
          </tr>
          <tr class="filter-row">
            <th>
              <div class="filter-pengaju">
                <select v-model="filters.pengajuField" class="filter-select">
                  <option value="nama">Nama</option>
                  <option value="npm">NPM</option>
                </select>
                <input v-model="filters.pengaju" class="filter-input" :placeholder="filters.pengajuField === 'npm' ? 'Cari NPM' : 'Cari nama'" />
              </div>
            </th>
            <th><input v-model="filters.ruangan" class="filter-input" placeholder="Cari ruangan" /></th>
            <th><input v-model="filters.tanggal" class="filter-input" placeholder="Cari tanggal" /></th>
            <th><input v-model="filters.waktu" class="filter-input" placeholder="Cari jam" /></th>
            <th><input v-model="filters.keperluan" class="filter-input" placeholder="Cari keperluan" /></th>
            <th></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in pagedItems" :key="p.id">
            <td>
              <div>{{ p.user?.name }}</div>
              <div v-if="p.user?.mahasiswa?.npm" class="pengaju-npm">{{ p.user.mahasiswa.npm }}</div>
            </td>
            <td>{{ p.ruangan?.nama_ruangan }}</td>
            <td>{{ namaHari(p.tanggal) }}, {{ formatTanggalId(p.tanggal) }}</td>
            <td>{{ formatJam(p.jam_mulai) }}–{{ formatJam(p.jam_selesai) }}</td>
            <td>{{ p.keperluan }}</td>
            <td><span :class="['status-badge', `status-${p.status}`]">{{ statusLabel(p.status) }}</span></td>
            <td style="text-align: right; white-space: nowrap">
              <template v-if="p.status === 'menunggu'">
                <button class="btn-link" :disabled="busyId === p.id" @click="setuju(p)">Setujui</button>
                <button class="btn-link btn-link-danger" :disabled="busyId === p.id" @click="tolak(p)">Tolak</button>
              </template>
              <button class="btn-link btn-link-danger" :disabled="busyId === p.id" @click="hapus(p)">Hapus</button>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="7" style="text-align: center; color: #9aa0a6">Tidak ada pengajuan.</td>
          </tr>
        </tbody>
      </table>
      <PaginationBar v-model:page="page" :total-pages="totalPages" />
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Daftar pengajuan peminjaman + approve/reject (Admin/Supervisor).
import { ref, computed, onMounted } from 'vue'
import { peminjamanRuanganService } from '@/services/peminjaman-ruangan'
import { usePagination } from '@/composables/use-pagination'
import { formatTanggalId, formatJam, statusLabel, namaHari } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const items = ref([])
const loading = ref(false)
const listError = ref('')
const filter = ref('menunggu')
const busyId = ref(null)
const filters = ref({ pengajuField: 'nama', pengaju: '', ruangan: '', tanggal: '', waktu: '', keperluan: '' })

const cocok = (val, q) => !q || String(val ?? '').toLowerCase().includes(q.toLowerCase())

const filtered = computed(() => {
  const base = filter.value === 'semua' ? items.value : items.value.filter((p) => p.status === 'menunggu')
  const f = filters.value
  return base.filter((p) => {
    const pengajuVal = f.pengajuField === 'npm' ? p.user?.mahasiswa?.npm : p.user?.name
    return (
      cocok(pengajuVal, f.pengaju) &&
      cocok(p.ruangan?.nama_ruangan, f.ruangan) &&
      cocok(`${namaHari(p.tanggal)} ${formatTanggalId(p.tanggal)}`, f.tanggal) &&
      cocok(`${formatJam(p.jam_mulai)} ${formatJam(p.jam_selesai)}`, f.waktu) &&
      cocok(p.keperluan, f.keperluan)
    )
  })
})
const { page, totalPages, pagedItems } = usePagination(filtered, 10)

const countByStatus = computed(() => ({
  menunggu: items.value.filter((p) => p.status === 'menunggu').length,
}))

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await peminjamanRuanganService.list()
    items.value = res.data.data
  } catch (err) {
    listError.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

async function setuju(p) {
  busyId.value = p.id
  try {
    await peminjamanRuanganService.approve(p.id)
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyetujui.')
  } finally {
    busyId.value = null
  }
}

async function tolak(p) {
  if (!confirm('Tolak pengajuan ini?')) return
  busyId.value = p.id
  try {
    await peminjamanRuanganService.reject(p.id)
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menolak.')
  } finally {
    busyId.value = null
  }
}

async function hapus(p) {
  if (!confirm('Hapus pengajuan ini dari daftar? Tindakan ini permanen.')) return
  busyId.value = p.id
  try {
    await peminjamanRuanganService.remove(p.id)
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus.')
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
.pengaju-npm {
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
