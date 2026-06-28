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
import { ref, onMounted } from 'vue'
import { marked } from 'marked'
import { infoLabService } from '@/services/info-lab'
import { dosenService } from '@/services/dosen'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuAdmin from '@/components/sidemenu-admin.vue'
import FooterComponent from '@/components/footer-component.vue'
import RichTextEditor from '@/components/rich-text-editor.vue'

const tipes = [
  { key: 'beranda', label: 'Beranda' },
  { key: 'kepala_lab', label: 'Profil Kepala Lab' },
  { key: 'visi_misi', label: 'Visi & Misi' },
  { key: 'roadmap_kk', label: 'Roadmap Lab' },
]

const activeTipe = ref('beranda')
const form = ref({ judul: '', konten: '', gambar: '' })
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
    form.value = { judul: d.judul || '', konten: toEditorHtml(d.konten || ''), gambar: d.gambar || '' }
  } catch (err) {
    // Belum ada baris (404) → mulai dari form kosong, simpan akan membuatnya
    if (err.response?.status === 404) {
      form.value = { judul: '', konten: '', gambar: '' }
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
  loadTipe()
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    await infoLabService.update(activeTipe.value, form.value)
    success.value = 'Konten berhasil disimpan.'
  } catch (err) {
    error.value = extractError(err)
  } finally {
    saving.value = false
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
  if (form.value.konten && !confirm('Ini akan menimpa konten Kepala Lab saat ini. Lanjutkan?')) return

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
    success.value = 'Data dosen berhasil diambil. Sunting bila perlu, lalu klik Simpan Konten.'
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
</style>
