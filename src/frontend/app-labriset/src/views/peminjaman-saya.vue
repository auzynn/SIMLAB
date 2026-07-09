<template>
  <div>
    <JumbotronSmall title="Peminjaman Saya" />

    <div class="main-container">
      <div>
        <h1>Peminjaman Saya</h1>
        <div class="profil-title"></div>
      </div>
      <p class="mt-30" style="max-width: 640px">
        Status pengajuan peminjaman Anda — ruangan/lab dan perangkat. Pengajuan yang masih
        <strong>menunggu</strong> dapat dibatalkan.
      </p>

      <!-- Tab: Ruangan / Perangkat -->
      <div class="tab-bar mt-30">
        <button :class="['tab', { active: tab === 'ruangan' }]" @click="tab = 'ruangan'">
          Ruangan ({{ itemsRuangan.length }})
        </button>
        <button :class="['tab', { active: tab === 'perangkat' }]" @click="tab = 'perangkat'">
          Perangkat ({{ itemsPerangkat.length }})
        </button>
      </div>

      <!-- ============ TAB RUANGAN ============ -->
      <template v-if="tab === 'ruangan'">
        <!-- Pengajuan ruangan dilakukan dari halaman Jadwal Lab (Informasi Jadwal + formulir) -->
        <div class="flex-h between mt-20" style="align-items: center; gap: 12px; flex-wrap: wrap">
          <h3>Riwayat Pengajuan Ruangan</h3>
          <router-link to="/jadwallab" class="btn btn-navy-solid" style="width: auto; padding: 8px 20px">
            + Ajukan Peminjaman Ruangan
          </router-link>
        </div>

        <p v-if="loadingRuangan" class="mt-20">Memuat data...</p>
        <p v-else-if="errorRuangan" class="mt-20" style="color: #c0392b">{{ errorRuangan }}</p>
        <table v-else class="data-table mt-20">
          <thead>
            <tr>
              <th>Ruangan</th>
              <th>Tanggal</th>
              <th>Waktu</th>
              <th>Keperluan</th>
              <th>Status</th>
              <th style="text-align: right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in pagedRuangan" :key="p.id">
              <td>{{ p.ruangan?.nama_ruangan }}</td>
              <td>{{ namaHari(p.tanggal) }}, {{ formatTanggalId(p.tanggal) }}</td>
              <td>{{ formatJam(p.jam_mulai) }}–{{ formatJam(p.jam_selesai) }}</td>
              <td>{{ p.keperluan }}</td>
              <td><span :class="['status-badge', `status-${p.status}`]">{{ statusLabel(p.status) }}</span></td>
              <td style="text-align: right; white-space: nowrap">
                <button
                  v-if="p.status === 'menunggu'"
                  class="btn-link btn-link-danger"
                  :disabled="busyId === 'r-' + p.id"
                  @click="batalkanRuangan(p)"
                >
                  Batalkan
                </button>
                <span v-else style="color: #9aa0a6">—</span>
              </td>
            </tr>
            <tr v-if="!itemsRuangan.length">
              <td colspan="6" style="text-align: center; color: #9aa0a6">Belum ada pengajuan ruangan.</td>
            </tr>
          </tbody>
        </table>
        <PaginationBar v-model:page="pageR" :total-pages="totalPagesR" />
      </template>

      <!-- ============ TAB PERANGKAT ============ -->
      <template v-else>
        <!-- Pengajuan dilakukan dari katalog Perangkat Lab: pilih perangkat → "Ajukan Pinjam" -->
        <div class="flex-h between mt-20" style="align-items: center; gap: 12px; flex-wrap: wrap">
          <h3>Riwayat Pengajuan Perangkat</h3>
          <router-link to="/perangkat" class="btn btn-navy-solid" style="width: auto; padding: 8px 20px">
            + Ajukan Peminjaman Perangkat
          </router-link>
        </div>
        <p v-if="loadingPerangkat" class="mt-20">Memuat data...</p>
        <p v-else-if="errorPerangkat" class="mt-20" style="color: #c0392b">{{ errorPerangkat }}</p>
        <table v-else class="data-table mt-20">
          <thead>
            <tr>
              <th>Perangkat</th>
              <th>Tanggal Pinjam</th>
              <th>Rencana Kembali</th>
              <th>Status</th>
              <th style="text-align: right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="p in pagedPerangkat" :key="p.id">
              <tr>
                <td>{{ p.perangkat?.nama_perangkat }}</td>
                <td>{{ formatTanggalId(p.tanggal_pinjam) }}</td>
                <td>{{ formatTanggalId(p.tanggal_kembali_rencana) }}</td>
                <td><span :class="['status-badge', `status-${p.status}`]">{{ statusLabel(p.status) }}</span></td>
                <td style="text-align: right; white-space: nowrap">
                  <button
                    v-if="p.status === 'menunggu'"
                    class="btn-link btn-link-danger"
                    :disabled="busyId === 'p-' + p.id"
                    @click="batalkanPerangkat(p)"
                  >
                    Batalkan
                  </button>
                  <button
                    v-else-if="p.status === 'disetujui' && !rencanaLewat(p) && !perpanjanganMenunggu(p)"
                    class="btn-link"
                    @click="bukaPerpanjangan(p)"
                  >
                    Ajukan Perpanjangan
                  </button>
                  <span v-else-if="perpanjanganMenunggu(p)" style="color: #856404">Perpanjangan menunggu</span>
                  <span v-else style="color: #9aa0a6">—</span>
                </td>
              </tr>
              <!-- Baris form perpanjangan (inline) -->
              <tr v-if="perpanjanganFor === p.id">
                <td colspan="5" style="background-color: var(--bs-grey1)">
                  <form class="flex-h" style="gap: 12px; align-items: flex-end; flex-wrap: wrap" @submit.prevent="submitPerpanjangan(p)">
                    <div>
                      <label style="display: block; margin-bottom: 6px">Tanggal Kembali Baru</label>
                      <input type="date" class="form-ctrl input-border" v-model="perpanjanganTanggal" :min="minPerpanjangan(p)" required />
                    </div>
                    <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" :disabled="savingPerpanjangan">
                      {{ savingPerpanjangan ? 'Mengirim...' : 'Kirim' }}
                    </button>
                    <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 20px" @click="perpanjanganFor = null">Batal</button>
                    <span v-if="perpanjanganError" style="color: #c0392b">{{ perpanjanganError }}</span>
                  </form>
                </td>
              </tr>
            </template>
            <tr v-if="!itemsPerangkat.length">
              <td colspan="5" style="text-align: center; color: #9aa0a6">Belum ada pengajuan perangkat.</td>
            </tr>
          </tbody>
        </table>
        <PaginationBar v-model:page="pageP" :total-pages="totalPagesP" />
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// "Peminjaman Saya" — gabungan pengajuan ruangan (UC-02) & perangkat (UC-03) milik Mahasiswa,
// dipisah tab. Backend memfilter agar hanya milik sendiri. Pengajuan 'menunggu' bisa dibatalkan.
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { peminjamanRuanganService } from '@/services/peminjaman-ruangan'
import { peminjamanPerangkatService } from '@/services/peminjaman-perangkat'
import { usePagination } from '@/composables/use-pagination'
import { formatTanggalId, formatJam, statusLabel, namaHari } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const route = useRoute()
const today = new Date().toISOString().slice(0, 10)

const tab = ref('ruangan')
const busyId = ref(null)

// ----- Ruangan -----
const itemsRuangan = ref([])
const { page: pageR, totalPages: totalPagesR, pagedItems: pagedRuangan } = usePagination(itemsRuangan, 10)
const loadingRuangan = ref(false)
const errorRuangan = ref('')

// ----- Perangkat -----
const itemsPerangkat = ref([])
const { page: pageP, totalPages: totalPagesP, pagedItems: pagedPerangkat } = usePagination(itemsPerangkat, 10)
const loadingPerangkat = ref(false)
const errorPerangkat = ref('')

const perpanjanganFor = ref(null)
const perpanjanganTanggal = ref('')
const savingPerpanjangan = ref(false)
const perpanjanganError = ref('')

function rencanaLewat(p) {
  return p.tanggal_kembali_rencana < today
}
function perpanjanganMenunggu(p) {
  return (p.perpanjangan ?? []).some((x) => x.status === 'menunggu')
}
function minPerpanjangan(p) {
  const d = new Date(p.tanggal_kembali_rencana)
  d.setDate(d.getDate() + 1)
  return d.toISOString().slice(0, 10)
}

async function loadRuangan() {
  loadingRuangan.value = true
  errorRuangan.value = ''
  try {
    const res = await peminjamanRuanganService.list()
    itemsRuangan.value = res.data.data
  } catch (err) {
    errorRuangan.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loadingRuangan.value = false
  }
}

async function loadPerangkatPeminjaman() {
  loadingPerangkat.value = true
  errorPerangkat.value = ''
  try {
    const res = await peminjamanPerangkatService.list()
    itemsPerangkat.value = res.data.data
  } catch (err) {
    errorPerangkat.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loadingPerangkat.value = false
  }
}

async function batalkanRuangan(p) {
  if (!confirm('Batalkan pengajuan peminjaman ruangan ini?')) return
  busyId.value = 'r-' + p.id
  try {
    await peminjamanRuanganService.remove(p.id)
    await loadRuangan()
  } catch (err) {
    alert(extractError(err))
  } finally {
    busyId.value = null
  }
}

async function batalkanPerangkat(p) {
  if (!confirm('Batalkan pengajuan peminjaman perangkat ini?')) return
  busyId.value = 'p-' + p.id
  try {
    await peminjamanPerangkatService.remove(p.id)
    await loadPerangkatPeminjaman()
  } catch (err) {
    alert(extractError(err))
  } finally {
    busyId.value = null
  }
}

function bukaPerpanjangan(p) {
  perpanjanganFor.value = p.id
  perpanjanganTanggal.value = ''
  perpanjanganError.value = ''
}

async function submitPerpanjangan(p) {
  savingPerpanjangan.value = true
  perpanjanganError.value = ''
  try {
    await peminjamanPerangkatService.ajukanPerpanjangan(p.id, { tanggal_kembali_baru: perpanjanganTanggal.value })
    perpanjanganFor.value = null
    await loadPerangkatPeminjaman()
  } catch (err) {
    perpanjanganError.value = extractError(err)
  } finally {
    savingPerpanjangan.value = false
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
  // Tab awal dari query (mis. datang dari katalog perangkat: ?tab=perangkat)
  if (route.query.tab === 'perangkat') tab.value = 'perangkat'

  loadRuangan()
  loadPerangkatPeminjaman()
})
</script>

<style scoped>
.tab-bar {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid var(--bs-grey2);
  margin-bottom: 14px;
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
.status-dikembalikan {
  color: #383d41;
  background-color: #e2e3e5;
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
