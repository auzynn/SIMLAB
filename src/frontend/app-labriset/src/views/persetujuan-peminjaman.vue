<template>
  <div>
    <JumbotronSmall title="Persetujuan Peminjaman" />

    <div class="main-container">
      <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
        <div>
          <h1>Persetujuan Peminjaman</h1>
          <div class="profil-title"></div>
        </div>
        <router-link to="/jadwallab" class="btn btn-navy-border" style="width: auto; padding: 8px 20px">
          &larr; Kembali ke Jadwal Lab
        </router-link>
      </div>
      <p class="mt-30" style="max-width: 680px">
        Tinjau dan proses pengajuan peminjaman <strong>ruangan</strong> dan <strong>perangkat</strong>,
        termasuk perpanjangan perangkat, dalam satu tempat.
      </p>

      <!-- Tab entitas -->
      <div class="tab-bar mt-30">
        <button :class="['tab', { active: tab === 'ruangan' }]" @click="tab = 'ruangan'">
          Peminjaman Ruangan ({{ roomMenunggu }})
        </button>
        <button :class="['tab', { active: tab === 'perangkat' }]" @click="tab = 'perangkat'">
          Peminjaman Perangkat ({{ devMenunggu }})
        </button>
        <button :class="['tab', { active: tab === 'perpanjangan' }]" @click="tab = 'perpanjangan'">
          Perpanjangan Perangkat ({{ perpMenunggu.length }})
        </button>
      </div>

      <!-- ============ TAB RUANGAN ============ -->
      <template v-if="tab === 'ruangan'">
        <div class="subfilter mt-20">
          <button :class="['chip', { active: roomStatus === 'menunggu' }]" @click="roomStatus = 'menunggu'">
            Menunggu ({{ roomMenunggu }})
          </button>
          <button :class="['chip', { active: roomStatus === 'semua' }]" @click="roomStatus = 'semua'">Semua</button>
        </div>

        <p v-if="loadingRoom" class="mt-20">Memuat data...</p>
        <p v-else-if="errorRoom" class="mt-20" style="color: #c0392b">{{ errorRoom }}</p>
        <table v-else class="data-table mt-10">
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
                  <select v-model="roomSearch.pengajuField" class="filter-select">
                    <option value="nama">Nama</option>
                    <option value="npm">NPM</option>
                  </select>
                  <input v-model="roomSearch.pengaju" class="filter-input" :placeholder="roomSearch.pengajuField === 'npm' ? 'Cari NPM' : 'Cari nama'" />
                </div>
              </th>
              <th><input v-model="roomSearch.ruangan" class="filter-input" placeholder="Cari ruangan" /></th>
              <th><input v-model="roomSearch.tanggal" class="filter-input" placeholder="Cari tanggal" /></th>
              <th><input v-model="roomSearch.waktu" class="filter-input" placeholder="Cari jam" /></th>
              <th><input v-model="roomSearch.keperluan" class="filter-input" placeholder="Cari keperluan" /></th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in pagedRoom" :key="p.id">
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
                  <button class="btn-link" :disabled="roomBusy === p.id" @click="approveRoom(p)">Setujui</button>
                  <button class="btn-link btn-link-danger" :disabled="roomBusy === p.id" @click="rejectRoom(p)">Tolak</button>
                </template>
                <button class="btn-link btn-link-danger" :disabled="roomBusy === p.id" @click="deleteRoom(p)">Hapus</button>
              </td>
            </tr>
            <tr v-if="!roomFiltered.length">
              <td colspan="7" style="text-align: center; color: #9aa0a6">Tidak ada pengajuan.</td>
            </tr>
          </tbody>
        </table>
        <PaginationBar v-model:page="pageR" :total-pages="totalPagesR" />
      </template>

      <!-- ============ TAB PERANGKAT ============ -->
      <template v-else-if="tab === 'perangkat'">
        <p v-if="loadingDev" class="mt-20">Memuat data...</p>
        <p v-else-if="errorDev" class="mt-20" style="color: #c0392b">{{ errorDev }}</p>
        <table v-else class="data-table mt-20">
          <thead>
            <tr>
              <th>Pengaju</th>
              <th>Perangkat</th>
              <th>Tanggal Pinjam</th>
              <th>Rencana Kembali</th>
              <th>Status</th>
              <th style="text-align: right">Aksi</th>
            </tr>
            <tr class="filter-row">
              <th>
                <div class="filter-pengaju">
                  <select v-model="devSearch.pengajuField" class="filter-select">
                    <option value="nama">Nama</option>
                    <option value="npm">NPM</option>
                  </select>
                  <input v-model="devSearch.pengaju" class="filter-input" :placeholder="devSearch.pengajuField === 'npm' ? 'Cari NPM' : 'Cari nama'" />
                </div>
              </th>
              <th><input v-model="devSearch.perangkat" class="filter-input" placeholder="Cari perangkat" /></th>
              <th><input v-model="devSearch.tglPinjam" class="filter-input" placeholder="Cari tanggal" /></th>
              <th><input v-model="devSearch.tglKembali" class="filter-input" placeholder="Cari tanggal" /></th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in pagedDev" :key="p.id">
              <td>
                <div>{{ p.user?.name }}</div>
                <div v-if="p.user?.mahasiswa?.npm" class="pengaju-npm">{{ p.user.mahasiswa.npm }}</div>
              </td>
              <td>{{ p.perangkat?.nama_perangkat }}</td>
              <td>{{ formatTanggalId(p.tanggal_pinjam) }}</td>
              <td>{{ formatTanggalId(p.tanggal_kembali_rencana) }}</td>
              <td><span :class="['status-badge', `status-${p.status}`]">{{ statusLabel(p.status) }}</span></td>
              <td style="text-align: right; white-space: nowrap">
                <template v-if="p.status === 'menunggu'">
                  <button class="btn-link" :disabled="devBusy === p.id" @click="approveDev(p)">Setujui</button>
                  <button class="btn-link btn-link-danger" :disabled="devBusy === p.id" @click="rejectDev(p)">Tolak</button>
                </template>
                <button
                  v-else-if="p.status === 'disetujui'"
                  class="btn-link"
                  :disabled="devBusy === p.id"
                  @click="kembalikanDev(p)"
                >
                  Konfirmasi Kembali
                </button>
                <button
                  v-else-if="p.status === 'dikembalikan' || p.status === 'ditolak'"
                  class="btn-link btn-link-danger"
                  :disabled="devBusy === p.id"
                  @click="hapusDev(p)"
                >
                  Hapus
                </button>
                <span v-else style="color: #9aa0a6">—</span>
              </td>
            </tr>
            <tr v-if="!devFiltered.length">
              <td colspan="6" style="text-align: center; color: #9aa0a6">Tidak ada pengajuan.</td>
            </tr>
          </tbody>
        </table>
        <PaginationBar v-model:page="pageP" :total-pages="totalPagesP" />
      </template>

      <!-- ============ TAB PERPANJANGAN ============ -->
      <template v-else>
        <p v-if="loadingDev" class="mt-20">Memuat data...</p>
        <p v-else-if="errorDev" class="mt-20" style="color: #c0392b">{{ errorDev }}</p>
        <table v-else class="data-table mt-20">
          <thead>
            <tr>
              <th>Pengaju</th>
              <th>Perangkat</th>
              <th>Rencana Kembali Saat Ini</th>
              <th>Usulan Tanggal Baru</th>
              <th style="text-align: right">Aksi</th>
            </tr>
            <tr class="filter-row">
              <th>
                <div class="filter-pengaju">
                  <select v-model="perpSearch.pengajuField" class="filter-select">
                    <option value="nama">Nama</option>
                    <option value="npm">NPM</option>
                  </select>
                  <input v-model="perpSearch.pengaju" class="filter-input" :placeholder="perpSearch.pengajuField === 'npm' ? 'Cari NPM' : 'Cari nama'" />
                </div>
              </th>
              <th><input v-model="perpSearch.perangkat" class="filter-input" placeholder="Cari perangkat" /></th>
              <th></th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="x in perpFiltered" :key="x.id">
              <td>
                <div>{{ x.peminjaman?.user?.name }}</div>
                <div v-if="x.peminjaman?.user?.mahasiswa?.npm" class="pengaju-npm">{{ x.peminjaman.user.mahasiswa.npm }}</div>
              </td>
              <td>{{ x.peminjaman?.perangkat?.nama_perangkat }}</td>
              <td>{{ formatTanggalId(x.peminjaman?.tanggal_kembali_rencana) }}</td>
              <td>{{ formatTanggalId(x.tanggal_kembali_baru) }}</td>
              <td style="text-align: right; white-space: nowrap">
                <button class="btn-link" :disabled="devBusy === 'pp-' + x.id" @click="approvePerp(x)">Setujui</button>
                <button class="btn-link btn-link-danger" :disabled="devBusy === 'pp-' + x.id" @click="rejectPerp(x)">Tolak</button>
              </td>
            </tr>
            <tr v-if="!perpFiltered.length">
              <td colspan="5" style="text-align: center; color: #9aa0a6">Tidak ada pengajuan perpanjangan.</td>
            </tr>
          </tbody>
        </table>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Persetujuan terpadu (Admin/Supervisor): peminjaman ruangan (UC-02), peminjaman perangkat &
// perpanjangan (UC-03) dalam satu halaman bertab. Masing-masing entitas memuat datanya sendiri.
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { peminjamanRuanganService } from '@/services/peminjaman-ruangan'
import { peminjamanPerangkatService } from '@/services/peminjaman-perangkat'
import { usePagination } from '@/composables/use-pagination'
import { formatTanggalId, formatJam, statusLabel, namaHari } from '@/utils/format'
import { useFeedback } from '@/composables/use-feedback'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const { notify, confirmDialog } = useFeedback()
const route = useRoute()
const tab = ref('ruangan')

const cocok = (val, q) => !q || String(val ?? '').toLowerCase().includes(q.toLowerCase())

// ---------- Ruangan (UC-02) ----------
const roomItems = ref([])
const loadingRoom = ref(false)
const errorRoom = ref('')
const roomBusy = ref(null)
const roomStatus = ref('menunggu')
const roomSearch = ref({ pengajuField: 'nama', pengaju: '', ruangan: '', tanggal: '', waktu: '', keperluan: '' })

const roomFiltered = computed(() => {
  const base = roomStatus.value === 'semua' ? roomItems.value : roomItems.value.filter((p) => p.status === 'menunggu')
  const f = roomSearch.value
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
const { page: pageR, totalPages: totalPagesR, pagedItems: pagedRoom } = usePagination(roomFiltered, 10)
const roomMenunggu = computed(() => roomItems.value.filter((p) => p.status === 'menunggu').length)

async function loadRoom() {
  loadingRoom.value = true
  errorRoom.value = ''
  try {
    const res = await peminjamanRuanganService.list()
    roomItems.value = res.data.data
  } catch (err) {
    errorRoom.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loadingRoom.value = false
  }
}

async function roomAksi(id, fn) {
  roomBusy.value = id
  try {
    await fn()
    await loadRoom()
  } catch (err) {
    notify.error(err.response?.data?.message || 'Operasi gagal.')
    // Muat ulang juga saat gagal: mis. approve yang kalah kuota kini otomatis ditandai
    // 'kadaluarsa' di server, sehingga pengajuan tsb harus hilang dari antrian "menunggu".
    await loadRoom()
  } finally {
    roomBusy.value = null
  }
}
const approveRoom = (p) => roomAksi(p.id, () => peminjamanRuanganService.approve(p.id))
const rejectRoom = async (p) => {
  if (!(await confirmDialog('Tolak pengajuan ini?'))) return
  return roomAksi(p.id, () => peminjamanRuanganService.reject(p.id))
}
const deleteRoom = async (p) => {
  if (!(await confirmDialog('Hapus pengajuan ini dari daftar? Tindakan ini permanen.'))) return
  return roomAksi(p.id, () => peminjamanRuanganService.remove(p.id))
}

// ---------- Perangkat + Perpanjangan (UC-03) ----------
const devItems = ref([])
const loadingDev = ref(false)
const errorDev = ref('')
const devBusy = ref(null)

const devSearch = ref({ pengajuField: 'nama', pengaju: '', perangkat: '', tglPinjam: '', tglKembali: '' })

// Cocok filter dulu, lalu menunggu di atas.
const devFiltered = computed(() => {
  const f = devSearch.value
  return [...devItems.value]
    .filter((p) => {
      const pengajuVal = f.pengajuField === 'npm' ? p.user?.mahasiswa?.npm : p.user?.name
      return (
        cocok(pengajuVal, f.pengaju) &&
        cocok(p.perangkat?.nama_perangkat, f.perangkat) &&
        cocok(formatTanggalId(p.tanggal_pinjam), f.tglPinjam) &&
        cocok(formatTanggalId(p.tanggal_kembali_rencana), f.tglKembali)
      )
    })
    .sort((a, b) => (a.status === 'menunggu' ? -1 : 0) - (b.status === 'menunggu' ? -1 : 0))
})
const { page: pageP, totalPages: totalPagesP, pagedItems: pagedDev } = usePagination(devFiltered, 10)
const devMenunggu = computed(() => devItems.value.filter((p) => p.status === 'menunggu').length)

const perpMenunggu = computed(() =>
  devItems.value.flatMap((p) =>
    (p.perpanjangan ?? []).filter((x) => x.status === 'menunggu').map((x) => ({ ...x, peminjaman: p })),
  ),
)
const perpSearch = ref({ pengajuField: 'nama', pengaju: '', perangkat: '' })
const perpFiltered = computed(() => {
  const f = perpSearch.value
  return perpMenunggu.value.filter((x) => {
    const pengajuVal = f.pengajuField === 'npm' ? x.peminjaman?.user?.mahasiswa?.npm : x.peminjaman?.user?.name
    return cocok(pengajuVal, f.pengaju) && cocok(x.peminjaman?.perangkat?.nama_perangkat, f.perangkat)
  })
})

async function loadDev() {
  loadingDev.value = true
  errorDev.value = ''
  try {
    const res = await peminjamanPerangkatService.list()
    devItems.value = res.data.data
  } catch (err) {
    errorDev.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loadingDev.value = false
  }
}

async function devAksi(id, fn) {
  devBusy.value = id
  try {
    await fn()
    await loadDev()
  } catch (err) {
    notify.error(err.response?.data?.message || 'Operasi gagal.')
  } finally {
    devBusy.value = null
  }
}
const approveDev = (p) => devAksi(p.id, () => peminjamanPerangkatService.approve(p.id))
const rejectDev = async (p) => {
  if (!(await confirmDialog('Tolak pengajuan ini?'))) return
  return devAksi(p.id, () => peminjamanPerangkatService.reject(p.id))
}
const kembalikanDev = async (p) => {
  if (!(await confirmDialog({ message: 'Konfirmasi bahwa perangkat sudah dikembalikan?', variant: 'info', confirmText: 'Ya, Kembalikan' }))) return
  return devAksi(p.id, () => peminjamanPerangkatService.kembalikan(p.id))
}
// Hapus riwayat — hanya untuk peminjaman yang sudah dikembalikan (bukan yang sedang berjalan).
const hapusDev = async (p) => {
  if (!(await confirmDialog('Hapus riwayat peminjaman ini dari daftar? Tindakan ini permanen.'))) return
  return devAksi(p.id, () => peminjamanPerangkatService.remove(p.id))
}
const approvePerp = (x) => devAksi('pp-' + x.id, () => peminjamanPerangkatService.approvePerpanjangan(x.id))
const rejectPerp = async (x) => {
  if (!(await confirmDialog('Tolak perpanjangan ini?'))) return
  return devAksi('pp-' + x.id, () => peminjamanPerangkatService.rejectPerpanjangan(x.id))
}

onMounted(() => {
  if (route.query.tab === 'perangkat') tab.value = 'perangkat'
  else if (route.query.tab === 'perpanjangan') tab.value = 'perpanjangan'
  loadRoom()
  loadDev()
})
</script>

<style scoped>
.tab-bar {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid var(--bs-grey2);
  flex-wrap: wrap;
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

/* Sub-filter status (khusus tab Ruangan) */
.subfilter {
  display: flex;
  gap: 8px;
}
.chip {
  background: none;
  border: 1px solid var(--bs-grey2);
  border-radius: 20px;
  padding: 5px 16px;
  cursor: pointer;
  font-size: 0.85em;
  font-weight: 600;
  color: #5f6368;
}
.chip.active {
  border-color: var(--bs-navy);
  background-color: var(--bs-navy);
  color: #fff;
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
