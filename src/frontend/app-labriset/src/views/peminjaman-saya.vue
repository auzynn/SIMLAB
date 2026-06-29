<template>
  <div>
    <JumbotronSmall title="Peminjaman Saya" />

    <div class="main-container">
      <div>
        <h1>Peminjaman Saya</h1>
        <div class="profil-title"></div>
      </div>
      <p class="mt-30" style="max-width: 640px">Status pengajuan peminjaman ruangan yang Anda ajukan.</p>

      <p v-if="loading" class="mt-30">Memuat data...</p>
      <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>
      <table v-else class="data-table mt-30">
        <thead>
          <tr>
            <th>Ruangan</th>
            <th>Tanggal</th>
            <th>Waktu</th>
            <th>Keperluan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in pagedItems" :key="p.id">
            <td>{{ p.ruangan?.nama_ruangan }}</td>
            <td>{{ namaHari(p.tanggal) }}, {{ formatTanggalId(p.tanggal) }}</td>
            <td>{{ formatJam(p.jam_mulai) }}–{{ formatJam(p.jam_selesai) }}</td>
            <td>{{ p.keperluan }}</td>
            <td><span :class="['status-badge', `status-${p.status}`]">{{ statusLabel(p.status) }}</span></td>
          </tr>
          <tr v-if="!items.length">
            <td colspan="5" style="text-align: center; color: #9aa0a6">Belum ada pengajuan.</td>
          </tr>
        </tbody>
      </table>
      <PaginationBar v-model:page="page" :total-pages="totalPages" />
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Daftar pengajuan peminjaman ruangan milik user yang login (backend memfilter milik sendiri).
import { ref, onMounted } from 'vue'
import { peminjamanRuanganService } from '@/services/peminjaman-ruangan'
import { usePagination } from '@/composables/use-pagination'
import { formatTanggalId, formatJam, statusLabel, namaHari } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const items = ref([])
const { page, totalPages, pagedItems } = usePagination(items, 10)
const loading = ref(false)
const listError = ref('')

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

onMounted(load)
</script>

<style scoped>
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
</style>
