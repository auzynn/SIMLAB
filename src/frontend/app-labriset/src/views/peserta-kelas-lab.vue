<template>
  <div>
    <JumbotronSmall title="Peserta Kelas Lab" />

    <div class="main-container">
      <p v-if="loading" class="mt-20">Memuat data...</p>
      <p v-else-if="loadError" class="mt-20" style="color: #c0392b">{{ loadError }}</p>

      <template v-else>
        <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
          <div>
            <h1>{{ kelas?.mata_kuliah?.nama_mk }}</h1>
            <div class="profil-title"></div>
          </div>
          <button type="button" class="btn btn-navy-border" style="display: inline-block; width: auto; padding: 8px 20px; flex-shrink: 0" @click="kembali">
            &larr; Kembali
          </button>
        </div>

        <div class="kelas-meta mt-30">
          <span class="meta-pill">{{ kelas?.nama_sesi }}</span>
          <span class="meta-item"><span class="meta-label">Jadwal:</span> {{ hariLabel(kelas?.hari) }}, {{ formatJam(kelas?.jam_mulai) }}–{{ formatJam(kelas?.jam_selesai) }}</span>
          <span class="meta-item"><span class="meta-label">Ruangan:</span> {{ kelas?.ruangan?.nama_ruangan }}</span>
          <span class="meta-item"><span class="meta-label">Pengampu:</span> {{ kelas?.dosen?.user?.name ?? '-' }}</span>
          <span class="meta-item"><span class="meta-label">Terisi:</span> {{ peserta.length }}/{{ kelas?.kuota }}</span>
        </div>

        <table class="data-table mt-30">
          <thead>
            <tr>
              <th style="width: 56px">No</th>
              <th>NPM</th>
              <th>Nama Mahasiswa</th>
              <th>Prodi</th>
              <th>Status</th>
              <th style="text-align: right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(p, i) in peserta" :key="p.id">
              <td>{{ i + 1 }}</td>
              <td>{{ p.mahasiswa?.npm ?? '-' }}</td>
              <td>{{ p.mahasiswa?.user?.name ?? '-' }}</td>
              <td>{{ p.mahasiswa?.prodi ?? '-' }}</td>
              <td><span :class="['status-badge', `status-${p.status}`]">{{ statusLabel(p.status) }}</span></td>
              <td style="text-align: right; white-space: nowrap">
                <button v-if="p.status === 'menunggu'" class="btn-link" :disabled="busyId === p.id" @click="terima(p)">Terima</button>
                <button class="btn-link btn-link-danger" :disabled="busyId === p.id" @click="keluarkan(p)">Keluarkan</button>
              </td>
            </tr>
            <tr v-if="!peserta.length">
              <td colspan="6" style="text-align: center; color: #9aa0a6">Belum ada mahasiswa terdaftar.</td>
            </tr>
          </tbody>
        </table>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Halaman daftar peserta satu sesi Kelas Lab (terpisah dari halaman Kelola Kelas Lab).
// Akses: pemilik kelas (Dosen) atau Supervisor — divalidasi backend (KelasLabPolicy::viewPeserta).
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { kelasLabService } from '@/services/kelas-lab'
import { formatJam, hariLabel, statusLabel } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const route = useRoute()
const router = useRouter()
const kelasId = route.params.id

// Halaman ini dijangkau dari >1 halaman (Kelola Kelas Lab & Detail Kelas Lab),
// jadi kembali ke halaman sebelumnya. Fallback ke Kelola bila tak ada history
// (mis. dibuka langsung via URL / tab baru).
function kembali() {
  if (window.history.length > 1) router.back()
  else router.push('/kelaslab/kelola')
}

const kelas = ref(null)
const peserta = ref([])
const loading = ref(false)
const loadError = ref('')
const busyId = ref(null)

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const [detail, list] = await Promise.all([
      kelasLabService.show(kelasId),
      kelasLabService.peserta(kelasId),
    ])
    kelas.value = detail.data.data
    peserta.value = list.data.data
  } catch (err) {
    loadError.value = err.response?.data?.message || 'Gagal memuat data peserta.'
  } finally {
    loading.value = false
  }
}

async function terima(p) {
  busyId.value = p.id
  try {
    await kelasLabService.approvePendaftaran(p.id)
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyetujui.')
  } finally {
    busyId.value = null
  }
}

async function keluarkan(p) {
  if (!confirm(`Keluarkan ${p.mahasiswa?.user?.name ?? 'mahasiswa'} dari kelas ini?`)) return
  busyId.value = p.id
  try {
    await kelasLabService.hapusPeserta(p.id)
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengeluarkan peserta.')
  } finally {
    busyId.value = null
  }
}

onMounted(load)
</script>

<style scoped>
.kelas-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 24px;
  color: #3c4043;
}
.meta-pill {
  padding: 5px 18px;
  border-radius: 20px;
  background-color: var(--bs-navy);
  color: #fff;
  font-weight: 600;
  font-size: 0.9em;
  margin-right: 18px;
}
.meta-item {
  font-size: 0.95em;
}
.meta-label {
  font-weight: 600;
  color: var(--bs-navy);
  margin-right: 3px;
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
