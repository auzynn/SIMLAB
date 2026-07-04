<template>
  <div>
    <JumbotronSmall title="Delegasi Aslab" />

    <div class="main-container flex-h between">
      <SidemenuAdmin />

      <div class="profil-container">
        <div>
          <h1>Delegasi Asisten Lab</h1>
          <div class="profil-title"></div>
        </div>
        <p class="mt-30" style="max-width: 640px">
          Tetapkan mahasiswa menjadi Asisten Lab (Supervisor) untuk semester/tahun berikutnya.
          Data diambil dari mahasiswa terdaftar. Aslab dapat dikembalikan menjadi mahasiswa kapan saja.
        </p>

        <p v-if="loading" class="mt-30">Memuat data...</p>
        <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>

        <template v-else>
          <!-- Aslab aktif -->
          <h3 class="mt-30">Asisten Lab Aktif ({{ aslab.length }})</h3>
          <table class="master-table mt-10">
            <thead>
              <tr>
                <th>NPM</th>
                <th>Nama</th>
                <th>Angkatan</th>
                <th style="text-align: right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in aslab" :key="a.id">
                <td>{{ a.mahasiswa?.npm ?? '-' }}</td>
                <td>{{ a.name }}</td>
                <td>{{ a.mahasiswa?.angkatan ?? '-' }}</td>
                <td style="text-align: right">
                  <button class="btn-link btn-link-danger" :disabled="busyId === a.id" @click="demote(a)">Kembalikan ke Mahasiswa</button>
                </td>
              </tr>
              <tr v-if="!aslab.length">
                <td colspan="4" style="text-align: center; color: #9aa0a6">Belum ada Aslab dari mahasiswa.</td>
              </tr>
            </tbody>
          </table>

          <!-- Kandidat mahasiswa -->
          <div class="flex-h between mt-30" style="align-items: flex-end">
            <h3>Kandidat Mahasiswa</h3>
            <input v-model="cari" class="form-ctrl input-border" style="max-width: 260px" placeholder="Cari nama / NPM" />
          </div>
          <table class="master-table mt-10">
            <thead>
              <tr>
                <th>NPM</th>
                <th>Nama</th>
                <th>Angkatan</th>
                <th style="text-align: right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in kandidatTampil" :key="m.id">
                <td>{{ m.mahasiswa?.npm ?? '-' }}</td>
                <td>{{ m.name }}</td>
                <td>{{ m.mahasiswa?.angkatan ?? '-' }}</td>
                <td style="text-align: right">
                  <button class="btn-link" :disabled="busyId === m.id" @click="promote(m)">Jadikan Aslab</button>
                </td>
              </tr>
              <tr v-if="!kandidatTampil.length">
                <td colspan="4" style="text-align: center; color: #9aa0a6">Tidak ada mahasiswa.</td>
              </tr>
            </tbody>
          </table>
          <PaginationBar v-model:page="page" :total-pages="totalPages" />
        </template>
      </div>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Panel Admin — Delegasi Aslab (mahasiswa ⇄ supervisor), Gate manage-users.
import { ref, computed, onMounted } from 'vue'
import { aslabService } from '@/services/aslab'
import { usePagination } from '@/composables/use-pagination'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuAdmin from '@/components/sidemenu-admin.vue'
import FooterComponent from '@/components/footer-component.vue'
import PaginationBar from '@/components/pagination-bar.vue'

const kandidat = ref([])
const aslab = ref([])
const loading = ref(false)
const listError = ref('')
const busyId = ref(null)
const cari = ref('')

const kandidatFiltered = computed(() => {
  const q = cari.value.trim().toLowerCase()
  if (!q) return kandidat.value
  return kandidat.value.filter(
    (m) => String(m.name ?? '').toLowerCase().includes(q) || String(m.mahasiswa?.npm ?? '').toLowerCase().includes(q),
  )
})
const { page, totalPages, pagedItems: kandidatTampil } = usePagination(kandidatFiltered, 10)

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await aslabService.list()
    kandidat.value = res.data.data.kandidat
    aslab.value = res.data.data.aslab
  } catch (err) {
    listError.value = extractError(err)
  } finally {
    loading.value = false
  }
}

async function promote(m) {
  if (!confirm(`Jadikan ${m.name} sebagai Asisten Lab (Supervisor)?`)) return
  busyId.value = m.id
  try {
    await aslabService.promote(m.id)
    await load()
  } catch (err) {
    alert(extractError(err))
  } finally {
    busyId.value = null
  }
}

async function demote(a) {
  if (!confirm(`Kembalikan ${a.name} menjadi Mahasiswa? Akses Supervisor akan dicabut.`)) return
  busyId.value = a.id
  try {
    await aslabService.demote(a.id)
    await load()
  } catch (err) {
    alert(extractError(err))
  } finally {
    busyId.value = null
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

onMounted(load)
</script>

<style scoped>
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
