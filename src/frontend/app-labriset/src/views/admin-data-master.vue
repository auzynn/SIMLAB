<template>
  <div>
    <JumbotronSmall title="Data Master" />

    <div class="main-container flex-h between">
      <SidemenuAdmin />

      <div class="profil-container">
        <div>
          <h1>Data Master</h1>
          <div class="profil-title"></div>
        </div>

        <p class="mt-30" style="max-width: 640px">
          Kelola data master laboratorium: ruangan, mata kuliah/praktikum, dan bidang minat. Data ini
          menjadi acuan saat pengajuan peminjaman ruangan, pembukaan Kelas Lab, dan Edit Profil Dosen.
        </p>

        <!-- Tab pemilih entitas -->
        <div class="tab-bar mt-30">
          <button :class="['tab', { active: tab === 'ruangan' }]" @click="tab = 'ruangan'">Ruangan</button>
          <button :class="['tab', { active: tab === 'mata-kuliah' }]" @click="tab = 'mata-kuliah'">Mata Kuliah</button>
          <button :class="['tab', { active: tab === 'perangkat' }]" @click="tab = 'perangkat'">Perangkat</button>
          <button :class="['tab', { active: tab === 'bidang-minat' }]" @click="tab = 'bidang-minat'">Bidang Minat</button>
        </div>

        <!-- ============ TAB RUANGAN ============ -->
        <section v-show="tab === 'ruangan'">
          <div class="flex-h between mt-30">
            <h3>Daftar Ruangan</h3>
            <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreateRuangan">
              + Tambah Ruangan
            </button>
          </div>

          <form v-if="showRuanganForm" class="master-form mt-20" @submit.prevent="submitRuangan">
            <h3 class="mb-20">{{ ruanganForm.id ? 'Edit Ruangan' : 'Tambah Ruangan' }}</h3>
            <div class="form-row">
              <label>Nama Ruangan</label>
              <input type="text" class="form-ctrl input-border" v-model="ruanganForm.nama_ruangan" required maxlength="255" placeholder="mis. Lab Jaringan Komputer" />
            </div>
            <div class="form-row">
              <label>Kapasitas (opsional)</label>
              <input type="number" min="0" class="form-ctrl input-border" v-model="ruanganForm.kapasitas" placeholder="mis. 40" />
            </div>
            <div class="form-row">
              <label>Status</label>
              <select class="form-ctrl input-border" v-model="ruanganForm.status" required>
                <option value="tersedia">Tersedia</option>
                <option value="dipakai">Dipakai</option>
                <option value="perbaikan">Perbaikan</option>
              </select>
            </div>

            <p v-if="ruanganError" style="color: #c0392b">{{ ruanganError }}</p>

            <div class="flex-h mt-20" style="gap: 12px">
              <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="savingRuangan">
                {{ savingRuangan ? 'Menyimpan...' : 'Simpan' }}
              </button>
              <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="showRuanganForm = false">Batal</button>
            </div>
          </form>

          <p v-if="loadingRuangan" class="mt-30">Memuat data...</p>
          <p v-else-if="ruanganListError" class="mt-30" style="color: #c0392b">{{ ruanganListError }}</p>
          <table v-else class="master-table mt-20">
            <thead>
              <tr>
                <th>Nama Ruangan</th>
                <th>Kapasitas</th>
                <th>Status</th>
                <th style="text-align: right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in pagedRuangan" :key="r.id">
                <td>{{ r.nama_ruangan }}</td>
                <td>{{ r.kapasitas ?? '—' }}</td>
                <td><span :class="['status-badge', `status-${r.status}`]">{{ statusLabel(r.status) }}</span></td>
                <td style="text-align: right">
                  <button class="btn-link" @click="openEditRuangan(r)">Edit</button>
                  <button class="btn-link btn-link-danger" @click="removeRuangan(r)">Hapus</button>
                </td>
              </tr>
              <tr v-if="!ruanganItems.length">
                <td colspan="4" style="text-align: center; color: #9aa0a6">Belum ada ruangan.</td>
              </tr>
            </tbody>
          </table>
          <PaginationBar v-model:page="ruanganPage" :total-pages="ruanganTotalPages" />
        </section>

        <!-- ============ TAB MATA KULIAH ============ -->
        <section v-show="tab === 'mata-kuliah'">
          <div class="flex-h between mt-30">
            <h3>Daftar Mata Kuliah</h3>
            <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreateMk">
              + Tambah Mata Kuliah
            </button>
          </div>

          <form v-if="showMkForm" class="master-form mt-20" @submit.prevent="submitMk">
            <h3 class="mb-20">{{ mkForm.id ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah' }}</h3>
            <div class="form-row">
              <label>Kode MK (opsional)</label>
              <input type="text" class="form-ctrl input-border" v-model="mkForm.kode_mk" maxlength="50" placeholder="mis. JKF301" />
            </div>
            <div class="form-row">
              <label>Nama Mata Kuliah</label>
              <input type="text" class="form-ctrl input-border" v-model="mkForm.nama_mk" required maxlength="255" placeholder="mis. Praktikum Jaringan Komputer" />
            </div>
            <div class="form-row">
              <label>SKS (opsional)</label>
              <input type="number" min="0" max="24" class="form-ctrl input-border" v-model="mkForm.sks" placeholder="mis. 3" />
            </div>

            <p v-if="mkError" style="color: #c0392b">{{ mkError }}</p>

            <div class="flex-h mt-20" style="gap: 12px">
              <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="savingMk">
                {{ savingMk ? 'Menyimpan...' : 'Simpan' }}
              </button>
              <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="showMkForm = false">Batal</button>
            </div>
          </form>

          <p v-if="loadingMk" class="mt-30">Memuat data...</p>
          <p v-else-if="mkListError" class="mt-30" style="color: #c0392b">{{ mkListError }}</p>
          <table v-else class="master-table mt-20">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <th style="text-align: right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in pagedMk" :key="m.id">
                <td>{{ m.kode_mk ?? '—' }}</td>
                <td>{{ m.nama_mk }}</td>
                <td>{{ m.sks ?? '—' }}</td>
                <td style="text-align: right">
                  <button class="btn-link" @click="openEditMk(m)">Edit</button>
                  <button class="btn-link btn-link-danger" @click="removeMk(m)">Hapus</button>
                </td>
              </tr>
              <tr v-if="!mkItems.length">
                <td colspan="4" style="text-align: center; color: #9aa0a6">Belum ada mata kuliah.</td>
              </tr>
            </tbody>
          </table>
          <PaginationBar v-model:page="mkPage" :total-pages="mkTotalPages" />
        </section>

        <!-- ============ TAB PERANGKAT ============ -->
        <section v-show="tab === 'perangkat'">
          <div class="flex-h between mt-30">
            <h3>Daftar Perangkat</h3>
            <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreatePerangkat">
              + Tambah Perangkat
            </button>
          </div>
          <p class="mt-10" style="max-width: 640px; color: #5f6368">
            Inventaris perangkat lab. Status "dipinjam" diatur otomatis oleh alur peminjaman;
            ubah manual hanya untuk perbaikan/pemeliharaan.
          </p>

          <form v-if="showPerangkatForm" class="master-form mt-20" @submit.prevent="submitPerangkat">
            <h3 class="mb-20">{{ perangkatForm.id ? 'Edit Perangkat' : 'Tambah Perangkat' }}</h3>
            <div class="form-row">
              <label>Nama Perangkat</label>
              <input type="text" class="form-ctrl input-border" v-model="perangkatForm.nama_perangkat" required maxlength="255" placeholder="mis. Router Mikrotik RB951" />
            </div>
            <div class="form-row">
              <label>Nomor Seri</label>
              <input type="text" class="form-ctrl input-border" v-model="perangkatForm.nomor_seri" required maxlength="255" placeholder="mis. SN-0012" />
            </div>
            <div class="form-row">
              <label>Kategori (opsional)</label>
              <input type="text" class="form-ctrl input-border" v-model="perangkatForm.kategori" maxlength="255" placeholder="mis. Router" />
            </div>
            <div class="form-row">
              <label>Status</label>
              <select class="form-ctrl input-border" v-model="perangkatForm.status" required>
                <option value="tersedia">Tersedia</option>
                <option value="dipinjam">Dipinjam</option>
                <option value="perbaikan">Perbaikan</option>
              </select>
            </div>

            <p v-if="perangkatError" style="color: #c0392b">{{ perangkatError }}</p>

            <div class="flex-h mt-20" style="gap: 12px">
              <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="savingPerangkat">
                {{ savingPerangkat ? 'Menyimpan...' : 'Simpan' }}
              </button>
              <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="showPerangkatForm = false">Batal</button>
            </div>
          </form>

          <p v-if="loadingPerangkat" class="mt-30">Memuat data...</p>
          <p v-else-if="perangkatListError" class="mt-30" style="color: #c0392b">{{ perangkatListError }}</p>
          <table v-else class="master-table mt-20">
            <thead>
              <tr>
                <th>Nama Perangkat</th>
                <th>Nomor Seri</th>
                <th>Kategori</th>
                <th>Status</th>
                <th style="text-align: right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in pagedPerangkat" :key="p.id">
                <td>{{ p.nama_perangkat }}</td>
                <td>{{ p.nomor_seri }}</td>
                <td>{{ p.kategori ?? '—' }}</td>
                <td><span :class="['status-badge', `status-${p.status}`]">{{ statusPerangkatLabel(p.status) }}</span></td>
                <td style="text-align: right">
                  <button class="btn-link" @click="openEditPerangkat(p)">Edit</button>
                  <button class="btn-link btn-link-danger" @click="removePerangkat(p)">Hapus</button>
                </td>
              </tr>
              <tr v-if="!perangkatItems.length">
                <td colspan="5" style="text-align: center; color: #9aa0a6">Belum ada perangkat.</td>
              </tr>
            </tbody>
          </table>
          <PaginationBar v-model:page="perangkatPage" :total-pages="perangkatTotalPages" />
        </section>

        <!-- ============ TAB BIDANG MINAT ============ -->
        <section v-show="tab === 'bidang-minat'">
          <div class="flex-h between mt-30">
            <h3>Daftar Bidang Minat</h3>
            <button class="btn btn-navy-solid" style="width: auto; padding: 8px 20px" @click="openCreateBidang">
              + Tambah Bidang
            </button>
          </div>
          <p class="mt-10" style="max-width: 640px; color: #5f6368">
            Daftar bidang minat yang dapat dipilih dosen di menu Edit Profil (boleh lebih dari satu).
          </p>

          <form v-if="showBidangForm" class="master-form mt-20" @submit.prevent="submitBidang">
            <h3 class="mb-20">{{ bidangForm.id ? 'Edit Bidang' : 'Tambah Bidang' }}</h3>
            <div class="form-row">
              <label>Nama Bidang</label>
              <input type="text" class="form-ctrl input-border" v-model="bidangForm.nama" required maxlength="100" placeholder="mis. Digital Forensik" />
            </div>

            <p v-if="bidangError" style="color: #c0392b">{{ bidangError }}</p>

            <div class="flex-h mt-20" style="gap: 12px">
              <button type="submit" class="btn btn-navy-solid" style="width: auto; padding: 8px 24px" :disabled="savingBidang">
                {{ savingBidang ? 'Menyimpan...' : 'Simpan' }}
              </button>
              <button type="button" class="btn btn-navy-border" style="width: auto; padding: 8px 24px" @click="showBidangForm = false">Batal</button>
            </div>
          </form>

          <p v-if="loadingBidang" class="mt-30">Memuat data...</p>
          <p v-else-if="bidangListError" class="mt-30" style="color: #c0392b">{{ bidangListError }}</p>
          <table v-else class="master-table mt-20">
            <thead>
              <tr>
                <th>Nama Bidang</th>
                <th style="text-align: right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in pagedBidang" :key="b.id">
                <td>{{ b.nama }}</td>
                <td style="text-align: right">
                  <button class="btn-link" @click="openEditBidang(b)">Edit</button>
                  <button class="btn-link btn-link-danger" @click="removeBidang(b)">Hapus</button>
                </td>
              </tr>
              <tr v-if="!bidangItems.length">
                <td colspan="2" style="text-align: center; color: #9aa0a6">Belum ada bidang minat.</td>
              </tr>
            </tbody>
          </table>
          <PaginationBar v-model:page="bidangPage" :total-pages="bidangTotalPages" />
        </section>
      </div>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Panel Admin/Supervisor — kelola Data Master (Ruangan & Mata Kuliah), Gate manage-master-data.
import { ref, onMounted } from 'vue'
import { ruanganService } from '@/services/ruangan'
import { mataKuliahService } from '@/services/mata-kuliah'
import { perangkatService } from '@/services/perangkat'
import { bidangMinatService } from '@/services/bidang-minat'
import { statusPerangkatLabel } from '@/utils/format'
import { usePagination } from '@/composables/use-pagination'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuAdmin from '@/components/sidemenu-admin.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const tab = ref('ruangan')

// ---------- Ruangan ----------
const ruanganItems = ref([])
const { page: ruanganPage, totalPages: ruanganTotalPages, pagedItems: pagedRuangan } = usePagination(ruanganItems, 10)
const loadingRuangan = ref(false)
const ruanganListError = ref('')
const showRuanganForm = ref(false)
const ruanganForm = ref({ id: null, nama_ruangan: '', kapasitas: '', status: 'tersedia' })
const savingRuangan = ref(false)
const ruanganError = ref('')

async function loadRuangan() {
  loadingRuangan.value = true
  ruanganListError.value = ''
  try {
    const res = await ruanganService.list()
    ruanganItems.value = res.data.data
  } catch (err) {
    ruanganListError.value = extractError(err)
  } finally {
    loadingRuangan.value = false
  }
}

function openCreateRuangan() {
  ruanganForm.value = { id: null, nama_ruangan: '', kapasitas: '', status: 'tersedia' }
  ruanganError.value = ''
  showRuanganForm.value = true
}

function openEditRuangan(r) {
  ruanganForm.value = { id: r.id, nama_ruangan: r.nama_ruangan, kapasitas: r.kapasitas ?? '', status: r.status }
  ruanganError.value = ''
  showRuanganForm.value = true
}

async function submitRuangan() {
  savingRuangan.value = true
  ruanganError.value = ''
  const payload = {
    nama_ruangan: ruanganForm.value.nama_ruangan,
    kapasitas: ruanganForm.value.kapasitas === '' ? null : Number(ruanganForm.value.kapasitas),
    status: ruanganForm.value.status,
  }
  try {
    if (ruanganForm.value.id) {
      await ruanganService.update(ruanganForm.value.id, payload)
    } else {
      await ruanganService.create(payload)
    }
    showRuanganForm.value = false
    await loadRuangan()
  } catch (err) {
    ruanganError.value = extractError(err)
  } finally {
    savingRuangan.value = false
  }
}

async function removeRuangan(r) {
  if (!confirm(`Hapus ruangan "${r.nama_ruangan}"?`)) return
  try {
    await ruanganService.remove(r.id)
    await loadRuangan()
  } catch (err) {
    alert(extractError(err))
  }
}

function statusLabel(status) {
  return { tersedia: 'Tersedia', dipakai: 'Dipakai', perbaikan: 'Perbaikan' }[status] ?? status
}

// ---------- Mata Kuliah ----------
const mkItems = ref([])
const { page: mkPage, totalPages: mkTotalPages, pagedItems: pagedMk } = usePagination(mkItems, 10)
const loadingMk = ref(false)
const mkListError = ref('')
const showMkForm = ref(false)
const mkForm = ref({ id: null, kode_mk: '', nama_mk: '', sks: '' })
const savingMk = ref(false)
const mkError = ref('')

async function loadMk() {
  loadingMk.value = true
  mkListError.value = ''
  try {
    const res = await mataKuliahService.list()
    mkItems.value = res.data.data
  } catch (err) {
    mkListError.value = extractError(err)
  } finally {
    loadingMk.value = false
  }
}

function openCreateMk() {
  mkForm.value = { id: null, kode_mk: '', nama_mk: '', sks: '' }
  mkError.value = ''
  showMkForm.value = true
}

function openEditMk(m) {
  mkForm.value = { id: m.id, kode_mk: m.kode_mk ?? '', nama_mk: m.nama_mk, sks: m.sks ?? '' }
  mkError.value = ''
  showMkForm.value = true
}

async function submitMk() {
  savingMk.value = true
  mkError.value = ''
  const payload = {
    kode_mk: mkForm.value.kode_mk === '' ? null : mkForm.value.kode_mk,
    nama_mk: mkForm.value.nama_mk,
    sks: mkForm.value.sks === '' ? null : Number(mkForm.value.sks),
  }
  try {
    if (mkForm.value.id) {
      await mataKuliahService.update(mkForm.value.id, payload)
    } else {
      await mataKuliahService.create(payload)
    }
    showMkForm.value = false
    await loadMk()
  } catch (err) {
    mkError.value = extractError(err)
  } finally {
    savingMk.value = false
  }
}

async function removeMk(m) {
  if (!confirm(`Hapus mata kuliah "${m.nama_mk}"?`)) return
  try {
    await mataKuliahService.remove(m.id)
    await loadMk()
  } catch (err) {
    alert(extractError(err))
  }
}

// ---------- Perangkat ----------
const perangkatItems = ref([])
const { page: perangkatPage, totalPages: perangkatTotalPages, pagedItems: pagedPerangkat } = usePagination(perangkatItems, 10)
const loadingPerangkat = ref(false)
const perangkatListError = ref('')
const showPerangkatForm = ref(false)
const perangkatForm = ref({ id: null, nama_perangkat: '', nomor_seri: '', kategori: '', status: 'tersedia' })
const savingPerangkat = ref(false)
const perangkatError = ref('')

async function loadPerangkat() {
  loadingPerangkat.value = true
  perangkatListError.value = ''
  try {
    const res = await perangkatService.list()
    perangkatItems.value = res.data.data
  } catch (err) {
    perangkatListError.value = extractError(err)
  } finally {
    loadingPerangkat.value = false
  }
}

function openCreatePerangkat() {
  perangkatForm.value = { id: null, nama_perangkat: '', nomor_seri: '', kategori: '', status: 'tersedia' }
  perangkatError.value = ''
  showPerangkatForm.value = true
}

function openEditPerangkat(p) {
  perangkatForm.value = { id: p.id, nama_perangkat: p.nama_perangkat, nomor_seri: p.nomor_seri, kategori: p.kategori ?? '', status: p.status }
  perangkatError.value = ''
  showPerangkatForm.value = true
}

async function submitPerangkat() {
  savingPerangkat.value = true
  perangkatError.value = ''
  const payload = {
    nama_perangkat: perangkatForm.value.nama_perangkat,
    nomor_seri: perangkatForm.value.nomor_seri,
    kategori: perangkatForm.value.kategori === '' ? null : perangkatForm.value.kategori,
    status: perangkatForm.value.status,
  }
  try {
    if (perangkatForm.value.id) {
      await perangkatService.update(perangkatForm.value.id, payload)
    } else {
      await perangkatService.create(payload)
    }
    showPerangkatForm.value = false
    await loadPerangkat()
  } catch (err) {
    perangkatError.value = extractError(err)
  } finally {
    savingPerangkat.value = false
  }
}

async function removePerangkat(p) {
  if (!confirm(`Hapus perangkat "${p.nama_perangkat}"?`)) return
  try {
    await perangkatService.remove(p.id)
    await loadPerangkat()
  } catch (err) {
    alert(extractError(err))
  }
}

// ---------- Bidang Minat ----------
const bidangItems = ref([])
const { page: bidangPage, totalPages: bidangTotalPages, pagedItems: pagedBidang } = usePagination(bidangItems, 10)
const loadingBidang = ref(false)
const bidangListError = ref('')
const showBidangForm = ref(false)
const bidangForm = ref({ id: null, nama: '' })
const savingBidang = ref(false)
const bidangError = ref('')

async function loadBidang() {
  loadingBidang.value = true
  bidangListError.value = ''
  try {
    const res = await bidangMinatService.list()
    bidangItems.value = res.data.data
  } catch (err) {
    bidangListError.value = extractError(err)
  } finally {
    loadingBidang.value = false
  }
}

function openCreateBidang() {
  bidangForm.value = { id: null, nama: '' }
  bidangError.value = ''
  showBidangForm.value = true
}

function openEditBidang(b) {
  bidangForm.value = { id: b.id, nama: b.nama }
  bidangError.value = ''
  showBidangForm.value = true
}

async function submitBidang() {
  savingBidang.value = true
  bidangError.value = ''
  try {
    if (bidangForm.value.id) {
      await bidangMinatService.update(bidangForm.value.id, bidangForm.value.nama)
    } else {
      await bidangMinatService.create(bidangForm.value.nama)
    }
    showBidangForm.value = false
    await loadBidang()
  } catch (err) {
    bidangError.value = extractError(err)
  } finally {
    savingBidang.value = false
  }
}

async function removeBidang(b) {
  if (!confirm(`Hapus bidang "${b.nama}"? Pilihan dosen pada bidang ini akan ikut terhapus.`)) return
  try {
    await bidangMinatService.remove(b.id)
    await loadBidang()
  } catch (err) {
    alert(extractError(err))
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
  loadRuangan()
  loadMk()
  loadPerangkat()
  loadBidang()
})
</script>

<style scoped>
.tab-bar {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid var(--bs-grey2);
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

.master-form {
  max-width: 480px;
  padding: 24px;
  background-color: var(--bs-grey1);
  border-radius: 8px;
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

.master-table {
  width: 100%;
  border-collapse: collapse;
}

.master-table th,
.master-table td {
  padding: 12px 10px;
  text-align: left;
  border-bottom: 1px solid var(--bs-grey2);
}

.master-table th {
  border-bottom: 3px solid var(--bs-grey2);
}

.status-badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 0.85em;
  font-weight: 600;
}

.status-tersedia {
  color: #1e7e34;
  background-color: #d4edda;
}

.status-dipakai,
.status-dipinjam {
  color: #856404;
  background-color: #fff3cd;
}

.status-perbaikan {
  color: #c0392b;
  background-color: #f8d7da;
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
