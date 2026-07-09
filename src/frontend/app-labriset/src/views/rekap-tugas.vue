<template>
  <div>
    <JumbotronSmall title="Rekap Tugas" />

    <div class="main-container">
      <div>
        <h1>Rekap Tugas Kelas Lab</h1>
        <div class="profil-title"></div>
      </div>

      <p class="mt-30 page-desc" style="max-width: 680px">
        Rekap pengumpulan tugas per kelas & per pertemuan selama satu semester. Data selalu
        mengikuti tugas terbaru yang masuk. Unduh sebagai PDF atau Excel kapan saja.
      </p>

      <div class="filter-bar">
        <button class="btn-primary" :disabled="loading" @click="muat">
          {{ loading ? 'Memuat…' : 'Muat Ulang' }}
        </button>
        <button class="btn-outline" :disabled="loading || unduh.pdf" @click="unduhFile('pdf')">
          {{ unduh.pdf ? 'Menyiapkan…' : 'Unduh PDF' }}
        </button>
        <button class="btn-outline" :disabled="loading || unduh.excel" @click="unduhFile('excel')">
          {{ unduh.excel ? 'Menyiapkan…' : 'Unduh Excel' }}
        </button>
        <span v-if="rekap" class="periode-note">Diperbarui: {{ rekap.generated_at }} WIB</span>
      </div>

      <p v-if="error" class="error mt-20">{{ error }}</p>

      <template v-if="rekap">
        <!-- Ringkasan kepatuhan per kelas -->
        <h2 class="section-title mt-30">Ringkasan Tugas Kelas Lab</h2>
        <div class="table-wrap">
          <table class="rekap-table">
            <thead>
              <tr>
                <th>Mata Kuliah</th><th>Sesi</th><th>Dosen</th><th>Jadwal</th>
                <th>Peserta</th><th>Bertugas</th><th>Pertemuan</th><th>Tunggakan</th><th>Status</th><th>Deadline Terdekat</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in rekap.ringkasan" :key="r.kelas_lab_id">
                <td>{{ r.mata_kuliah }}</td>
                <td>{{ r.nama_sesi }}</td>
                <td>{{ r.dosen || '-' }}</td>
                <td>{{ capitalize(r.hari) }} {{ r.jam }}</td>
                <td class="c">{{ r.peserta_disetujui }}</td>
                <td class="c">{{ r.pertemuan_bertugas }}/16</td>
                <td class="c">{{ r.pertemuan_berjalan }}/16</td>
                <td class="c">{{ r.tunggakan }}</td>
                <td><span :class="['status-pill', `st-${r.status}`]">{{ statusLabel(r.status) }}</span></td>
                <td>{{ r.deadline_terdekat || '-' }}</td>
              </tr>
              <tr v-if="!rekap.ringkasan.length">
                <td colspan="10" class="kosong">Belum ada kelas untuk direkap.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Matriks detail per kelas -->
        <h2 class="section-title mt-30">Detail per Kelas</h2>
        <div v-for="kelas in rekap.detail" :key="kelas.kelas_lab_id" class="kelas-block">
          <button class="kelas-head" @click="toggle(kelas.kelas_lab_id)">
            <span class="kelas-head-title">
              {{ kelas.mata_kuliah }} — {{ kelas.nama_sesi }}
              <small>{{ kelas.dosen || '-' }} · {{ capitalize(kelas.hari) }} {{ kelas.jam }}</small>
            </span>
            <span class="chev">{{ terbuka[kelas.kelas_lab_id] ? '▲' : '▼' }}</span>
          </button>

          <div v-show="terbuka[kelas.kelas_lab_id]" class="table-wrap">
            <p v-if="!kelas.pertemuan.length" class="kosong pad">Belum ada pertemuan yang diberi tugas/deadline.</p>
            <table v-else class="rekap-table matriks">
              <thead>
                <tr>
                  <th class="sticky-l">NPM</th><th class="sticky-l2">Nama</th>
                  <th v-for="p in kelas.pertemuan" :key="p.pertemuan" class="c" :title="tooltipPertemuan(p)">
                    P{{ p.pertemuan }}
                  </th>
                  <th class="c">Total</th><th class="c">Telat</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ps in kelas.peserta" :key="ps.npm">
                  <td class="sticky-l">{{ ps.npm }}</td>
                  <td class="sticky-l2">{{ ps.nama }}</td>
                  <td v-for="p in kelas.pertemuan" :key="p.pertemuan" :class="['c', selClass(ps.sel[p.pertemuan])]">
                    <a v-if="ps.sel[p.pertemuan]?.tautan" :href="ps.sel[p.pertemuan].tautan" target="rel" rel="noopener" :title="selTitle(ps.sel[p.pertemuan])">
                      {{ selSymbol(ps.sel[p.pertemuan]) }}
                    </a>
                    <span v-else :title="selTitle(ps.sel[p.pertemuan])">{{ selSymbol(ps.sel[p.pertemuan]) }}</span>
                  </td>
                  <td class="c">{{ ps.total_kumpul }}</td>
                  <td class="c">{{ ps.telat }}</td>
                </tr>
                <tr v-if="!kelas.peserta.length">
                  <td :colspan="kelas.pertemuan.length + 4" class="kosong">Belum ada peserta disetujui.</td>
                </tr>
              </tbody>
            </table>
            <p v-if="kelas.pertemuan.length" class="legend pad">
              ✓ tepat waktu · ! telat · – belum mengumpulkan
            </p>
          </div>
        </div>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Halaman Rekap Tugas (SRS UC-06, 3_SDD.md 5.15) — Admin/Supervisor/Dosen.
// Ringkasan + matriks per pertemuan; unduh PDF/Excel via rekapTugasService (blob download).
import { ref, reactive, onMounted } from 'vue'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
import { rekapTugasService } from '@/services/rekap-tugas'

const rekap = ref(null)
const loading = ref(false)
const unduh = reactive({ pdf: false, excel: false })
const error = ref('')
const terbuka = reactive({})

function capitalize(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''
}

function statusLabel(s) {
  return { perhatian: 'Perlu perhatian', berjalan: 'Berjalan', beres: 'Beres' }[s] || s
}

function selSymbol(sel) {
  return { tepat: '✓', telat: '!', belum: '–' }[sel?.status] || '–'
}

function selClass(sel) {
  return `cell-${sel?.status || 'belum'}`
}

function selTitle(sel) {
  if (!sel || sel.status === 'belum') return 'Belum mengumpulkan'
  return `${sel.judul || 'Tugas'} — dikumpulkan ${sel.dikumpulkan}${sel.status === 'telat' ? ' (telat)' : ''}`
}

function tooltipPertemuan(p) {
  return `${p.materi || '(tanpa materi)'} — deadline ${p.deadline}`
}

function toggle(id) {
  terbuka[id] = !terbuka[id]
}

async function muat() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await rekapTugasService.rekap()
    rekap.value = data.data
    // Buka otomatis kelas pertama agar pengguna langsung melihat matriks.
    if (rekap.value.detail.length) terbuka[rekap.value.detail[0].kelas_lab_id] = true
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat rekap tugas.'
  } finally {
    loading.value = false
  }
}

async function unduhFile(jenis) {
  unduh[jenis] = true
  error.value = ''
  try {
    const res = jenis === 'pdf' ? await rekapTugasService.pdf() : await rekapTugasService.excel()
    const ext = jenis === 'pdf' ? 'pdf' : 'xlsx'
    const tgl = (rekap.value?.generated_at || '').slice(0, 10) || 'rekap'
    const url = URL.createObjectURL(res.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `rekap-tugas-${tgl}.${ext}`
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    error.value = `Gagal mengunduh ${jenis.toUpperCase()}.`
  } finally {
    unduh[jenis] = false
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
  align-items: center;
  gap: 14px;
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

.section-title {
  color: var(--bs-navy);
  border-bottom: 2px solid var(--bs-grey2);
  padding-bottom: 6px;
}

.table-wrap {
  overflow-x: auto;
  background: #fff;
  border-radius: 8px;
  box-shadow: 3px 3px 8px 0 rgba(0, 0, 0, 0.08);
}

.rekap-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.88em;
}

.rekap-table th,
.rekap-table td {
  border: 1px solid var(--bs-grey2);
  padding: 7px 10px;
  text-align: left;
  white-space: nowrap;
}

.rekap-table th {
  background: #f3f4f6;
  color: var(--bs-navy);
}

.rekap-table td.c,
.rekap-table th.c {
  text-align: center;
}

.status-pill {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 12px;
  font-size: 0.85em;
  font-weight: 700;
}

.st-perhatian {
  background: #f8d7da;
  color: #842029;
}

.st-berjalan {
  background: #fff3cd;
  color: #664d03;
}

.st-beres {
  background: #d1e7dd;
  color: #0f5132;
}

.cell-tepat {
  background: #d1e7dd;
}

.cell-telat {
  background: #fff3cd;
}

.cell-belum {
  background: #f8d7da;
}

.matriks a {
  color: inherit;
  font-weight: 700;
  text-decoration: none;
}

.sticky-l,
.sticky-l2 {
  position: sticky;
  background: #fff;
  z-index: 1;
}

.sticky-l {
  left: 0;
}

.sticky-l2 {
  left: 90px;
}

.rekap-table th.sticky-l,
.rekap-table th.sticky-l2 {
  background: #f3f4f6;
}

.kelas-block {
  margin-top: 14px;
}

.kelas-head {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: var(--bs-navy);
  color: #fff;
  border: none;
  border-radius: 8px 8px 0 0;
  cursor: pointer;
  font-weight: 700;
}

.kelas-head-title small {
  display: block;
  font-weight: 400;
  opacity: 0.85;
  font-size: 0.82em;
  margin-top: 2px;
}

.periode-note {
  color: #6b7280;
  font-size: 0.9em;
}

.legend {
  color: #6b7280;
  font-size: 0.85em;
}

.pad {
  padding: 10px 14px;
}

.kosong {
  color: #9ca3af;
  font-style: italic;
  text-align: center;
  padding: 14px;
}

.error {
  color: #b91c1c;
}
</style>
