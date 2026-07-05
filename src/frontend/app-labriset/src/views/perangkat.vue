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
        </thead>
        <tbody>
          <tr v-for="p in pagedItems" :key="p.id">
            <td>{{ p.nama_perangkat }}</td>
            <td>{{ p.nomor_seri }}</td>
            <td>{{ p.kategori ?? '—' }}</td>
            <td><span :class="['status-badge', `status-${p.status}`]">{{ statusPerangkatLabel(p.status) }}</span></td>
            <td v-if="auth.user?.role === 'mahasiswa'" style="text-align: right; white-space: nowrap">
              <button
                v-if="p.status === 'tersedia'"
                class="btn-link"
                @click="ajukan(p)"
              >
                Ajukan Pinjam
              </button>
              <span v-else style="color: #9aa0a6">—</span>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td :colspan="auth.user?.role === 'mahasiswa' ? 5 : 4" style="text-align: center; color: #9aa0a6">
              Belum ada perangkat.
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
// Katalog perangkat lab (semua role login). Mahasiswa: tombol Ajukan Pinjam → form peminjaman.
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { perangkatService } from '@/services/perangkat'
import { useAuthStore } from '@/stores/auth'
import { usePagination } from '@/composables/use-pagination'
import { statusPerangkatLabel } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const auth = useAuthStore()
const router = useRouter()
const items = ref([])
const loading = ref(false)
const listError = ref('')
const filter = ref('semua')

const filtered = computed(() =>
  filter.value === 'tersedia' ? items.value.filter((p) => p.status === 'tersedia') : items.value,
)
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

// Arahkan ke "Peminjaman Saya" tab Perangkat dengan perangkat terpilih (pre-fill via query).
function ajukan(p) {
  router.push({ path: '/peminjaman-saya', query: { tab: 'perangkat', perangkat: p.id } })
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
