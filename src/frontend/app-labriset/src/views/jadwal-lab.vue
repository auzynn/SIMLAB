<template>
  <div>
    <JumbotronSmall title="Jadwal & Peminjaman Lab" />

    <div class="main-container">
      <div class="jadwal-grid">
        <!-- ===== Ketersediaan (semua role) — kolom informasi, lebih lebar ===== -->
        <div class="card schedule-card">
          <div class="flex-h between" style="align-items: flex-start; flex-wrap: wrap; gap: 8px">
            <div>
              <h3>Informasi Jadwal Lab</h3>
              <p class="schedule-sub">Jadwal aktif minggu ini</p>
            </div>
            <p class="schedule-legend">
              <span class="legend legend-kelas"></span> Kelas Lab/Praktikum
              <span class="legend legend-pinjam" style="margin-left: 14px"></span> Peminjaman disetujui
            </p>
          </div>

          <p v-if="loading" class="mt-20">Memuat data...</p>
          <template v-else>
            <p v-if="!kelasLab.length && !peminjaman.length" class="mt-20" style="color: #9aa0a6">
              Belum ada jadwal terisi. Ruangan tersedia untuk dipinjam.
            </p>

            <!-- Seksi 1: Kelas Lab/Praktikum (rutin mingguan) — grid 2 kolom -->
            <section v-if="kelasLab.length" class="schedule-section mt-20">
              <div class="section-title section-kelas">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="17" rx="2" /><path d="M3 9h18M8 2v4M16 2v4" />
                </svg>
                Kelas Lab / Praktikum
              </div>
              <div class="slot-grid mt-10">
                <div v-for="k in kelasLab" :key="'k' + k.id" class="slot slot-kelas">
                  <div class="slot-head">
                    <strong>{{ hariLabel(k.hari) }}</strong>
                    <span class="slot-time">{{ formatJam(k.jam_mulai) }}–{{ formatJam(k.jam_selesai) }}</span>
                  </div>
                  <div class="slot-room slot-room-kelas">{{ k.mata_kuliah?.nama_mk }} — {{ k.nama_sesi }}</div>
                  <div class="slot-sub">{{ k.ruangan?.nama_ruangan }}</div>
                </div>
              </div>
            </section>

            <!-- Seksi 2: Peminjaman disetujui (tanggal spesifik) — dikelompokkan per minggu -->
            <section v-if="peminjaman.length" class="schedule-section mt-20">
              <div class="section-title section-pinjam">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
                </svg>
                Peminjaman disetujui
              </div>
              <p class="auto-note">
                <InfoIcon /> Daftar ini diperbarui otomatis setiap minggu — peminjaman minggu lalu hilang sendiri setiap Minggu pukul 23.59.
              </p>

              <template v-for="(grup, gi) in peminjamanGrup" :key="grup.key">
                <div v-if="gi > 0" class="week-divider"></div>
                <p class="week-label">{{ grup.label }}</p>
                <div class="mt-10">
                  <div v-for="p in grup.items" :key="'p' + p.id" class="slot slot-pinjam pinjam-row">
                    <div class="pinjam-icon">
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 4-6 8-6s8 2 8 6" />
                      </svg>
                    </div>
                    <div class="pinjam-body">
                      <div class="slot-head">
                        <strong>{{ namaHari(p.tanggal) }}, {{ formatTanggalId(p.tanggal) }}</strong>
                        <span class="slot-time">{{ formatJam(p.jam_mulai) }}–{{ formatJam(p.jam_selesai) }}</span>
                      </div>
                      <div class="slot-room slot-room-pinjam">{{ p.ruangan?.nama_ruangan }}</div>
                      <div class="slot-sub">
                        {{ p.user?.name }}<span v-if="p.user?.mahasiswa?.npm"> · {{ p.user.mahasiswa.npm }}</span>
                      </div>
                    </div>
                    <span class="badge-disetujui">Disetujui</span>
                  </div>
                </div>
              </template>
            </section>
          </template>
        </div>

        <!-- ===== Kolom aksi: form/persetujuan sesuai role + entri Perangkat (semua role login) ===== -->
        <div class="action-col">
        <!-- ===== Form Pengajuan (Mahasiswa saja) ===== -->
        <div v-if="bisaMengajukan" class="card form-card">
          <h3>Formulir Peminjaman Ruangan</h3>
          <p class="form-kategori">Untuk meminjam ruangan/lab.</p>
          <form class="mt-20" @submit.prevent="submit">
            <div class="mb-20">
              <label>Ruangan <span class="req">*</span></label>
              <select v-model="form.ruangan_id" class="form-ctrl input-border" style="width: 100%" required>
                <option value="" disabled>-- Pilih ruangan --</option>
                <option v-for="r in ruanganTersedia" :key="r.id" :value="r.id">
                  {{ r.nama_ruangan }}<span v-if="r.status !== 'tersedia'"> ({{ r.status }})</span>
                </option>
              </select>
            </div>

            <!-- Durasi: satu hari atau beberapa hari -->
            <div class="mb-20">
              <label>Durasi peminjaman <span class="req">*</span></label>
              <div class="toggle-group">
                <button type="button" :class="['toggle-btn', { active: form.mode === 'satu' }]" @click="form.mode = 'satu'">
                  <CalendarIcon /> Satu hari
                </button>
                <button type="button" :class="['toggle-btn', { active: form.mode === 'beberapa' }]" @click="form.mode = 'beberapa'">
                  <CalendarIcon /> Beberapa hari
                </button>
              </div>
            </div>

            <!-- Mode: satu hari -->
            <div v-if="form.mode === 'satu'" class="mb-20">
              <label>Tanggal <span class="req">*</span></label>
              <input v-model="form.tanggal" type="date" :min="hariIni" class="form-ctrl input-border" style="width: 100%" />
            </div>

            <!-- Mode: beberapa hari -->
            <template v-else>
              <div class="mb-20">
                <label>Hari yang dibutuhkan <span class="req">*</span></label>
                <div class="hari-grid">
                  <label v-for="h in HARI" :key="h" class="hari-check" :class="{ checked: form.hari.includes(h) }">
                    <input type="checkbox" :value="h" v-model="form.hari" />
                    {{ hariLabel(h) }}
                  </label>
                </div>
                <p v-if="form.hari.length === 1" class="form-warning">
                  Hanya satu hari dipilih — silakan gunakan durasi <strong>"Satu hari"</strong>, atau pilih hari lainnya.
                </p>
                <p v-else class="form-hint">Pilih hari-hari yang akan digunakan (minimal dua).</p>
              </div>

              <div v-if="hariTerpilih.length >= 2" class="mb-20">
                <label>Tanggal per hari <span class="req">*</span></label>
                <div v-for="h in hariTerpilih" :key="h" class="tanggal-hari-row">
                  <span class="tanggal-hari-label">{{ hariLabel(h) }}</span>
                  <input v-model="form.dayDates[h]" type="date" :min="hariIni" class="form-ctrl input-border" style="flex: 1" />
                </div>
                <p class="form-hint">Tentukan tanggal untuk masing-masing hari yang dipilih.</p>
              </div>
            </template>

            <div class="flex-h" style="gap: 12px">
              <div class="mb-20" style="flex: 1">
                <label>Jam Mulai <span class="req">*</span></label>
                <input v-model="form.jam_mulai" type="time" min="07:00" max="17:00" class="form-ctrl input-border" style="width: 100%" required />
              </div>
              <div class="mb-20" style="flex: 1">
                <label>Jam Selesai <span class="req">*</span></label>
                <input v-model="form.jam_selesai" type="time" min="07:00" max="17:00" class="form-ctrl input-border" style="width: 100%" required />
              </div>
            </div>
            <p class="form-note">
              <InfoIcon /> Jam operasional lab: 07.00–17.00 WIB.<span v-if="form.mode === 'beberapa'"> Jam berlaku sama untuk semua hari yang dipilih.</span>
            </p>

            <div class="mb-20">
              <label>Keperluan <span class="req">*</span></label>
              <textarea
                ref="keperluanEl"
                v-model="form.keperluan"
                rows="2"
                class="form-ctrl input-border autosize"
                style="width: 100%"
                required
                placeholder="Jelaskan keperluan penggunaan ruangan..."
                @input="autoResize"
              ></textarea>
            </div>

            <p v-if="formError" style="color: #c0392b">{{ formError }}</p>
            <p v-if="formSukses" style="color: #1e7e34">{{ formSukses }}</p>

            <button type="submit" class="btn btn-navy-solid mt-20" style="width: 100%; padding: 11px" :disabled="saving">
              {{ saving ? 'Mengirim...' : 'Kirim Pengajuan' }}
            </button>
          </form>
        </div>

        <!-- ===== Persetujuan (Admin/Supervisor) — satu kartu terpadu ruangan + perangkat ===== -->
        <div v-else-if="bisaApprove" class="card form-card approval-card">
          <h3>Persetujuan Peminjaman</h3>
          <p class="mt-10" style="color: #5f6368">
            Tinjau dan setujui/tolak pengajuan peminjaman ruangan & perangkat serta perpanjangan.
          </p>

          <div class="approval-count mt-30">
            <span class="approval-num">{{ menungguCount + menungguPerangkatCount }}</span>
            pengajuan menunggu persetujuan
          </div>
          <p class="approval-breakdown">
            Ruangan: <strong>{{ menungguCount }}</strong> · Perangkat: <strong>{{ menungguPerangkatCount }}</strong>
          </p>

          <router-link to="/persetujuan-peminjaman" class="btn btn-navy-solid" style="width: 100%; padding: 10px; text-align: center; margin-top: 20px">
            Tinjau Pengajuan
          </router-link>
        </div>

        <!-- ===== Perangkat Lab — entri untuk semua role login (menggantikan menu navbar) ===== -->
        <div class="card perangkat-entry-card">
          <h3>Perangkat Lab</h3>
          <p class="mt-10" style="color: #5f6368">
            Inventaris perangkat lab (PC, Router, Switch, IoT Kit, dll). Lihat ketersediaan & ajukan peminjaman.
          </p>
          <router-link to="/perangkat" class="btn btn-navy-solid" style="width: 100%; padding: 10px; text-align: center; margin-top: 20px">
            Lihat &amp; Pinjam Perangkat
          </router-link>
        </div>
        </div>
      </div>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Jadwal ketersediaan ruangan (kelas_lab + peminjaman disetujui) + form pengajuan peminjaman.
// Pengajuan peminjaman hanya untuk Mahasiswa (Dosen tidak meminjam ruangan — SRS UC-02).
import { ref, computed, onMounted, nextTick, h } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { peminjamanRuanganService } from '@/services/peminjaman-ruangan'
import { peminjamanPerangkatService } from '@/services/peminjaman-perangkat'
import { ruanganService } from '@/services/ruangan'
import { formatTanggalId, formatJam, hariLabel, namaHari } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const auth = useAuthStore()
const bisaMengajukan = computed(() => auth.user?.role === 'mahasiswa')
const bisaApprove = computed(() => ['admin', 'supervisor'].includes(auth.user?.role))
const hariIni = new Date().toISOString().slice(0, 10)

const menungguCount = ref(0)
const menungguPerangkatCount = ref(0)

const loading = ref(false)
const peminjaman = ref([])
const kelasLab = ref([])

// Akhir minggu berjalan (Minggu, ISO) — patokan memisah "minggu ini" vs "mendatang".
const akhirMingguIni = (() => {
  const d = new Date()
  const day = d.getDay() // 0=Minggu..6=Sabtu
  const sun = new Date(d.getFullYear(), d.getMonth(), d.getDate() + (day === 0 ? 0 : 7 - day))
  const m = String(sun.getMonth() + 1).padStart(2, '0')
  const dd = String(sun.getDate()).padStart(2, '0')
  return `${sun.getFullYear()}-${m}-${dd}`
})()
const tglStr = (t) => String(t).slice(0, 10)

// Peminjaman dikelompokkan: minggu ini (s/d Minggu) vs minggu mendatang.
const peminjamanGrup = computed(() => {
  const grup = []
  const ini = peminjaman.value.filter((p) => tglStr(p.tanggal) <= akhirMingguIni)
  const depan = peminjaman.value.filter((p) => tglStr(p.tanggal) > akhirMingguIni)
  if (ini.length) grup.push({ key: 'ini', label: 'Minggu ini', items: ini })
  if (depan.length) grup.push({ key: 'depan', label: 'Minggu mendatang', items: depan })
  return grup
})
const ruangan = ref([])
const ruanganTersedia = computed(() => ruangan.value)

const HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']

// Ikon kecil inline (tanpa dependency) untuk toggle durasi & catatan jam.
const CalendarIcon = () =>
  h('svg', { viewBox: '0 0 24 24', width: 14, height: 14, fill: 'none', stroke: 'currentColor', 'stroke-width': 2, style: 'vertical-align:-2px' }, [
    h('rect', { x: 3, y: 4, width: 18, height: 17, rx: 2 }),
    h('path', { d: 'M3 9h18M8 2v4M16 2v4' }),
  ])
const InfoIcon = () =>
  h('svg', { viewBox: '0 0 24 24', width: 14, height: 14, fill: 'none', stroke: 'currentColor', 'stroke-width': 2, style: 'vertical-align:-2px' }, [
    h('circle', { cx: 12, cy: 12, r: 9 }),
    h('path', { d: 'M12 11v5M12 8h.01' }),
  ])

const blankForm = () => ({ ruangan_id: '', mode: 'satu', tanggal: '', hari: [], dayDates: {}, jam_mulai: '', jam_selesai: '', keperluan: '' })
const form = ref(blankForm())
const saving = ref(false)
const formError = ref('')
const formSukses = ref('')
const keperluanEl = ref(null)

// Hari terpilih, selalu urut Senin–Sabtu meski dicentang acak.
const hariTerpilih = computed(() => HARI.filter((h2) => form.value.hari.includes(h2)))

// Daftar tanggal final yang akan dikirim (satu hari → 1 tanggal; beberapa hari → tanggal per hari).
function tanggalUntukKirim() {
  if (form.value.mode === 'satu') {
    return form.value.tanggal ? [form.value.tanggal] : []
  }
  return hariTerpilih.value.map((h2) => form.value.dayDates[h2]).filter(Boolean)
}

// Auto-resize textarea Keperluan: tinggi mengikuti isi tanpa scroll.
function autoResize() {
  const el = keperluanEl.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = `${el.scrollHeight}px`
}

async function loadKalender() {
  loading.value = true
  try {
    const res = await peminjamanRuanganService.kalender()
    peminjaman.value = res.data.data.peminjaman
    kelasLab.value = res.data.data.kelas_lab
  } finally {
    loading.value = false
  }
}

async function loadRuangan() {
  try {
    const res = await ruanganService.list()
    ruangan.value = res.data.data
  } catch {
    ruangan.value = []
  }
}

// Hitung pengajuan yang masih menunggu (untuk kartu Persetujuan Admin/Supervisor)
async function loadMenunggu() {
  try {
    const res = await peminjamanRuanganService.list()
    menungguCount.value = res.data.data.filter((p) => p.status === 'menunggu').length
  } catch {
    menungguCount.value = 0
  }
}

// Hitung pengajuan perangkat menunggu: peminjaman + perpanjangan (untuk kartu Persetujuan Perangkat)
async function loadMenungguPerangkat() {
  try {
    const res = await peminjamanPerangkatService.list()
    const data = res.data.data
    const pinjam = data.filter((p) => p.status === 'menunggu').length
    const perpanjang = data.flatMap((p) => p.perpanjangan ?? []).filter((x) => x.status === 'menunggu').length
    menungguPerangkatCount.value = pinjam + perpanjang
  } catch {
    menungguPerangkatCount.value = 0
  }
}

async function submit() {
  formError.value = ''
  formSukses.value = ''

  // Mode "beberapa hari" wajib minimal dua hari (satu hari pakai mode "Satu hari").
  if (form.value.mode === 'beberapa' && hariTerpilih.value.length < 2) {
    formError.value = 'Pilih minimal dua hari untuk durasi "Beberapa hari", atau gunakan "Satu hari".'
    return
  }

  const tanggalList = tanggalUntukKirim()
  if (!tanggalList.length) {
    formError.value =
      form.value.mode === 'satu'
        ? 'Silakan pilih tanggal peminjaman.'
        : 'Tentukan tanggal untuk setiap hari yang dipilih.'
    return
  }

  saving.value = true
  try {
    const { ruangan_id, jam_mulai, jam_selesai, keperluan } = form.value
    // Satu pengajuan per tanggal; tiap tanggal dilaporkan sukses/gagal beserta alasannya (mis. bentrok).
    // ponytail: saat modul Notifikasi (Fase 9) aktif, alasan bentrok juga dikirim sebagai notifikasi ke pengaju.
    const hasil = await Promise.all(
      tanggalList.map(async (tanggal) => {
        try {
          await peminjamanRuanganService.create({ ruangan_id, tanggal, jam_mulai, jam_selesai, keperluan })
          return { tanggal, ok: true }
        } catch (err) {
          return { tanggal, ok: false, msg: extractError(err) }
        }
      }),
    )
    const berhasil = hasil.filter((r) => r.ok)
    const gagal = hasil.filter((r) => !r.ok)
    const rincianGagal = gagal.map((g) => `${formatTanggalId(g.tanggal)} — ${g.msg}`).join('; ')

    if (berhasil.length > 0) {
      formSukses.value = `${berhasil.length} pengajuan terkirim, menunggu persetujuan.`
      formError.value = gagal.length ? `${gagal.length} gagal: ${rincianGagal}` : ''
      form.value = blankForm()
      await nextTick()
      autoResize()
      await loadKalender()
    } else {
      formError.value = rincianGagal || 'Semua pengajuan gagal.'
    }
  } finally {
    saving.value = false
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
  loadKalender()
  if (bisaMengajukan.value) loadRuangan()
  if (bisaApprove.value) {
    loadMenunggu()
    loadMenungguPerangkat()
  }
})
</script>

<style scoped>
/* Dua kolom: informasi ketersediaan lebih lebar dari kolom form */
.jadwal-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 24px;
  align-items: start;
}

/* Satu kolom di layar sempit */
@media (max-width: 860px) {
  .jadwal-grid {
    grid-template-columns: 1fr;
  }
}

.card {
  background-color: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 0.1);
}

/* Kolom aksi: konten per-role + kartu Perangkat bertumpuk vertikal */
.action-col,
.approval-col {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.approval-count {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  background-color: #eef1f7;
  border-radius: 8px;
  color: #5f6368;
  font-size: 0.95em;
}
.approval-num {
  font-size: 1.6em;
  font-weight: 700;
  line-height: 1;
  color: var(--bs-navy);
}
.approval-breakdown {
  margin-top: 10px;
  font-size: 0.9em;
  color: #5f6368;
}

.schedule-sub {
  color: #9aa0a6;
  font-size: 0.9em;
  margin-top: 2px;
  margin-bottom: 8px;
}
.schedule-legend {
  color: #5f6368;
  font-size: 0.85em;
  margin-top: 4px;
}
.legend {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 3px;
  vertical-align: middle;
}
.legend-kelas {
  background-color: var(--bs-navy);
}
.legend-pinjam {
  background-color: #ed8b00;
}

/* Jarak antar seksi: beri ruang lega + garis pemisah halus */
.schedule-section + .schedule-section {
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px solid var(--bs-grey2, #e3e6ea);
}

/* Judul seksi (per tipe) dengan ikon — beri sedikit jarak ke kartu di bawahnya */
.section-title {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 14px;
  font-size: 0.9em;
  font-weight: 600;
  color: #5f6368;
}
.section-kelas {
  color: var(--bs-navy);
}
.section-pinjam {
  color: #c47408;
}

/* Catatan auto-update mingguan */
.auto-note {
  display: flex;
  align-items: center;
  gap: 7px;
  margin: 10px 0 4px;
  padding: 8px 12px;
  background-color: #fff6e9;
  border-radius: 6px;
  font-size: 0.82em;
  color: #c47408;
}
/* Label & pembatas antar kelompok minggu */
.week-label {
  margin-top: 16px;
  margin-bottom: 8px;
  font-size: 0.85em;
  font-weight: 700;
  color: #5f6368;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.week-divider {
  margin-top: 18px;
  border-top: 1px dashed var(--bs-grey2, #e3e6ea);
}

/* Grid kartu Kelas Lab: mengalir 2-up (atau lebih) sesuai lebar */
.slot-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}
.slot {
  padding: 12px 14px;
  border-radius: 8px;
  border-left: 5px solid;
}
.slot-kelas {
  background-color: #eef1f7;
  border-left-color: var(--bs-navy);
}
.slot-pinjam {
  background-color: #fff6e9;
  border-left-color: #ed8b00;
}
.slot-head {
  display: flex;
  align-items: baseline;
  gap: 8px;
  flex-wrap: wrap;
}
.slot-time {
  font-size: 0.9em;
  color: #5f6368;
}
.slot-room {
  margin-top: 6px;
  font-weight: 600;
  font-size: 0.95em;
}
.slot-room-kelas {
  color: var(--bs-navy);
}
.slot-room-pinjam {
  color: #c47408;
}
.slot-sub {
  font-size: 0.88em;
  color: #5f6368;
}

/* Baris peminjaman: ikon orang + isi + badge status */
.pinjam-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 8px;
}
.pinjam-icon {
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background-color: #ed8b00;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pinjam-body {
  flex: 1;
  min-width: 0;
}
.badge-disetujui {
  align-self: center;
  flex-shrink: 0;
  padding: 3px 14px;
  border-radius: 20px;
  font-size: 0.78em;
  font-weight: 600;
  color: #c47408;
  background-color: #fff;
  border: 1px solid #ed8b00;
}

/* Textarea auto-resize: tanpa scrollbar, tumbuh mengikuti isi */
.autosize {
  resize: none;
  overflow: hidden;
  min-height: 64px;
}

/* Penanda field wajib */
.req {
  color: #c0392b;
}

/* Toggle durasi: Satu hari / Beberapa hari */
.toggle-group {
  display: flex;
  gap: 10px;
}
.toggle-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 12px;
  border: 1px solid var(--bs-grey2, #e3e6ea);
  border-radius: 8px;
  background-color: white;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9em;
  color: #5f6368;
}
.toggle-btn.active {
  border-color: var(--bs-navy);
  background-color: var(--bs-navy);
  color: #fff;
}

/* Pilihan hari (checkbox) untuk peminjaman beberapa hari */
.hari-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.hari-check {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border: 1px solid var(--bs-grey2, #e3e6ea);
  border-radius: 20px;
  cursor: pointer;
  font-size: 0.9em;
  user-select: none;
}
.hari-check.checked {
  border-color: var(--bs-navy);
  background-color: #eef1f7;
  color: var(--bs-navy);
  font-weight: 600;
}
.hari-check input {
  cursor: pointer;
}

/* Tanggal per hari (mode beberapa hari) */
.tanggal-hari-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}
.tanggal-hari-label {
  flex-shrink: 0;
  width: 72px;
  padding: 4px 0;
  font-weight: 600;
  color: var(--bs-navy);
  font-size: 0.9em;
}

.form-kategori {
  margin-top: 4px;
  font-size: 0.88em;
  color: #5f6368;
}
.form-hint {
  margin-top: 6px;
  font-size: 0.82em;
  color: #9aa0a6;
}
.form-warning {
  margin-top: 8px;
  padding: 8px 12px;
  background-color: #fff3cd;
  border-radius: 6px;
  font-size: 0.85em;
  color: #856404;
}
.form-note {
  display: flex;
  align-items: center;
  gap: 7px;
  margin: -6px 0 16px;
  padding: 8px 12px;
  background-color: #eef1f7;
  border-radius: 6px;
  font-size: 0.85em;
  color: #5f6368;
}
</style>
