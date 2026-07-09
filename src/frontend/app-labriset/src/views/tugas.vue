<template>
  <div>
    <JumbotronSmall title="Pengumpulan Tugas" />

    <div class="main-container">
      <div class="flex-h" style="justify-content: flex-end; margin-bottom: 16px">
        <router-link to="/kelaslab" class="btn btn-navy-border" style="display: inline-block; width: auto; padding: 8px 20px">
          &larr; Kembali ke Kelas Lab
        </router-link>
      </div>

      <!-- ============ MAHASISWA: form kirim + daftar tugas sendiri ============ -->
      <template v-if="isMahasiswa">
        <div class="tugas-grid">
          <!-- Kolom form kirim tugas -->
          <div class="card">
            <h3>Kirim Tugas</h3>
            <p class="form-kategori">Kirim tautan tugas untuk Kelas Lab yang Anda ikuti.</p>

            <p v-if="loadingKelas" class="mt-20 muted">Memuat kelas...</p>
            <p v-else-if="!kelasDisetujui.length" class="empty-note mt-20">
              Anda belum menjadi peserta (disetujui) di Kelas Lab mana pun. Daftar &amp; tunggu persetujuan
              sebelum mengirim tugas.
            </p>

            <form v-else class="mt-20" @submit.prevent="kirim">
              <div class="mb-20">
                <label>Kelas Lab <span class="req">*</span></label>
                <select v-model="form.kelas_lab_id" class="form-ctrl input-border full" required>
                  <option value="" disabled>-- Pilih kelas --</option>
                  <option v-for="k in kelasDisetujui" :key="k.id" :value="k.id">
                    {{ k.mata_kuliah?.nama_mk }} — {{ k.nama_sesi }}
                  </option>
                </select>
              </div>

              <div class="mb-20">
                <label>Pertemuan <span class="req">*</span></label>
                <select v-model.number="form.pertemuan" class="form-ctrl input-border full" required>
                  <option value="" disabled>-- Pilih pertemuan --</option>
                  <option v-for="n in 16" :key="n" :value="n">Pertemuan {{ n }}</option>
                </select>
                <p v-if="form.pertemuan && materiTerpilih" class="materi-hint">
                  <strong>Materi:</strong> {{ materiTerpilih }}
                </p>
                <p v-if="form.pertemuan && deadlineTerpilih" :class="terlambatTerpilih ? 'deadline-hint deadline-hint-late' : 'deadline-hint'">
                  <span class="dl-icon">{{ terlambatTerpilih ? '⚠️' : '⏰' }}</span>
                  <span>
                    <strong>Batas Waktu Pengumpulan (Deadline):</strong><br />
                    {{ formatDeadline(deadlineTerpilih) }} WIB
                    <template v-if="terlambatTerpilih"><br /><strong>Sudah melewati batas waktu — pengumpulan akan dihitung TERLAMBAT.</strong></template>
                  </span>
                </p>
                <p v-else-if="form.pertemuan" class="deadline-hint-none">
                  Belum ada tugas/deadline yang ditetapkan dosen untuk pertemuan ini.
                </p>
              </div>

              <!-- Tautan tempat unggah dokumen dari dosen (muncul saat kelas dipilih) -->
              <div v-if="kelasTerpilih?.tautan_pengumpulan" class="upload-box mb-20">
                <a :href="kelasTerpilih.tautan_pengumpulan" target="_blank" rel="noopener" class="btn-upload">
                  Tempat unggah dokumen (PDF/DOCX)
                </a>
                <p class="upload-note">Unggah dokumen Anda di sini, lalu tempel tautan hasilnya di kolom Tautan.</p>
                <p class="upload-note upload-format">
                  <strong>Format penamaan file:</strong> NamaTugas_NPM_Nama
                </p>
              </div>

              <div class="mb-20">
                <label>Judul <span class="req">*</span></label>
                <input v-model="form.judul" type="text" maxlength="255" class="form-ctrl input-border full" placeholder="NamaTugas - NPM - Nama" required />
                <p class="field-hint">Ikuti format penamaan: <strong>NamaTugas - NPM - Nama</strong>.</p>
              </div>
              <div class="mb-20">
                <label>Tautan <span class="req">*</span></label>
                <input v-model="form.tautan" type="url" maxlength="2048" class="form-ctrl input-border full" placeholder="Isi dengan shortlink" required />
                <p class="field-hint">Gunakan Google Drive, GitHub, atau layanan sejenis untuk mengunggah projek. Disarankan memakai shortlink agar tautan ringkas.</p>
              </div>

              <p v-if="aksiError" class="msg-error">{{ aksiError }}</p>
              <p v-if="aksiSukses" class="msg-sukses">{{ aksiSukses }}</p>
              <button type="submit" class="btn btn-navy-solid full" :disabled="saving">
                {{ saving ? 'Mengirim...' : 'Kirim Tugas' }}
              </button>
            </form>
          </div>

          <!-- Kolom daftar tugas sendiri -->
          <div class="card">
            <h3>Tugas Saya</h3>
            <p v-if="loading" class="mt-20 muted">Memuat data...</p>
            <p v-else-if="!list.length" class="mt-20 muted">Belum ada tugas terkirim.</p>
            <table v-else class="tugas-table mt-20">
              <thead>
                <tr><th>Kelas</th><th style="width: 110px">Pertemuan</th><th>Judul</th><th></th></tr>
              </thead>
              <tbody>
                <tr v-for="t in list" :key="t.id">
                  <td>{{ t.kelas_lab?.mata_kuliah?.nama_mk }} — {{ t.kelas_lab?.nama_sesi }}</td>
                  <td>Pertemuan {{ t.pertemuan }}</td>
                  <td>{{ t.judul }}</td>
                  <td class="aksi-cell">
                    <a :href="t.tautan" target="_blank" rel="noopener" class="btn-buka">Buka</a>
                    <button class="btn-hapus" title="Hapus tugas" @click="hapus(t)">Hapus</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- ============ DOSEN / ADMIN / SUPERVISOR: tugas masuk ============ -->
      <template v-else>
        <div class="card">
          <div class="flex-h between" style="align-items: flex-start; flex-wrap: wrap; gap: 8px">
            <div>
              <h3>{{ isDosen ? 'Tugas Masuk (Kelas Anda)' : 'Tugas Masuk' }}</h3>
              <p class="muted" style="margin-top: 4px">
                {{ isDosen ? 'Tugas yang dikirim mahasiswa untuk kelas yang Anda ampu.' : 'Seluruh tugas yang dikirim mahasiswa.' }}
              </p>
            </div>
            <div class="rekap-stats">
              <div class="stat"><span class="stat-num">{{ list.length }}</span> tugas</div>
              <div class="stat"><span class="stat-num">{{ mahasiswaUnik }}</span> mahasiswa</div>
            </div>
          </div>

          <p v-if="loading" class="mt-20 muted">Memuat data...</p>
          <p v-else-if="!list.length" class="mt-20 muted">Belum ada tugas masuk.</p>
          <table v-else class="tugas-table mt-20">
            <thead>
              <tr><th>Mahasiswa</th><th>Kelas</th><th style="width: 110px">Pertemuan</th><th>Judul</th><th></th></tr>
              <tr class="filter-row">
                <th>
                  <div class="filter-pengaju">
                    <select v-model="filtersMasuk.mhsField" class="filter-select">
                      <option value="nama">Nama</option>
                      <option value="npm">NPM</option>
                    </select>
                    <input v-model="filtersMasuk.mhs" class="filter-input" :placeholder="filtersMasuk.mhsField === 'npm' ? 'Cari NPM' : 'Cari nama'" />
                  </div>
                </th>
                <th><input v-model="filtersMasuk.kelas" class="filter-input" placeholder="Cari kelas" /></th>
                <th></th>
                <th><input v-model="filtersMasuk.judul" class="filter-input" placeholder="Cari judul" /></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in filteredMasuk" :key="t.id">
                <td>
                  {{ t.mahasiswa?.user?.name }}
                  <span v-if="t.mahasiswa?.npm" class="muted"> · {{ t.mahasiswa.npm }}</span>
                </td>
                <td>{{ t.kelas_lab?.mata_kuliah?.nama_mk }} — {{ t.kelas_lab?.nama_sesi }}</td>
                <td>Pertemuan {{ t.pertemuan }}</td>
                <td>{{ t.judul }}</td>
                <td class="aksi-cell">
                  <a :href="t.tautan" target="_blank" rel="noopener" class="btn-buka">Buka</a>
                  <button v-if="bisaHapus" class="btn-hapus" title="Hapus tugas" @click="hapus(t)">Hapus</button>
                </td>
              </tr>
              <tr v-if="!filteredMasuk.length">
                <td colspan="5" style="text-align: center; color: #9aa0a6">Tidak ada tugas yang cocok dengan filter.</td>
              </tr>
            </tbody>
          </table>
          <p v-if="aksiError" class="msg-error mt-10">{{ aksiError }}</p>
        </div>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Pengumpulan Tugas (menggantikan modul Presensi). Halaman adaptif per-role:
// Mahasiswa kirim tautan tugas untuk kelas yang diikuti (disetujui) + daftar tugasnya;
// Dosen (kelas yang diampu) / Admin / Supervisor melihat & membuka tugas masuk.
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { tugasService } from '@/services/tugas'
import { kelasLabService } from '@/services/kelas-lab'
import { formatDeadline, sudahLewatDeadline } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const auth = useAuthStore()
const isMahasiswa = computed(() => auth.user?.role === 'mahasiswa')
const isDosen = computed(() => auth.user?.role === 'dosen')
const bisaHapus = computed(() => ['admin', 'supervisor'].includes(auth.user?.role))

const list = ref([])
const kelas = ref([])
const loading = ref(false)
const loadingKelas = ref(false)
const saving = ref(false)
const aksiError = ref('')
const aksiSukses = ref('')
const form = ref({ kelas_lab_id: '', pertemuan: '', judul: '', tautan: '' })

// Kelas yang bisa jadi tujuan tugas: peserta disetujui.
const kelasDisetujui = computed(() => kelas.value.filter((k) => k.status_pendaftaran === 'disetujui'))

// Kelas yang sedang dipilih di form (untuk menampilkan tautan pengumpulan dari dosen).
const kelasTerpilih = computed(() => kelasDisetujui.value.find((k) => k.id === form.value.kelas_lab_id) || null)

// Deadline pertemuan untuk kelas yang dipilih (dimuat dari backend saat kelas berubah).
const deadlineKelas = ref([]) // [{ pertemuan, deadline }]
const deadlineTerpilih = computed(() => {
  if (!form.value.pertemuan) return null
  return (deadlineKelas.value.find((d) => d.pertemuan === form.value.pertemuan) || {}).deadline || null
})

// Nama materi pertemuan terpilih (bila dosen sudah mengisinya).
const materiTerpilih = computed(() => {
  if (!form.value.pertemuan) return null
  return (deadlineKelas.value.find((d) => d.pertemuan === form.value.pertemuan) || {}).materi || null
})

// Apakah deadline pertemuan terpilih sudah terlewati (untuk peringatan "terlambat").
const terlambatTerpilih = computed(() => sudahLewatDeadline(deadlineTerpilih.value))

// Muat deadline saat kelas dipilih (biar mahasiswa lihat batas waktu per pertemuan).
watch(() => form.value.kelas_lab_id, async (id) => {
  deadlineKelas.value = []
  if (!id) return
  try {
    const res = await kelasLabService.deadlineList(id)
    deadlineKelas.value = res.data.data
  } catch {
    deadlineKelas.value = []
  }
})

// Jumlah mahasiswa unik di daftar tugas masuk (statistik Dosen/Admin/Supervisor).
const mahasiswaUnik = computed(() => new Set(list.value.map((t) => t.mahasiswa_id)).size)

// Filter tabel "Tugas Masuk" (Dosen/Admin/Supervisor) — pola sama seperti persetujuan.
const filtersMasuk = ref({ mhsField: 'nama', mhs: '', kelas: '', judul: '' })
const cocok = (val, q) => !q || String(val ?? '').toLowerCase().includes(q.toLowerCase())
const filteredMasuk = computed(() => {
  const f = filtersMasuk.value
  return list.value.filter((t) => {
    const mhsVal = f.mhsField === 'npm' ? t.mahasiswa?.npm : t.mahasiswa?.user?.name
    return (
      cocok(mhsVal, f.mhs) &&
      cocok(`${t.kelas_lab?.mata_kuliah?.nama_mk ?? ''} ${t.kelas_lab?.nama_sesi ?? ''}`, f.kelas) &&
      cocok(t.judul, f.judul)
    )
  })
})

async function load() {
  loading.value = true
  try {
    const res = await tugasService.list()
    list.value = res.data.data
  } catch {
    list.value = []
  } finally {
    loading.value = false
  }
}

async function loadKelas() {
  loadingKelas.value = true
  try {
    const res = await kelasLabService.list()
    kelas.value = res.data.data
  } catch {
    kelas.value = []
  } finally {
    loadingKelas.value = false
  }
}

async function kirim() {
  aksiError.value = ''
  aksiSukses.value = ''
  saving.value = true
  try {
    await tugasService.create({ ...form.value })
    aksiSukses.value = 'Tugas berhasil dikirim.'
    form.value = { kelas_lab_id: '', pertemuan: '', judul: '', tautan: '' }
    await load()
  } catch (err) {
    aksiError.value = extractError(err)
  } finally {
    saving.value = false
  }
}

async function hapus(t) {
  if (!window.confirm(`Hapus tugas "${t.judul}"?`)) return
  aksiError.value = ''
  try {
    await tugasService.remove(t.id)
    await load()
  } catch (err) {
    aksiError.value = extractError(err)
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
  load()
  if (isMahasiswa.value) loadKelas()
})
</script>

<style scoped>
.tugas-grid {
  display: grid;
  grid-template-columns: 1fr 1.4fr;
  gap: 24px;
  align-items: start;
}
@media (max-width: 860px) {
  .tugas-grid {
    grid-template-columns: 1fr;
  }
}

.card {
  background-color: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 0.1);
}

.full {
  width: 100%;
}
.btn-navy-solid.full {
  padding: 11px;
}

.muted {
  color: #9aa0a6;
}
.form-kategori {
  margin-top: 4px;
  font-size: 0.88em;
  color: #5f6368;
}
.req {
  color: #c0392b;
}
.msg-error {
  color: #c0392b;
  margin-top: 12px;
}
.msg-sukses {
  color: #1e7e34;
  margin-top: 12px;
}
.empty-note {
  padding: 14px 16px;
  background-color: #eef1f7;
  border-radius: 8px;
  color: #5f6368;
  font-size: 0.9em;
  line-height: 1.5;
}
.field-hint {
  margin-top: 6px;
  font-size: 0.82em;
  color: #9aa0a6;
  line-height: 1.5;
}
.deadline-hint {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 8px;
  padding: 10px 12px;
  background-color: #fdecec;
  border-left: 4px solid #c0392b;
  border-radius: 6px;
  font-size: 0.85em;
  color: #c0392b;
  line-height: 1.5;
}
.deadline-hint strong {
  color: #c0392b;
}
.dl-icon {
  flex-shrink: 0;
}
/* Peringatan lebih tegas saat sudah melewati deadline */
.deadline-hint-late {
  background-color: #fbeaea;
  border-left-color: #a5281b;
  color: #a5281b;
}
.deadline-hint-none {
  margin-top: 8px;
  padding: 8px 12px;
  background-color: #f0f2f4;
  border-radius: 6px;
  font-size: 0.82em;
  color: #9aa0a6;
}
.materi-hint {
  margin-top: 8px;
  padding: 8px 12px;
  background-color: #eef1f7;
  border-left: 4px solid var(--bs-navy);
  border-radius: 6px;
  font-size: 0.85em;
  color: #3c4043;
}
.materi-hint strong {
  color: var(--bs-navy);
}

/* Kotak tautan tempat unggah dokumen (dari dosen) */
.upload-box {
  padding: 14px 16px;
  background-color: #eef1f7;
  border-radius: 8px;
  border-left: 4px solid var(--bs-navy);
}
.btn-upload {
  display: inline-block;
  padding: 7px 16px;
  border-radius: 6px;
  background-color: var(--bs-navy);
  color: #fff;
  font-size: 0.88em;
  font-weight: 600;
  text-decoration: none;
}
.btn-upload:hover {
  opacity: 0.9;
}
.upload-note {
  margin-top: 8px;
  font-size: 0.82em;
  color: #5f6368;
}
.upload-format {
  padding: 6px 10px;
  background-color: #fff;
  border-radius: 6px;
  line-height: 1.6;
}
.upload-format strong {
  color: var(--bs-navy);
}

/* Tabel tugas */
.tugas-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.92em;
}
.tugas-table th,
.tugas-table td {
  text-align: left;
  padding: 10px 12px;
  border-bottom: 1px solid var(--bs-grey2, #e3e6ea);
}
.tugas-table th {
  color: #5f6368;
  font-weight: 600;
  font-size: 0.9em;
}

/* Baris filter pencarian per kolom (Tugas Masuk) */
.filter-row th {
  padding: 6px 12px;
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
.aksi-cell {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-end;
  white-space: nowrap;
}

/* Statistik */
.rekap-stats {
  display: flex;
  gap: 20px;
}
.stat {
  color: #5f6368;
  font-size: 0.9em;
}
.stat-num {
  font-size: 1.5em;
  font-weight: 700;
  color: var(--bs-navy);
  margin-right: 4px;
}

.btn-buka {
  padding: 4px 14px;
  border: 1px solid var(--bs-navy);
  border-radius: 6px;
  background-color: var(--bs-navy);
  color: #fff;
  font-size: 0.85em;
  text-decoration: none;
}
.btn-buka:hover {
  opacity: 0.9;
}
.btn-hapus {
  padding: 4px 12px;
  border: 1px solid #e0b4b4;
  border-radius: 6px;
  background-color: #fff;
  color: #c0392b;
  cursor: pointer;
  font-size: 0.85em;
}
.btn-hapus:hover {
  background-color: #fbeaea;
}
</style>
