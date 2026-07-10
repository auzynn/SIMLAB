<template>
  <div>
    <JumbotronSmall title="Perangkat Lab" />

    <div class="main-container">
      <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
        <div>
          <h1>Perangkat Lab</h1>
          <div class="profil-title"></div>
        </div>
        <router-link
          v-if="auth.user?.role === 'mahasiswa'"
          to="/peminjaman-saya?tab=perangkat"
          class="btn btn-navy-solid"
          style="width: auto; padding: 8px 20px"
        >
          Peminjaman Saya
        </router-link>
      </div>
      <p class="mt-30" style="max-width: 640px">
        Inventaris perangkat laboratorium (PC, Router, Switch, IoT Kit, dll) beserta status
        ketersediaannya. Mahasiswa dapat mengajukan peminjaman perangkat yang tersedia.
      </p>

      <div class="tab-bar mt-30">
        <button :class="['tab', { active: filter === 'semua' }]" @click="filter = 'semua'">
          Semua ({{ items.length }})
        </button>
        <button :class="['tab', { active: filter === 'tersedia' }]" @click="filter = 'tersedia'">
          Tersedia ({{ countTersedia }})
        </button>
      </div>

      <p v-if="loading" class="mt-30">Memuat data...</p>
      <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>
      <table v-else class="data-table mt-20">
        <thead>
          <tr>
            <th>Nama Perangkat</th>
            <th>Nomor Seri</th>
            <th>Kategori</th>
            <th>Status</th>
            <th v-if="auth.user?.role === 'mahasiswa'" style="text-align: right">Aksi</th>
          </tr>
          <tr class="filter-row">
            <th><input v-model="filters.nama" class="filter-input" placeholder="Cari nama" /></th>
            <th><input v-model="filters.nomor" class="filter-input" placeholder="Cari nomor seri" /></th>
            <th><input v-model="filters.kategori" class="filter-input" placeholder="Cari kategori" /></th>
            <th></th>
            <th v-if="auth.user?.role === 'mahasiswa'"></th>
          </tr>
        </thead>
        <tbody>
          <template v-for="p in pagedItems" :key="p.id">
            <tr>
              <td>{{ p.nama_perangkat }}</td>
              <td>{{ p.nomor_seri }}</td>
              <td>{{ p.kategori ?? '—' }}</td>
              <td><span :class="['status-badge', `status-${p.status}`]">{{ statusPerangkatLabel(p.status) }}</span></td>
              <td v-if="auth.user?.role === 'mahasiswa'" style="text-align: right; white-space: nowrap">
                <button
                  v-if="p.status === 'tersedia'"
                  class="btn-link"
                  @click="bukaAjukan(p)"
                >
                  Ajukan Pinjam
                </button>
                <span v-else style="color: #9aa0a6">—</span>
              </td>
            </tr>
            <!-- Baris form pengajuan (inline) — muncul saat "Ajukan Pinjam" ditekan -->
            <tr v-if="ajukanFor === p.id">
              <td colspan="5" style="background-color: var(--bs-grey1)">
                <form class="flex-h" style="gap: 12px; align-items: flex-end; flex-wrap: wrap" @submit.prevent="submitAjukan(p)">
                  <div>
                    <label style="display: block; margin-bottom: 6px">Tanggal Pinjam</label>
                    <input type="date" class="form-ctrl input-border" v-model="ajukanForm.tanggal_pinjam" :min="today" required />
                  </div>
                  <div>
                    <label style="display: block; margin-bottom: 6px">Rencana Tanggal Kembali</label>
                    <input type="date" class="form-ctrl input-border" v-model="ajukanForm.tanggal_kembali_rencana" :min="ajukanForm.tanggal_pinjam || today" required />
                  </div>
                  <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" :disabled="savingAjukan">
                    {{ savingAjukan ? 'Mengirim...' : 'Kirim Pengajuan' }}
                  </button>
                  <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 20px" @click="ajukanFor = null">Batal</button>
                  <span v-if="ajukanError" style="color: #c0392b">{{ ajukanError }}</span>
                </form>
              </td>
            </tr>
          </template>
          <tr v-if="!filtered.length">
            <td :colspan="auth.user?.role === 'mahasiswa' ? 5 : 4" style="text-align: center; color: #9aa0a6">
              {{ items.length ? 'Tidak ada perangkat yang cocok dengan filter.' : 'Belum ada perangkat.' }}
            </td>
          </tr>
        </tbody>
      </table>
      <PaginationBar v-model:page="page" :total-pages="totalPages" />
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Katalog perangkat lab (semua role login). Mahasiswa: "Ajukan Pinjam" membuka form inline & mengirim langsung.
import { ref, computed, onMounted } from 'vue'
import { perangkatService } from '@/services/perangkat'
import { peminjamanPerangkatService } from '@/services/peminjaman-perangkat'
import { useAuthStore } from '@/stores/auth'
import { usePagination } from '@/composables/use-pagination'
import { statusPerangkatLabel } from '@/utils/format'
import { useFeedback } from '@/composables/use-feedback'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const { notify } = useFeedback()
const auth = useAuthStore()
const today = new Date().toISOString().slice(0, 10)
const items = ref([])
const loading = ref(false)
const listError = ref('')
const filter = ref('semua')

// Pengajuan inline per perangkat
const ajukanFor = ref(null)
const ajukanForm = ref({ tanggal_pinjam: today, tanggal_kembali_rencana: '' })
const savingAjukan = ref(false)
const ajukanError = ref('')

// Filter pencarian per kolom (nama, nomor seri, kategori) di atas filter status (tab).
const filters = ref({ nama: '', nomor: '', kategori: '' })
const cocok = (val, q) => !q || String(val ?? '').toLowerCase().includes(q.toLowerCase())

const filtered = computed(() => {
  const byStatus = filter.value === 'tersedia' ? items.value.filter((p) => p.status === 'tersedia') : items.value
  const f = filters.value
  return byStatus.filter(
    (p) => cocok(p.nama_perangkat, f.nama) && cocok(p.nomor_seri, f.nomor) && cocok(p.kategori, f.kategori),
  )
})
const { page, totalPages, pagedItems } = usePagination(filtered, 10)
const countTersedia = computed(() => items.value.filter((p) => p.status === 'tersedia').length)

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await perangkatService.list()
    items.value = res.data.data
  } catch (err) {
    listError.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

// Buka form pengajuan inline untuk perangkat terpilih.
function bukaAjukan(p) {
  ajukanFor.value = p.id
  ajukanForm.value = { tanggal_pinjam: today, tanggal_kembali_rencana: '' }
  ajukanError.value = ''
}

// Kirim pengajuan peminjaman langsung dari katalog.
async function submitAjukan(p) {
  savingAjukan.value = true
  ajukanError.value = ''
  try {
    await peminjamanPerangkatService.create({
      perangkat_id: p.id,
      tanggal_pinjam: ajukanForm.value.tanggal_pinjam,
      tanggal_kembali_rencana: ajukanForm.value.tanggal_kembali_rencana,
    })
    ajukanFor.value = null
    notify.success('Pengajuan peminjaman terkirim, menunggu persetujuan. Pantau di "Peminjaman Saya".')
    await load()
  } catch (err) {
    ajukanError.value = extractError(err)
  } finally {
    savingAjukan.value = false
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
.filter-input {
  width: 100%;
  padding: 5px 8px;
  border: 1px solid var(--bs-grey2);
  border-radius: 6px;
  font-size: 0.85em;
  font-family: inherit;
}
.status-badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 0.85em;
  font-weight: 600;
}
.status-tersedia {
  color: #1e7e34;
  background-color: #d4edda;
}
.status-dipinjam {
  color: #856404;
  background-color: #fff3cd;
}
.status-perbaikan {
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
</style>
