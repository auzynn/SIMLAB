<template>
  <div>
    <JumbotronSmall title="Detail Pertemuan" />

    <div class="main-container">
      <p v-if="loading" class="mt-20">Memuat data...</p>
      <p v-else-if="loadError" class="mt-20" style="color: #c0392b">{{ loadError }}</p>

      <template v-else>
        <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
          <div>
            <h1>{{ kelas?.mata_kuliah?.nama_mk }} — Pertemuan {{ pertemuan }}</h1>
            <div class="profil-title"></div>
          </div>
          <router-link :to="`/kelaslab/${kelasId}/detail`" class="btn btn-navy-border" style="display: inline-block; width: auto; padding: 8px 20px; flex-shrink: 0">
            &larr; Kembali ke Detail Kelas
          </router-link>
        </div>

        <!-- Info sesi + deadline -->
        <div class="card mt-30">
          <div class="kelas-meta">
            <span class="meta-pill">{{ kelas?.nama_sesi }}</span>
            <span class="meta-item">{{ hariLabel(kelas?.hari) }}, {{ formatJam(kelas?.jam_mulai) }}–{{ formatJam(kelas?.jam_selesai) }}</span>
            <span class="meta-item">{{ kelas?.ruangan?.nama_ruangan }}</span>
          </div>
          <p v-if="materi" class="materi-box mt-30"><span class="materi-label">Materi:</span> {{ materi }}</p>
          <p class="deadline-box" :class="materi ? 'mt-10' : 'mt-30'">
            <span class="deadline-label">Batas Waktu Pengumpulan (Deadline):</span>
            <span v-if="deadline" class="deadline-value">{{ formatDeadline(deadline) }} WIB</span>
            <span v-else class="deadline-none">Belum ada tugas / deadline untuk pertemuan ini.</span>
          </p>
        </div>

        <!-- Rekap pengumpulan -->
        <div class="card mt-20">
          <div class="flex-h between" style="align-items: center; flex-wrap: wrap; gap: 8px">
            <h3>Status Pengumpulan Mahasiswa</h3>
            <div v-if="deadline" class="rekap-stats">
              <div class="stat"><span class="stat-num">{{ sudahList.length }}</span> sudah</div>
              <div class="stat"><span class="stat-num">{{ belumList.length }}</span> belum</div>
            </div>
          </div>

          <!-- Tanpa deadline = tidak ada tugas untuk pertemuan ini -->
          <p v-if="!deadline" class="notice-none mt-20">
            Belum ada tugas untuk pertemuan ini. Tetapkan batas waktu (deadline) di halaman Detail Kelas
            agar pengumpulan mahasiswa dapat dipantau.
          </p>
          <template v-else>
            <p v-if="!pesertaDisetujui.length" class="mt-20 muted">Belum ada peserta disetujui pada kelas ini.</p>
            <table v-else class="data-table mt-20">
              <thead>
                <tr>
                  <th style="width: 56px">No</th><th>NPM</th><th>Nama Mahasiswa</th>
                  <th style="width: 150px">Status</th><th>Judul Tugas</th><th style="width: 90px; text-align: right">Tautan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(p, i) in pesertaDisetujui" :key="p.id">
                  <td>{{ i + 1 }}</td>
                  <td>{{ p.mahasiswa?.npm ?? '-' }}</td>
                  <td>{{ p.mahasiswa?.user?.name ?? '-' }}</td>
                  <td>
                    <template v-if="tugasByMhs(p.mahasiswa_id)">
                      <span v-if="telatKirim(p.mahasiswa_id)" class="badge-telat">Sudah · Terlambat</span>
                      <span v-else class="badge-sudah">Sudah mengumpulkan</span>
                    </template>
                    <span v-else class="badge-belum">Belum</span>
                  </td>
                  <td>
                    <span v-if="tugasByMhs(p.mahasiswa_id)">{{ tugasByMhs(p.mahasiswa_id).judul }}</span>
                    <span v-else class="muted">—</span>
                  </td>
                  <td style="text-align: right">
                    <a v-if="tugasByMhs(p.mahasiswa_id)" :href="tugasByMhs(p.mahasiswa_id).tautan" target="_blank" rel="noopener" class="btn-buka">Buka</a>
                    <span v-else class="muted">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </template>
        </div>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Halaman Detail Pertemuan (untuk Dosen pengampu / Supervisor / Admin):
// menampilkan deadline pertemuan + daftar peserta yang SUDAH dan BELUM mengumpulkan tugas.
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { kelasLabService } from '@/services/kelas-lab'
import { tugasService } from '@/services/tugas'
import { formatJam, hariLabel, formatDeadline, dikirimTerlambat } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const route = useRoute()
const kelasId = Number(route.params.id)
const pertemuan = Number(route.params.pertemuan)

const kelas = ref(null)
const peserta = ref([])
const tugas = ref([])
const deadline = ref(null)
const materi = ref(null)
const loading = ref(false)
const loadError = ref('')

const pesertaDisetujui = computed(() => peserta.value.filter((p) => p.status === 'disetujui'))

// Tugas yang tertaut ke kelas + pertemuan ini.
const tugasPertemuan = computed(() => tugas.value.filter((t) => t.kelas_lab_id === kelasId && t.pertemuan === pertemuan))

function tugasByMhs(mahasiswaId) {
  return tugasPertemuan.value.find((t) => t.mahasiswa_id === mahasiswaId) || null
}

// Apakah tugas mahasiswa ini dikirim setelah deadline pertemuan.
function telatKirim(mahasiswaId) {
  const t = tugasByMhs(mahasiswaId)
  return !!t && dikirimTerlambat(t.created_at, deadline.value)
}

const sudahList = computed(() => pesertaDisetujui.value.filter((p) => tugasByMhs(p.mahasiswa_id)))
const belumList = computed(() => pesertaDisetujui.value.filter((p) => !tugasByMhs(p.mahasiswa_id)))

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const [detail, pes, tug, dl] = await Promise.all([
      kelasLabService.show(kelasId),
      kelasLabService.peserta(kelasId),
      tugasService.list(),
      kelasLabService.deadlineList(kelasId),
    ])
    kelas.value = detail.data.data
    peserta.value = pes.data.data
    tugas.value = tug.data.data
    const entri = dl.data.data.find((d) => d.pertemuan === pertemuan) || {}
    deadline.value = entri.deadline || null
    materi.value = entri.materi || null
  } catch (err) {
    loadError.value = err.response?.data?.message || 'Gagal memuat detail pertemuan.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.card {
  background-color: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 0.1);
}
.muted {
  color: #9aa0a6;
}
.kelas-meta {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  color: #3c4043;
}
.meta-pill {
  padding: 5px 18px;
  border-radius: 20px;
  background-color: var(--bs-navy);
  color: #fff;
  font-weight: 600;
  font-size: 0.9em;
}
.meta-item {
  font-size: 0.95em;
}
.materi-box {
  padding: 12px 16px;
  background-color: #eef1f7;
  border-left: 4px solid var(--bs-navy);
  border-radius: 6px;
  font-size: 0.95em;
  color: #3c4043;
}
.materi-label {
  font-weight: 700;
  color: var(--bs-navy);
  margin-right: 6px;
}
.deadline-box {
  padding: 12px 16px;
  background-color: #fdecec;
  border-left: 4px solid #c0392b;
  border-radius: 6px;
  font-size: 0.95em;
}
.deadline-label {
  font-weight: 700;
  color: #c0392b;
  margin-right: 6px;
}
.deadline-value {
  font-weight: 700;
  color: #c0392b;
}
.deadline-none {
  color: #9aa0a6;
}
.notice-none {
  padding: 12px 16px;
  background-color: #f0f2f4;
  border-radius: 6px;
  color: #5f6368;
  font-size: 0.9em;
}
.rekap-stats {
  display: flex;
  gap: 20px;
}
.stat {
  color: #5f6368;
  font-size: 0.9em;
}
.stat-num {
  font-size: 1.4em;
  font-weight: 700;
  color: var(--bs-navy);
  margin-right: 4px;
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
  color: #5f6368;
  font-size: 0.9em;
}
.badge-sudah {
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 0.8em;
  font-weight: 600;
  color: #1e7e34;
  background-color: #e6f4ea;
}
.badge-belum {
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 0.8em;
  font-weight: 600;
  color: #c0392b;
  background-color: #fdecec;
}
.badge-telat {
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 0.8em;
  font-weight: 700;
  color: #fff;
  background-color: #d98a00;
}
.btn-buka {
  display: inline-block;
  padding: 5px 16px;
  border-radius: 6px;
  background-color: var(--bs-navy);
  color: #fff;
  font-size: 0.85em;
  font-weight: 600;
}
</style>
