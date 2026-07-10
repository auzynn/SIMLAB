<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall title="Konten Info Lab" />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container flex-h between">
      <!-- ---------- SIDE MENU ---------- -->
      <SidemenuAdmin />
      <!-- ---------- SIDE MENU END ---------- -->

      <div class="profil-container">
        <div>
          <h1>Konten Informasi Lab</h1>
          <div class="profil-title"></div>
        </div>

        <p class="mt-30" style="max-width: 600px">
          Pilih halaman yang ingin disunting, lalu format konten dengan editor visual (tebal, judul, daftar, tautan).
        </p>

        <!-- ---------- TAB PILIH TIPE ---------- -->
        <div class="tab-row mt-30">
          <button
            v-for="t in tipes"
            :key="t.key"
            class="tab"
            :class="{ 'tab-active': activeTipe === t.key }"
            @click="selectTipe(t.key)"
          >
            {{ t.label }}
          </button>
        </div>

        <!-- ---------- AMBIL DARI PROFIL DOSEN (khusus Kepala Lab) ---------- -->
        <div v-if="activeTipe === 'kepala_lab' && !loading" class="generate-box mt-30">
          <h3 class="mb-20">Ambil dari Profil Dosen</h3>

          <div class="form-row">
            <label>Pilih Dosen</label>
            <select class="form-ctrl input-border" v-model="genDosenId">
              <option value="">- pilih dosen -</option>
              <option v-for="d in dosenList" :key="d.id" :value="d.id">{{ d.user?.name }}</option>
            </select>
          </div>

          <div class="form-row">
            <label>Bagian yang ditampilkan</label>
            <div class="check-grid">
              <label v-for="f in genFields" :key="f.key" class="check-item">
                <input type="checkbox" v-model="f.on" /> {{ f.label }}
              </label>
            </div>
          </div>

          <button
            type="button"
            class="btn btn-navy-border"
            style="width: auto; padding: 8px 20px"
            :disabled="!genDosenId || generating"
            @click="generateFromDosen"
          >
            {{ generating ? 'Mengambil...' : 'Ambil & Isikan ke Editor' }}
          </button>
          <p v-if="genError" style="color: #c0392b" class="mt-20">{{ genError }}</p>
        </div>
        <!-- ---------- AMBIL DARI PROFIL DOSEN END ---------- -->

        <!-- ---------- FORM KONTEN ---------- -->
        <p v-if="loading" class="mt-30">Memuat konten...</p>

        <form v-else class="info-form mt-30" @submit.prevent="save">
          <!-- ---------- PENGUMUMAN: daftar ringkas + edit inline per item ---------- -->
          <template v-if="isPengumuman">
            <p class="mb-20" style="color: #5f6368; font-size: 0.9em">
              Pengumuman ini tampil di kartu <strong>Pengumuman</strong> pada Beranda publik. Item paling atas tampil paling awal.
            </p>

            <p v-if="!pengumumanItems.length" class="mini-empty mb-20">Belum ada pengumuman. Klik "Tambah Pengumuman" di bawah.</p>

            <ul class="peng-list">
              <li v-for="(p, i) in pengumumanItems" :key="i" class="peng-item" :class="{ 'peng-open': editingIndex === i }">
                <!-- Baris ringkas (mode tampil) -->
                <div v-if="editingIndex !== i" class="peng-summary">
                  <div class="peng-summary-main">
                    <span class="peng-summary-title">{{ p.judul || '(Tanpa judul)' }}</span>
                    <span class="peng-summary-meta">
                      <span v-if="p.tanggal">{{ formatTanggalId(p.tanggal) }}</span>
                      <span v-if="p.lampiran && p.lampiran.url" class="peng-clip">&#128206; {{ p.lampiran.label || p.lampiran.url }}</span>
                    </span>
                  </div>
                  <div class="peng-actions">
                    <button type="button" class="peng-icon-btn edit" @click="editingIndex = i">Edit</button>
                    <button type="button" class="peng-icon-btn del" @click="removePengumuman(i)">Hapus</button>
                  </div>
                </div>

                <!-- Form edit (mode sunting) -->
                <div v-else class="peng-edit">
                  <div class="form-row">
                    <label>Judul</label>
                    <input type="text" class="form-ctrl input-border" v-model="p.judul" placeholder="mis. Jadwal UAS Praktikum" />
                  </div>
                  <div class="form-row">
                    <label>Isi</label>
                    <textarea class="form-ctrl input-border peng-isi" rows="2" v-model="p.isi" v-auto-resize placeholder="Keterangan pengumuman..."></textarea>
                  </div>
                  <div class="form-row">
                    <label>Tanggal</label>
                    <input type="date" class="form-ctrl input-border" v-model="p.tanggal" />
                  </div>

                  <!-- Lampiran (opsional): Link/URL atau File — klik lagi tab aktif untuk melepas. -->
                  <div class="form-row">
                    <label>Lampiran (opsional)</label>
                    <div class="seg">
                      <button type="button" class="seg-btn" :class="{ active: p.lampiran && p.lampiran.tipe === 'link' }" @click="toggleLampiran(p, 'link')">Link/URL</button>
                      <button type="button" class="seg-btn" :class="{ active: p.lampiran && p.lampiran.tipe === 'file' }" @click="toggleLampiran(p, 'file')">File</button>
                    </div>

                    <template v-if="p.lampiran && p.lampiran.tipe === 'link'">
                      <input type="text" class="form-ctrl input-border mt-10" v-model="p.lampiran.url" placeholder="https://..." />
                      <input type="text" class="form-ctrl input-border mt-10" v-model="p.lampiran.label" placeholder="Teks tautan (opsional)" />
                    </template>

                    <template v-else-if="p.lampiran && p.lampiran.tipe === 'file'">
                      <div v-if="p.lampiran.url" class="peng-file mt-14">
                        <a :href="p.lampiran.url" target="_blank" rel="noopener">&#128206; {{ p.lampiran.label || 'Lihat file' }}</a>
                        <button type="button" class="peng-icon-btn del" @click="p.lampiran.url = ''; p.lampiran.label = ''">Ganti</button>
                      </div>
                      <template v-else>
                        <input type="file" class="peng-file-input mt-14" :disabled="uploadingIndex === i" @change="onLampiranFile($event, p, i)" />
                        <p v-if="uploadingIndex === i" class="mini-empty mt-10">Mengunggah...</p>
                        <p v-if="uploadError" style="color: #c0392b" class="mt-10">{{ uploadError }}</p>
                      </template>
                    </template>
                  </div>

                  <button type="button" class="btn btn-navy-solid" style="width: auto; padding: 6px 22px" @click="editingIndex = null">Selesai</button>
                </div>
              </li>
            </ul>

            <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 20px; margin: 16px 0" @click="addPengumuman">
              + Tambah Pengumuman
            </button>
          </template>

          <!-- ---------- TIPE LAIN: editor konten biasa (judul/gambar/rich text) ---------- -->
          <template v-else>
            <div class="form-row">
              <label>Judul</label>
              <input type="text" class="form-ctrl input-border" v-model="form.judul" />
            </div>

            <div class="form-row">
              <label>Gambar (URL)</label>
              <input type="text" class="form-ctrl input-border" v-model="form.gambar" placeholder="https://..." />
            </div>

            <div class="form-row">
              <label>Konten</label>
              <RichTextEditor v-model="form.konten" />
            </div>
          </template>

          <p v-if="error" style="color: #c0392b">{{ error }}</p>
          <p v-if="success" style="color: #2e7d32">{{ success }}</p>

          <button type="submit" class="btn btn-navy-solid mt-20" style="width: auto; padding: 8px 28px" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan Konten' }}
          </button>
        </form>
        <!-- ---------- FORM KONTEN END ---------- -->
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Panel kelola konten info lab (Admin only) — sunting 4 tipe halaman informasi.
// Terhubung ke /api/info-lab/{tipe} (3_SDD.md 5.12). Otorisasi backend via Gate manage-info-lab.
import { ref, computed, onMounted } from 'vue'
import { marked } from 'marked'
import { infoLabService } from '@/services/info-lab'
import { dosenService } from '@/services/dosen'
import { useFeedback } from '@/composables/use-feedback'
import { formatTanggalId } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuAdmin from '@/components/sidemenu-admin.vue'
import FooterComponent from '@/components/footer-component.vue'
import RichTextEditor from '@/components/rich-text-editor.vue'

const tipes = [
  { key: 'beranda', label: 'Pengumuman' },
  { key: 'kepala_lab', label: 'Profil Kepala Lab' },
  { key: 'visi_misi', label: 'Visi & Misi' },
  { key: 'roadmap_kk', label: 'Roadmap Lab' },
]

const activeTipe = ref('beranda')
const { confirmDialog } = useFeedback()
const form = ref({ judul: '', konten: '', gambar: '', dosen_id: null })
// Tab "beranda" kini menyunting Pengumuman (daftar terstruktur), bukan konten rich-text.
const isPengumuman = computed(() => activeTipe.value === 'beranda')
const pengumumanItems = ref([])
const editingIndex = ref(null) // item pengumuman yang sedang disunting (inline); null = semua ringkas
const uploadingIndex = ref(null)
const uploadError = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

// --- Ambil dari Profil Dosen (khusus tab kepala_lab) ---
const dosenList = ref([])
const genDosenId = ref('')
const generating = ref(false)
const genError = ref('')
const genFields = ref([
  { key: 'jenis_kelamin', label: 'Jenis Kelamin', on: true },
  { key: 'jabatan_fungsional', label: 'Jabatan Fungsional', on: true },
  { key: 'nidn', label: 'NIDN', on: true },
  { key: 'ttl', label: 'Tempat & Tanggal Lahir', on: true },
  { key: 'email', label: 'Email', on: true },
  { key: 'no_telp', label: 'Nomor Telepon', on: true },
  { key: 'bidang_minat', label: 'Bidang Minat', on: true },
  { key: 'biografi', label: 'Biografi', on: true },
  { key: 'foto', label: 'Foto', on: true },
])

async function loadTipe() {
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    const res = await infoLabService.get(activeTipe.value)
    const d = res.data.data
    if (isPengumuman.value) {
      // Konten Pengumuman disimpan sebagai JSON array [{judul, isi, tanggal}].
      pengumumanItems.value = parsePengumuman(d.konten)
      form.value = { judul: d.judul || 'Pengumuman', konten: '', gambar: '', dosen_id: null }
    } else {
      form.value = { judul: d.judul || '', konten: toEditorHtml(d.konten || ''), gambar: d.gambar || '', dosen_id: d.dosen_id ?? null }
      genDosenId.value = d.dosen_id ?? ''
    }
  } catch (err) {
    // Belum ada baris (404) → mulai dari form kosong, simpan akan membuatnya
    if (err.response?.status === 404) {
      pengumumanItems.value = []
      form.value = { judul: '', konten: '', gambar: '', dosen_id: null }
    } else {
      error.value = extractError(err)
    }
  } finally {
    loading.value = false
  }
}

function selectTipe(key) {
  if (key === activeTipe.value) return
  activeTipe.value = key
  editingIndex.value = null
  loadTipe()
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    if (isPengumuman.value) {
      // Simpan hanya item terisi; JSON string ("[]" pun sah untuk aturan required|string).
      const items = pengumumanItems.value
        .filter((p) => (p.judul || '').trim() || (p.isi || '').trim())
        .map((p) => {
          const item = { judul: (p.judul || '').trim(), isi: (p.isi || '').trim(), tanggal: p.tanggal || '' }
          if (p.lampiran && (p.lampiran.url || '').trim()) {
            item.lampiran = { tipe: p.lampiran.tipe === 'file' ? 'file' : 'link', url: p.lampiran.url.trim(), label: (p.lampiran.label || '').trim() }
          }
          return item
        })
      await infoLabService.update('beranda', { judul: form.value.judul || 'Pengumuman', konten: JSON.stringify(items), gambar: '' })
    } else {
      await infoLabService.update(activeTipe.value, form.value)
    }
    success.value = 'Konten berhasil disimpan.'
  } catch (err) {
    error.value = extractError(err)
  } finally {
    saving.value = false
  }
}

// --- Pengumuman (tab "beranda") ---
// Baca konten JSON menjadi daftar item; toleran terhadap data lama (teks biasa) → daftar kosong.
function parsePengumuman(raw) {
  if (!raw) return []
  try {
    const arr = JSON.parse(raw)
    if (!Array.isArray(arr)) return []
    return arr
      .filter((x) => x && (x.judul || x.isi))
      .map((x) => ({ judul: x.judul || '', isi: x.isi || '', tanggal: (x.tanggal || '').slice(0, 10), lampiran: normLampiran(x.lampiran) }))
  } catch {
    return []
  }
}

// Bentuk lampiran yang sah: { tipe: 'link'|'file', url, label }. Selain itu → null.
function normLampiran(l) {
  if (!l || (l.tipe !== 'link' && l.tipe !== 'file')) return null
  return { tipe: l.tipe, url: l.url || '', label: l.label || '' }
}

function addPengumuman() {
  pengumumanItems.value.push({ judul: '', isi: '', tanggal: '', lampiran: null })
  editingIndex.value = pengumumanItems.value.length - 1 // langsung buka untuk disunting
}

function removePengumuman(i) {
  pengumumanItems.value.splice(i, 1)
  if (editingIndex.value === i) editingIndex.value = null
  else if (editingIndex.value > i) editingIndex.value -= 1
}

// Toggle lampiran: klik tab (link/file) untuk memilih; klik tab aktif lagi untuk melepas.
// Lampiran opsional, jadi tak ada tab "Tanpa" — cukup lepas dengan klik ulang.
function toggleLampiran(p, tipe) {
  p.lampiran = p.lampiran && p.lampiran.tipe === tipe ? null : { tipe, url: '', label: '' }
  uploadError.value = ''
}

// Textarea "Isi" tumbuh mengikuti konten (auto-resize) — pas untuk pengumuman pendek/panjang.
function fitTextarea(el) {
  el.style.height = 'auto'
  el.style.height = el.scrollHeight + 'px'
}
const vAutoResize = {
  mounted(el) {
    fitTextarea(el)
    el.addEventListener('input', () => fitTextarea(el))
  },
}

// Unggah file lampiran → simpan URL & nama asli ke item.
async function onLampiranFile(e, p, i) {
  const file = e.target.files?.[0]
  if (!file) return
  uploadingIndex.value = i
  uploadError.value = ''
  try {
    const res = await infoLabService.upload(file)
    // Sembunyikan ekstensi/format file dari label yang tampil ke publik.
    const label = (res.data.name || '').replace(/\.[^.]+$/, '')
    p.lampiran = { tipe: 'file', url: res.data.url, label }
  } catch (err) {
    uploadError.value = extractError(err)
    e.target.value = '' // izinkan pilih ulang file yang sama
  } finally {
    uploadingIndex.value = null
  }
}

// Konten lama tersimpan sebagai Markdown; konten baru sebagai HTML (editor visual).
// Saat dibuka: render Markdown→HTML agar tampil utuh di editor; jika sudah HTML, pakai apa adanya.
function toEditorHtml(content) {
  if (!content) return ''
  const looksHtml = /<[a-z][\s\S]*>/i.test(content)
  const html = looksHtml ? content : marked.parse(content)
  return stripEmptyThead(html)
}

// Hapus <thead> yang selnya kosong (tabel profil dari markdown memang berheader kosong),
// supaya TipTap tidak membuat baris header kosong. Tampilan publik tidak berubah.
function stripEmptyThead(html) {
  return String(html).replace(/<thead>[\s\S]*?<\/thead>/gi, (block) => {
    const text = block.replace(/<[^>]+>/g, '').trim()
    return text === '' ? '' : block
  })
}

// Muat daftar dosen untuk pemilih "Ambil dari Profil Dosen"
async function loadDosenList() {
  try {
    const res = await dosenService.getAll()
    dosenList.value = res.data.data
  } catch {
    dosenList.value = []
  }
}

function escHtml(s) {
  return String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c])
}

function formatTanggalLahir(iso) {
  if (!iso) return ''
  const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
  const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number)
  if (!y || !m || !d) return ''
  return `${d} ${bulan[m - 1]} ${y}`
}

// Susun konten Kepala Lab dari profil dosen — hanya bagian yang dicentang.
// Hasil diisikan ke editor (judul/gambar/konten) untuk disunting lalu disimpan.
async function generateFromDosen() {
  if (!genDosenId.value) return
  if (form.value.konten && !(await confirmDialog('Ini akan menimpa konten Kepala Lab saat ini. Lanjutkan?'))) return

  generating.value = true
  genError.value = ''
  try {
    const res = await dosenService.get(genDosenId.value)
    const d = res.data.data
    const on = (k) => genFields.value.find((f) => f.key === k)?.on

    const ttl = [d.tempat_lahir, formatTanggalLahir(d.tanggal_lahir)].filter(Boolean).join(', ')
    const bidang = Array.isArray(d.bidang_minat) ? d.bidang_minat.map((b) => b.nama).join(', ') : ''

    const rows = []
    if (on('jenis_kelamin') && d.jenis_kelamin) rows.push(['Jenis Kelamin', d.jenis_kelamin])
    if (on('jabatan_fungsional') && d.jabatan_fungsional) rows.push(['Jabatan Fungsional', d.jabatan_fungsional])
    if (on('nidn') && d.nidn) rows.push(['NIDN', d.nidn])
    if (on('ttl') && ttl) rows.push(['Tempat dan Tanggal Lahir', ttl])
    if (on('email') && d.user?.email) rows.push(['Email', d.user.email])
    if (on('no_telp') && d.user?.no_telp) rows.push(['Nomor Telepon', d.user.no_telp])
    if (on('bidang_minat') && bidang) rows.push(['Bidang Minat', bidang])

    let html = ''
    if (rows.length) {
      const trs = rows
        .map(([l, v]) => `<tr><td><strong>${escHtml(l)}</strong></td><td>:</td><td>${escHtml(v)}</td></tr>`)
        .join('')
      html += `<table><tbody>${trs}</tbody></table>`
    }
    if (on('biografi') && d.biografi) html += `<p>${escHtml(d.biografi)}</p>`

    form.value.judul = d.user?.name || form.value.judul
    if (on('foto')) form.value.gambar = d.foto || d.user?.avatar || form.value.gambar
    form.value.konten = html
    // Tautkan dosen agar halaman publik dirender sebagai kartu identitas (bukan hanya konten).
    form.value.dosen_id = Number(genDosenId.value)
    success.value = 'Data dosen berhasil diambil & ditautkan. Klik Simpan Konten untuk menerapkan.'
  } catch (err) {
    genError.value = extractError(err)
  } finally {
    generating.value = false
  }
}

// Ambil pesan error ramah dari respons Laravel (422 / 403)
function extractError(err) {
  const res = err.response?.data
  if (res?.errors) {
    const first = Object.values(res.errors)[0]
    if (Array.isArray(first) && first.length) return first[0]
  }
  return res?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

onMounted(() => {
  loadTipe()
  loadDosenList()
})
</script>

<style scoped>
.tab-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.tab {
  padding: 8px 18px;
  background-color: var(--bs-grey2);
  border: none;
  border-radius: 5px;
  cursor: pointer;
  color: var(--bs-navy);
}

.tab:hover {
  font-weight: bold;
}

.tab-active {
  font-weight: bold;
  border-bottom: 3px solid var(--bs-yellow);
}

.generate-box {
  max-width: 640px;
  padding: 20px;
  background-color: var(--bs-grey1);
  border-radius: 8px;
}

.check-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 6px 16px;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 400;
  cursor: pointer;
}

.check-item input {
  margin: 0;
}

.info-form {
  max-width: 640px;
}

.form-row {
  margin-bottom: 16px;
}

.form-row label {
  display: block;
  margin-bottom: 6px;
}

.form-row .form-ctrl {
  width: 100%;
}

textarea.form-ctrl {
  resize: vertical;
  font-family: inherit;
}

/* Isi pengumuman: auto-resize, tanpa scrollbar & tanpa handle manual. */
.peng-isi {
  resize: none;
  overflow: hidden;
  min-height: 44px;
}

.mt-14 {
  margin-top: 14px;
}

/* File input pada satu baris tersendiri, di bawah tombol segmen. */
.peng-file-input {
  display: block;
}

/* Daftar pengumuman ringkas. */
.peng-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin: 0;
  padding: 0;
}

.peng-item {
  border: 1px solid var(--bs-grey2);
  border-radius: 8px;
  background-color: #fff;
  overflow: hidden;
}

.peng-item.peng-open {
  background-color: var(--bs-grey1);
}

/* Baris ringkas: judul + meta di kiri, tombol Edit/Hapus di kanan. */
.peng-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
}

.peng-summary-main {
  flex: 1;
  min-width: 0;
}

.peng-summary-title {
  display: block;
  font-weight: 600;
  color: var(--bs-navy);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.peng-summary-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 12px;
  margin-top: 2px;
  font-size: 0.8em;
  color: #9aa0a6;
}

.peng-clip {
  color: var(--bs-navy);
}

.peng-actions {
  flex-shrink: 0;
  display: flex;
  gap: 6px;
}

.peng-icon-btn {
  border: 1px solid var(--bs-grey2);
  background-color: #fff;
  border-radius: 5px;
  padding: 4px 12px;
  font-size: 0.85em;
  font-weight: 600;
  cursor: pointer;
}

.peng-icon-btn.edit {
  color: var(--bs-navy);
}

.peng-icon-btn.edit:hover {
  border-color: var(--bs-navy);
}

.peng-icon-btn.del {
  color: #c0392b;
}

.peng-icon-btn.del:hover {
  border-color: #c0392b;
}

/* Form edit inline. */
.peng-edit {
  padding: 16px 14px;
}

/* Segmen pilih tipe lampiran (Tanpa / Link / File). */
.seg {
  display: inline-flex;
  border: 1px solid var(--bs-grey2);
  border-radius: 6px;
  overflow: hidden;
}

.seg-btn {
  border: none;
  background-color: #fff;
  padding: 6px 16px;
  cursor: pointer;
  color: var(--bs-navy);
  font-size: 0.88em;
  border-right: 1px solid var(--bs-grey2);
}

.seg-btn:last-child {
  border-right: none;
}

.seg-btn.active {
  background-color: var(--bs-navy);
  color: #fff;
  font-weight: 600;
}

.peng-file {
  display: flex;
  align-items: center;
  gap: 12px;
}

.peng-file a {
  color: var(--bs-navy);
  text-decoration: underline;
  word-break: break-all;
}
</style>
