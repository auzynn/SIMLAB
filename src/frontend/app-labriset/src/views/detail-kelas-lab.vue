<template>
  <div>
    <JumbotronSmall title="Detail Kelas Lab" />

    <div class="main-container">
      <p v-if="loading" class="mt-20">Memuat data...</p>
      <p v-else-if="loadError" class="mt-20" style="color: #c0392b">{{ loadError }}</p>

      <template v-else>
        <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
          <div>
            <h1>{{ kelas?.mata_kuliah?.nama_mk }}</h1>
            <div class="profil-title"></div>
          </div>
          <router-link to="/kelaslab" class="btn btn-navy-border" style="display: inline-block; width: auto; padding: 8px 20px; flex-shrink: 0">
            &larr; Kembali ke Kelas Lab
          </router-link>
        </div>

        <!-- ===== Detail sesi ===== -->
        <div class="card mt-30">
          <div class="kelas-meta">
            <span class="meta-pill">{{ kelas?.nama_sesi }}</span>
            <span v-if="statusSaya" :class="['status-badge', `status-${statusSaya}`]">{{ statusLabel(statusSaya) }}</span>
          </div>
          <div class="detail-grid mt-30">
            <div class="detail-item"><span class="detail-label">Jadwal</span>{{ hariLabel(kelas?.hari) }}, {{ formatJam(kelas?.jam_mulai) }}–{{ formatJam(kelas?.jam_selesai) }}</div>
            <div class="detail-item"><span class="detail-label">Ruangan</span>{{ kelas?.ruangan?.nama_ruangan ?? '-' }}</div>
            <div class="detail-item"><span class="detail-label">Pengampu</span>{{ kelas?.dosen?.user?.name ?? '-' }}</div>
            <div class="detail-item"><span class="detail-label">Kuota</span>{{ kelas?.kuota }} peserta</div>
            <div class="detail-item"><span class="detail-label">Semester</span>{{ formatTanggalId(kelas?.tanggal_mulai_semester) }} – {{ formatTanggalId(kelas?.tanggal_selesai_semester) }}</div>
          </div>
        </div>

        <!-- ===== Tautan pengumpulan dari dosen (jika ada) ===== -->
        <div v-if="kelas?.tautan_pengumpulan" class="card mt-20">
          <h3>{{ isMahasiswa ? 'Unggah Dokumen Laporan' : 'Tautan Pengumpulan Dokumen' }}</h3>
          <p class="muted" style="margin-top: 4px">
            <template v-if="isMahasiswa">Unggah dokumen laporan Anda melalui tautan berikut, lalu kirim tautan projek Anda lewat menu Kirim Tugas.</template>
            <template v-else>Tautan tempat mahasiswa mengunggah dokumen laporan tugas untuk kelas ini.</template>
          </p>
          <a :href="kelas.tautan_pengumpulan" target="_blank" rel="noopener" class="btn btn-navy-border" style="display: inline-block; width: auto; padding: 8px 20px; margin-top: 14px">
            {{ isMahasiswa ? 'Buka Tautan Unggah Dokumen' : 'Buka Tautan Pengumpulan' }}
          </a>
          <p v-if="isMahasiswa" class="format-note">
            <strong>Format penamaan file:</strong> NamaTugas_NPM_Nama
          </p>
        </div>

        <!-- ===== 16 pertemuan pengumpulan Tugas Projek (Mahasiswa) ===== -->
        <div v-if="isMahasiswa" class="card mt-20">
          <div class="flex-h between" style="align-items: center; flex-wrap: wrap; gap: 8px">
            <h3>Tugas Projek Saya di Kelas Ini</h3>
            <router-link to="/tugas" class="btn btn-navy-solid" style="width: auto; padding: 6px 16px">Kirim Tugas</router-link>
          </div>
          <p class="muted" style="margin-top: 4px">Klik sebuah pertemuan untuk melihat batas waktu dan tugas yang Anda kirim.</p>

          <p v-if="loadingTugas" class="mt-20 muted">Memuat tugas...</p>
          <div v-else class="pertemuan-grid mt-20">
            <div v-for="n in 16" :key="n" class="pertemuan-item" :class="{ open: pertemuanTerbuka === n }">
              <button type="button" class="pertemuan-head" @click="togglePertemuan(n)">
                <div class="ph-left">
                  <span class="ph-caret">{{ pertemuanTerbuka === n ? '▾' : '▸' }}</span>
                  <span>
                    <strong>Pertemuan {{ n }}</strong>
                    <span v-if="materiMap[n]" class="ph-materi">{{ materiMap[n] }}</span>
                  </span>
                </div>
                <div class="ph-right">
                  <span v-if="deadlineMap[n]" :class="statusBadgeCls(n)">{{ statusBadgeText(n) }}</span>
                  <span :class="deadlineMap[n] ? 'ph-deadline deadline-red' : 'ph-deadline'">
                    {{ deadlineMap[n] ? formatDeadline(deadlineMap[n]) : 'Belum ada tugas' }}
                  </span>
                </div>
              </button>

              <div v-if="pertemuanTerbuka === n" class="pertemuan-body">
                <p v-if="materiMap[n]" class="materi-line"><span class="materi-label">Materi:</span> {{ materiMap[n] }}</p>
                <p class="deadline-line">
                  <span class="deadline-label">Batas Waktu Pengumpulan (Deadline):</span>
                  <span v-if="deadlineMap[n]" class="deadline-red">{{ formatDeadline(deadlineMap[n]) }} WIB</span>
                  <span v-else class="muted">Belum ada tugas untuk pertemuan ini.</span>
                </p>

                <template v-if="deadlineMap[n]">
                  <div v-if="tugasPerPertemuan[n]" class="tugas-saya-row">
                    <div>
                      <div class="ts-judul">{{ tugasPerPertemuan[n].judul }}</div>
                      <div v-if="kirimTelat(n)" class="telat-text small">⚠️ Tugas Anda terkirim TERLAMBAT (melewati batas waktu).</div>
                      <div v-else class="muted small">Tugas Anda sudah terkirim tepat waktu.</div>
                    </div>
                    <a :href="tugasPerPertemuan[n].tautan" target="_blank" rel="noopener" class="btn-buka">Buka</a>
                  </div>
                  <template v-else>
                    <p v-if="sudahLewatDeadline(deadlineMap[n])" class="late-warning">
                      ⚠️ Batas waktu sudah terlewati dan Anda belum mengumpulkan. Pengumpulan akan dihitung terlambat.
                    </p>
                    <div class="tugas-saya-row">
                      <span class="muted">Anda belum mengirim tugas untuk pertemuan ini.</span>
                      <router-link to="/tugas" class="btn btn-navy-solid btn-xs">Kirim Tugas</router-link>
                    </div>
                  </template>
                </template>
                <p v-else class="muted small">Tidak ada tugas yang perlu dikumpulkan untuk pertemuan ini.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== Tugas masuk untuk kelas ini (Dosen pengampu / Supervisor / Admin) ===== -->
        <div v-if="bisaLihatMasuk" class="card mt-20">
          <div class="flex-h between" style="align-items: center; flex-wrap: wrap; gap: 12px">
            <h3>Tugas Masuk di Kelas Ini</h3>
            <div class="flex-h" style="align-items: center; gap: 16px; flex-wrap: wrap">
              <div class="rekap-stats">
                <div class="stat"><span class="stat-num">{{ tugasKelas.length }}</span> tugas</div>
                <div class="stat"><span class="stat-num">{{ pesertaDisetujui.length }}</span> peserta</div>
              </div>
              <router-link :to="`/kelaslab/${kelasId}/peserta`" class="btn btn-navy-border btn-xs">
                Daftar Peserta Kelas
              </router-link>
            </div>
          </div>
          <p class="muted" style="margin-top: 4px">Klik sebuah pertemuan untuk menetapkan batas waktu (deadline) dan membuka detail pengumpulannya.</p>

          <p v-if="loadingTugas || loadingPeserta" class="mt-20 muted">Memuat data...</p>
          <!-- Grid 2 kolom berisi 16 pertemuan; tiap item bisa dibuka -->
          <div v-else class="pertemuan-grid mt-20">
            <div v-for="n in 16" :key="n" class="pertemuan-item" :class="{ open: pertemuanTerbuka === n }">
              <button type="button" class="pertemuan-head" @click="togglePertemuan(n)">
                <div class="ph-left">
                  <span class="ph-caret">{{ pertemuanTerbuka === n ? '▾' : '▸' }}</span>
                  <span>
                    <strong>Pertemuan {{ n }}</strong>
                    <span v-if="materiMap[n]" class="ph-materi">{{ materiMap[n] }}</span>
                  </span>
                </div>
                <div class="ph-right">
                  <span v-if="deadlineMap[n]" class="ph-count">{{ pengumpulPerPertemuan(n).length }}/{{ pesertaDisetujui.length }} kumpul</span>
                  <span :class="deadlineMap[n] ? 'ph-deadline deadline-red' : 'ph-deadline'">
                    {{ deadlineMap[n] ? formatDeadline(deadlineMap[n]) : 'Belum ada tugas' }}
                  </span>
                </div>
              </button>

              <div v-if="pertemuanTerbuka === n" class="pertemuan-body">
                <!-- Materi + Deadline + pengaturan (Dosen pengampu/Supervisor/Admin) -->
                <div class="deadline-atur">
                  <p v-if="materiMap[n]" class="materi-line"><span class="materi-label">Materi:</span> {{ materiMap[n] }}</p>
                  <p class="deadline-line">
                    <span class="deadline-label">Batas Waktu Pengumpulan (Deadline):</span>
                    <span v-if="deadlineMap[n]" class="deadline-red">{{ formatDeadline(deadlineMap[n]) }} WIB</span>
                    <span v-else class="muted">Belum ada tugas untuk pertemuan ini.</span>
                  </p>
                  <div class="deadline-form">
                    <input v-model="materiInput[n]" type="text" maxlength="255" placeholder="Nama materi (opsional)" class="form-ctrl input-border materi-input" />
                    <input v-model="deadlineInput[n]" type="datetime-local" class="form-ctrl input-border deadline-input" />
                    <button class="btn btn-navy-solid btn-xs" :disabled="savingDeadline === n" @click="simpanDeadline(n)">
                      {{ (deadlineMap[n] || materiMap[n]) ? 'Ubah' : 'Simpan' }}
                    </button>
                    <button v-if="deadlineMap[n] || materiMap[n]" class="btn-hapus-dl" :disabled="savingDeadline === n" @click="hapusDeadline(n)">Hapus</button>
                  </div>
                  <p class="form-hint-mini">Materi bisa diisi tanpa deadline (silabus). Deadline menandai adanya tugas.</p>
                </div>

                <router-link :to="`/kelaslab/${kelasId}/pertemuan/${n}`" class="btn btn-navy-border btn-xs" style="display: inline-block">
                  Lihat Detail Pertemuan &rarr;
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Halaman Detail Kelas Lab: info sesi + (untuk Mahasiswa) daftar tugas yang ia kirim untuk kelas ini.
// Endpoint show kelas-lab & tugas sudah ada; halaman ini murni penyusun tampilan.
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { kelasLabService } from '@/services/kelas-lab'
import { tugasService } from '@/services/tugas'
import { useFeedback } from '@/composables/use-feedback'
import { formatJam, hariLabel, statusLabel, formatTanggalId, formatDeadline, toDatetimeLocal, sudahLewatDeadline, dikirimTerlambat } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const route = useRoute()
const kelasId = Number(route.params.id)
const { notify, confirmDialog } = useFeedback()
const auth = useAuthStore()
const isMahasiswa = computed(() => auth.user?.role === 'mahasiswa')
// Dosen pengampu / Supervisor / Admin melihat tugas masuk kelas ini (list backend sudah tersaring per-role).
const bisaLihatMasuk = computed(() => ['dosen', 'supervisor', 'admin'].includes(auth.user?.role))

const kelas = ref(null)
const tugas = ref([])
const peserta = ref([])
const loading = ref(false)
const loadingTugas = ref(false)
const loadingPeserta = ref(false)
const loadError = ref('')
const pertemuanTerbuka = ref(null)
const deadlineData = ref([])
const deadlineInput = ref({}) // pertemuan → nilai input datetime-local
const materiInput = ref({}) // pertemuan → nilai input materi
const savingDeadline = ref(null)

// Peta pertemuan (1–16) → string deadline dari backend (null bila materi tanpa deadline).
const deadlineMap = computed(() => {
  const map = {}
  for (const d of deadlineData.value) map[d.pertemuan] = d.deadline
  return map
})

// Peta pertemuan (1–16) → nama materi (bisa ada tanpa deadline).
const materiMap = computed(() => {
  const map = {}
  for (const d of deadlineData.value) if (d.materi) map[d.pertemuan] = d.materi
  return map
})

// Status pendaftaran mahasiswa ini (jika ada) — di-append backend saat role mahasiswa.
const statusSaya = computed(() => kelas.value?.status_pendaftaran || null)

// Tugas milik mahasiswa yang tertaut ke kelas ini (list tugas sudah tersaring per-role).
const tugasKelas = computed(() => tugas.value.filter((t) => t.kelas_lab_id === kelasId))

// Peta pertemuan (1–16) → tugas, untuk mengisi baris tabel 16 pertemuan (tampilan Mahasiswa).
const tugasPerPertemuan = computed(() => {
  const map = {}
  for (const t of tugasKelas.value) map[t.pertemuan] = t
  return map
})

// Peserta kelas yang disetujui (yang wajib mengumpulkan) — untuk daftar per pertemuan.
const pesertaDisetujui = computed(() => peserta.value.filter((p) => p.status === 'disetujui'))

// Tugas yang dikirim untuk pertemuan tertentu (Dosen/Supervisor/Admin).
function pengumpulPerPertemuan(n) {
  return tugasKelas.value.filter((t) => t.pertemuan === n)
}

// Apakah tugas mahasiswa untuk pertemuan n dikirim setelah deadline.
function kirimTelat(n) {
  const t = tugasPerPertemuan.value[n]
  return !!t && dikirimTerlambat(t.created_at, deadlineMap.value[n])
}

// Badge status pengumpulan mahasiswa untuk pertemuan n (hanya bila ada deadline/tugas).
function statusBadgeText(n) {
  if (tugasPerPertemuan.value[n]) return kirimTelat(n) ? 'Sudah · Telat' : 'Sudah'
  return sudahLewatDeadline(deadlineMap.value[n]) ? 'Terlambat' : 'Belum'
}
function statusBadgeCls(n) {
  if (tugasPerPertemuan.value[n]) return kirimTelat(n) ? 'badge-telat' : 'badge-sudah'
  return sudahLewatDeadline(deadlineMap.value[n]) ? 'badge-terlambat' : 'badge-belum'
}

// Buka/tutup rincian sebuah pertemuan (accordion satu terbuka).
function togglePertemuan(n) {
  pertemuanTerbuka.value = pertemuanTerbuka.value === n ? null : n
}

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await kelasLabService.show(kelasId)
    kelas.value = res.data.data
  } catch (err) {
    loadError.value = err.response?.data?.message || 'Gagal memuat detail Kelas Lab.'
  } finally {
    loading.value = false
  }
}

async function loadTugas() {
  // Mahasiswa lihat tugasnya sendiri; Dosen/Supervisor/Admin lihat tugas masuk kelas.
  if (!isMahasiswa.value && !bisaLihatMasuk.value) return
  loadingTugas.value = true
  try {
    const res = await tugasService.list()
    tugas.value = res.data.data
  } catch {
    tugas.value = []
  } finally {
    loadingTugas.value = false
  }
}

// Daftar peserta kelas (untuk peninjau) — dipakai menampilkan status pengumpulan per pertemuan.
async function loadPeserta() {
  if (!bisaLihatMasuk.value) return
  loadingPeserta.value = true
  try {
    const res = await kelasLabService.peserta(kelasId)
    peserta.value = res.data.data
  } catch {
    peserta.value = []
  } finally {
    loadingPeserta.value = false
  }
}

// Deadline pertemuan (dilihat semua role; hanya peninjau yang bisa mengubah).
async function loadDeadline() {
  try {
    const res = await kelasLabService.deadlineList(kelasId)
    deadlineData.value = res.data.data
    // Prefill input dari data yang ada (materi & deadline).
    const dMap = {}
    const mMap = {}
    for (const d of deadlineData.value) {
      dMap[d.pertemuan] = toDatetimeLocal(d.deadline)
      mMap[d.pertemuan] = d.materi || ''
    }
    deadlineInput.value = dMap
    materiInput.value = mMap
  } catch {
    deadlineData.value = []
  }
}

async function simpanDeadline(n) {
  const nilai = deadlineInput.value[n]
  const materi = (materiInput.value[n] || '').trim()
  if (!nilai && !materi) {
    notify.warning('Isi nama materi dan/atau tanggal & jam deadline terlebih dahulu.')
    return
  }
  savingDeadline.value = n
  try {
    // datetime-local 'YYYY-MM-DDTHH:mm' → 'YYYY-MM-DD HH:mm:00' untuk backend.
    await kelasLabService.setDeadline(kelasId, n, {
      materi: materi || null,
      deadline: nilai ? nilai.replace('T', ' ') + ':00' : null,
    })
    await loadDeadline()
    notify.success(`Materi/deadline Pertemuan ${n} disimpan`)
  } catch (err) {
    notify.error(err.response?.data?.message || 'Gagal menyimpan materi/deadline.')
  } finally {
    savingDeadline.value = null
  }
}

async function hapusDeadline(n) {
  if (!(await confirmDialog(`Hapus materi & deadline Pertemuan ${n}?`))) return
  savingDeadline.value = n
  try {
    await kelasLabService.removeDeadline(kelasId, n)
    deadlineInput.value[n] = ''
    materiInput.value[n] = ''
    await loadDeadline()
    notify.success(`Materi/deadline Pertemuan ${n} dihapus`)
  } catch (err) {
    notify.error(err.response?.data?.message || 'Gagal menghapus materi/deadline.')
  } finally {
    savingDeadline.value = null
  }
}

onMounted(() => {
  load()
  loadTugas()
  loadPeserta()
  loadDeadline()
})
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
}
.meta-pill {
  padding: 5px 18px;
  border-radius: 20px;
  background-color: var(--bs-navy);
  color: #fff;
  font-weight: 600;
  font-size: 0.9em;
}
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px 24px;
}
.detail-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
  font-size: 0.95em;
  color: #3c4043;
}
.detail-label {
  font-size: 0.8em;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  font-weight: 600;
  color: var(--bs-navy);
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
.btn-buka {
  display: inline-block;
  padding: 5px 16px;
  border-radius: 6px;
  background-color: var(--bs-navy);
  color: #fff;
  font-size: 0.85em;
  font-weight: 600;
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
.small {
  font-size: 0.85em;
}
.format-note {
  margin-top: 12px;
  padding: 8px 12px;
  background-color: #eef1f7;
  border-radius: 6px;
  font-size: 0.85em;
  color: #5f6368;
}
.format-note strong {
  color: var(--bs-navy);
}

/* Grid 2 kolom berisi 16 pertemuan yang bisa dibuka (accordion) */
.pertemuan-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
@media (max-width: 720px) {
  .pertemuan-grid {
    grid-template-columns: 1fr;
  }
}
.pertemuan-item {
  border: 1px solid var(--bs-grey2, #e3e6ea);
  border-radius: 8px;
  overflow: hidden;
}
.pertemuan-item.open {
  border-color: var(--bs-navy);
}
.pertemuan-head {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 14px;
  background-color: #f7f8fa;
  border: none;
  cursor: pointer;
  text-align: left;
}
.pertemuan-item.open .pertemuan-head {
  background-color: #eef1f7;
}
.ph-left {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--bs-navy);
  font-size: 0.95em;
}
.ph-caret {
  color: #5f6368;
  font-size: 0.85em;
}
.ph-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}
.ph-count {
  font-size: 0.82em;
  font-weight: 700;
  color: #1e7e34;
}
.ph-deadline {
  font-size: 0.72em;
  color: #9aa0a6;
}
.pertemuan-body {
  padding: 12px 14px;
  border-top: 1px solid var(--bs-grey2, #e3e6ea);
}
.deadline-line {
  font-size: 0.85em;
  color: #5f6368;
  margin-bottom: 8px;
}
.deadline-label {
  font-weight: 700;
  color: #c0392b;
  margin-right: 4px;
}
/* Penekanan deadline (warna merah) */
.deadline-red {
  color: #c0392b;
  font-weight: 700;
}
.ph-deadline.deadline-red {
  font-weight: 700;
}
/* Blok pengaturan deadline (peninjau) */
.deadline-atur {
  padding: 10px 12px;
  background-color: #fdecec;
  border-radius: 6px;
  margin-bottom: 12px;
}
.deadline-form {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 8px;
}
.deadline-input {
  padding: 5px 8px;
  font-size: 0.85em;
}
.materi-input {
  padding: 5px 8px;
  font-size: 0.85em;
  min-width: 180px;
  flex: 1;
}
.form-hint-mini {
  margin-top: 6px;
  font-size: 0.78em;
  color: #8a6d3b;
}
/* Materi pertemuan */
.materi-line {
  margin-bottom: 6px;
  font-size: 0.9em;
  color: #3c4043;
}
.materi-label {
  font-weight: 700;
  color: var(--bs-navy);
  margin-right: 4px;
}
.ph-materi {
  display: block;
  font-size: 0.8em;
  font-weight: 500;
  color: #5f6368;
  margin-top: 2px;
}
.btn-xs {
  width: auto;
  padding: 5px 14px;
  font-size: 0.85em;
}
.btn-hapus-dl {
  padding: 5px 12px;
  border: 1px solid #e0b4b4;
  border-radius: 6px;
  background-color: #fff;
  color: #c0392b;
  cursor: pointer;
  font-size: 0.85em;
}
.btn-hapus-dl:hover {
  background-color: #fbeaea;
}
/* Baris tugas milik mahasiswa dalam accordion */
.tugas-saya-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}
.ts-judul {
  font-weight: 600;
  color: var(--bs-navy);
  font-size: 0.92em;
}
.badge-terlambat {
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.78em;
  font-weight: 700;
  color: #fff;
  background-color: #c0392b;
}
/* Sudah mengumpulkan tapi melewati batas waktu (amber) */
.badge-telat {
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.78em;
  font-weight: 700;
  color: #fff;
  background-color: #d98a00;
}
.telat-text {
  color: #c0392b;
  font-weight: 600;
}
.late-warning {
  margin-bottom: 10px;
  padding: 8px 12px;
  background-color: #fbeaea;
  border-left: 4px solid #c0392b;
  border-radius: 6px;
  font-size: 0.82em;
  color: #a5281b;
  font-weight: 600;
}
.peserta-ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.peserta-li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 6px 0;
  border-bottom: 1px solid #f0f2f4;
  font-size: 0.9em;
}
.peserta-li:last-child {
  border-bottom: none;
}
.pl-nama {
  min-width: 0;
}
.pl-status {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.badge-sudah {
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.78em;
  font-weight: 600;
  color: #1e7e34;
  background-color: #e6f4ea;
}
.badge-belum {
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.78em;
  font-weight: 600;
  color: #9aa0a6;
  background-color: #f0f2f4;
}
.btn-buka {
  white-space: nowrap;
}
</style>
