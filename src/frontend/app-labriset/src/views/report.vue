<template>
  <div>
    <JumbotronSmall title="Laporan" />

    <div class="main-container">
      <div>
        <h1>Laporan Aktivitas Lab</h1>
        <div class="profil-title"></div>
      </div>

      <p class="mt-30 page-desc" style="max-width: 640px">
        Rekap peminjaman ruangan, peminjaman perangkat, dan pengumpulan tugas dalam rentang tanggal.
        Kosongkan tanggal untuk memakai default 30 hari terakhir.
      </p>

      <!-- Filter rentang tanggal + unduh PDF -->
      <div class="filter-bar">
        <label>Dari <input type="date" v-model="from" /></label>
        <label>Sampai <input type="date" v-model="to" /></label>
        <button class="btn-primary" :disabled="loading" @click="muat">Terapkan</button>
        <button class="btn-outline" :disabled="loading || unduh" @click="unduhPdf">
          {{ unduh ? 'Menyiapkan…' : 'Unduh PDF' }}
        </button>
      </div>

      <p v-if="error" class="error mt-20">{{ error }}</p>

      <div v-if="rekap" class="report-grid mt-30">
        <div class="report-card">
          <h3>Peminjaman Ruangan</h3>
          <ul>
            <li><span>Total Pengajuan</span><b>{{ rekap.peminjaman_ruangan.total_pengajuan }}</b></li>
            <li><span>Disetujui</span><b>{{ rekap.peminjaman_ruangan.total_disetujui }}</b></li>
            <li><span>Ditolak</span><b>{{ rekap.peminjaman_ruangan.total_ditolak }}</b></li>
            <li><span>Menunggu</span><b>{{ rekap.peminjaman_ruangan.total_menunggu }}</b></li>
          </ul>
        </div>

        <div class="report-card">
          <h3>Peminjaman Perangkat</h3>
          <ul>
            <li><span>Total Pengajuan</span><b>{{ rekap.peminjaman_perangkat.total_pengajuan }}</b></li>
            <li><span>Disetujui</span><b>{{ rekap.peminjaman_perangkat.total_disetujui }}</b></li>
            <li><span>Ditolak</span><b>{{ rekap.peminjaman_perangkat.total_ditolak }}</b></li>
            <li><span>Dikembalikan</span><b>{{ rekap.peminjaman_perangkat.total_dikembalikan }}</b></li>
          </ul>
        </div>

        <div class="report-card">
          <h3>Pengumpulan Tugas</h3>
          <ul>
            <li><span>Total Terkumpul</span><b>{{ rekap.tugas.total_terkumpul }}</b></li>
            <li><span>Mahasiswa Unik</span><b>{{ rekap.tugas.total_mahasiswa_unik }}</b></li>
            <li><span>Jumlah Kelas</span><b>{{ rekap.tugas.total_kelas }}</b></li>
          </ul>
        </div>
      </div>

      <p v-if="rekap" class="periode-note mt-20">
        Periode: {{ rekap.periode.dari }} s.d. {{ rekap.periode.sampai }}
      </p>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Halaman Laporan (FASE 8, SRS UC-06) — Admin/Supervisor. Rekap + unduh PDF via reportService.
import { ref, onMounted } from 'vue'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import { reportService } from '@/services/report'

const from = ref('')
const to = ref('')
const rekap = ref(null)
const loading = ref(false)
const unduh = ref(false)
const error = ref('')

// Susun params hanya untuk tanggal yang diisi (biar backend pakai default bila kosong).
function params() {
  const p = {}
  if (from.value) p.from = from.value
  if (to.value) p.to = to.value
  return p
}

async function muat() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await reportService.rekap(params())
    rekap.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat laporan.'
  } finally {
    loading.value = false
  }
}

async function unduhPdf() {
  unduh.value = true
  error.value = ''
  try {
    const res = await reportService.pdf(params())
    // Buat object URL dari blob lalu picu unduhan file.
    const url = URL.createObjectURL(res.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `laporan-lab-${rekap.value?.periode?.dari || 'rekap'}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    error.value = 'Gagal mengunduh PDF.'
  } finally {
    unduh.value = false
  }
}

onMounted(muat)
</script>

<style scoped>
.page-desc {
  margin-bottom: 28px;
}

.filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 14px;
}

.filter-bar label {
  display: flex;
  flex-direction: column;
  font-size: 0.9em;
  color: var(--bs-navy);
  gap: 4px;
}

.filter-bar input {
  padding: 8px 10px;
  border: 1px solid var(--bs-grey2);
  border-radius: 6px;
}

.btn-primary,
.btn-outline {
  padding: 9px 20px;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
  border: 2px solid var(--bs-navy);
}

.btn-primary {
  background-color: var(--bs-navy);
  color: #fff;
}

.btn-outline {
  background-color: transparent;
  color: var(--bs-navy);
}

.btn-primary:disabled,
.btn-outline:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.report-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.report-card {
  padding: 24px;
  background-color: #fff;
  border-radius: 8px;
  border-left: 6px solid var(--bs-navy);
  box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 0.1);
}

.report-card h3 {
  color: var(--bs-navy);
  margin-bottom: 12px;
}

.report-card ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.report-card li {
  display: flex;
  justify-content: space-between;
  padding: 7px 0;
  border-bottom: 1px solid var(--bs-grey2);
}

.report-card li:last-child {
  border-bottom: none;
}

.report-card li b {
  color: var(--bs-navy);
}

.periode-note {
  color: #6b7280;
  font-size: 0.9em;
}

.error {
  color: #b91c1c;
}
</style>
