<template>
  <div>
    <JumbotronSmall title="Daftar Kelas Lab/Praktikum" />

    <div class="main-container">
      <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
        <div>
          <h1>Daftar Kelas Lab/Praktikum</h1>
          <div class="profil-title"></div>
        </div>
        <router-link to="/kelaslab" class="btn btn-navy-border" style="display: inline-block; width: auto; padding: 8px 20px">
          &larr; Kembali
        </router-link>
      </div>
      <p class="mt-30" style="max-width: 680px">
        Pilih sesi yang ingin diikuti. Pendaftaran menunggu persetujuan dosen/supervisor. Satu mata kuliah hanya boleh satu sesi.
      </p>

      <p v-if="loading" class="mt-30">Memuat data...</p>
      <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>

      <template v-else>
        <div v-for="grup in grouped" :key="grup.mataKuliahId" class="mk-group mt-30">
          <h3>{{ grup.namaMk }}</h3>
          <div class="sesi-list mt-10">
            <div v-for="k in grup.sesi" :key="k.id" class="sesi-row">
              <div class="sesi-main">
                <div class="sesi-title-line">
                  <strong class="sesi-title">{{ k.nama_sesi }}</strong>
                  <span :class="['kuota-badge', { penuh: k.sisa_kuota <= 0 }]">Sisa {{ k.sisa_kuota }}/{{ k.kuota }}</span>
                  <span v-if="k.status_pendaftaran" :class="['status-badge', `status-${k.status_pendaftaran}`]">{{ statusLabel(k.status_pendaftaran) }}</span>
                </div>
                <div class="sesi-meta">
                  <span>{{ hariLabel(k.hari) }} {{ formatJam(k.jam_mulai) }}–{{ formatJam(k.jam_selesai) }}</span>
                  <span class="meta-sep">·</span>
                  <span>{{ k.ruangan?.nama_ruangan }}</span>
                  <span class="meta-sep">·</span>
                  <span>{{ k.dosen?.user?.name ?? '-' }}</span>
                </div>
              </div>
              <div class="sesi-action">
                <button
                  v-if="isMahasiswa"
                  class="btn sesi-btn-sm"
                  :class="terdaftar.has(k.id) ? 'btn-navy-border' : 'btn-navy-solid'"
                  :disabled="busyId === k.id || terkunci(k) || (terdaftar.has(k.id) && k.status_pendaftaran === 'disetujui')"
                  @click="toggleDaftar(k)"
                >
                  {{ daftarLabel(k) }}
                </button>
              </div>
            </div>
          </div>
        </div>
        <p v-if="!items.length" class="mt-30" style="color: #9aa0a6">Belum ada Kelas Lab yang dibuka.</p>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Katalog pendaftaran Kelas Lab — Mahasiswa memilih & mendaftar sesi (status menunggu persetujuan).
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { kelasLabService } from '@/services/kelas-lab'
import { formatJam, hariLabel, statusLabel } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const auth = useAuthStore()
const isMahasiswa = computed(() => auth.user?.role === 'mahasiswa')

const items = ref([])
const loading = ref(false)
const listError = ref('')
const busyId = ref(null)
const terdaftar = ref(new Set())

const kelasSaya = computed(() => items.value.filter((k) => terdaftar.value.has(k.id)))

function sesiMatkulSama(k) {
  return kelasSaya.value.some((ks) => ks.id !== k.id && ks.mata_kuliah_id === k.mata_kuliah_id)
}
function bentrokJadwal(k) {
  return kelasSaya.value.some(
    (ks) => ks.id !== k.id && ks.hari === k.hari && ks.jam_mulai < k.jam_selesai && ks.jam_selesai > k.jam_mulai,
  )
}
function terkunci(k) {
  return !terdaftar.value.has(k.id) && (sesiMatkulSama(k) || bentrokJadwal(k) || k.sisa_kuota <= 0)
}

const grouped = computed(() => {
  const map = new Map()
  for (const k of items.value) {
    const id = k.mata_kuliah_id
    if (!map.has(id)) map.set(id, { mataKuliahId: id, namaMk: k.mata_kuliah?.nama_mk ?? 'Mata Kuliah', sesi: [] })
    map.get(id).sesi.push(k)
  }
  return [...map.values()]
})

function daftarLabel(k) {
  if (terdaftar.value.has(k.id)) return k.status_pendaftaran === 'disetujui' ? 'Terdaftar (disetujui)' : 'Batalkan Pendaftaran'
  if (sesiMatkulSama(k)) return 'Sudah ambil sesi matkul ini'
  if (bentrokJadwal(k)) return 'Bentrok jadwal'
  if (k.sisa_kuota <= 0) return 'Kuota Penuh'
  return 'Daftar'
}

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await kelasLabService.list()
    items.value = res.data.data
    terdaftar.value = new Set(items.value.filter((k) => k.terdaftar).map((k) => k.id))
  } catch (err) {
    listError.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

async function toggleDaftar(k) {
  const sesi = `${k.mata_kuliah?.nama_mk} — ${k.nama_sesi}`
  if (terdaftar.value.has(k.id)) {
    if (!confirm(`Batalkan pendaftaran dari ${sesi}?`)) return
  } else if (!confirm(`Daftar ke ${sesi}?\nPendaftaran akan menunggu persetujuan dosen/supervisor.`)) {
    return
  }

  busyId.value = k.id
  try {
    if (terdaftar.value.has(k.id)) {
      await kelasLabService.batalDaftar(k.id)
    } else {
      await kelasLabService.daftar(k.id)
    }
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Operasi gagal.')
  } finally {
    busyId.value = null
  }
}

onMounted(load)
</script>

<style scoped>
.mk-group h3 {
  color: var(--bs-navy);
  margin-bottom: 12px;
  font-size: 1.02em;
}

/* Baris daftar sesi: ringkas namun tetap detail & mudah dipindai */
.sesi-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.sesi-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 16px;
  background-color: white;
  border-radius: 8px;
  border-left: 4px solid var(--bs-navy);
  box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.06);
}
.sesi-main {
  min-width: 0;
  flex: 1;
}
.sesi-title-line {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.sesi-title {
  color: var(--bs-navy);
  font-size: 0.98em;
}
.sesi-meta {
  margin-top: 4px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  font-size: 0.85em;
  color: #5f6368;
}
.meta-sep {
  color: #cbd0d6;
}
.sesi-action {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  min-width: 180px;
}
.sesi-btn-sm {
  width: auto;
  padding: 6px 16px;
}
.kuota-badge {
  font-size: 0.8em;
  font-weight: 600;
  padding: 2px 10px;
  border-radius: 20px;
  color: #1e7e34;
  background-color: #d4edda;
  white-space: nowrap;
}
.kuota-badge.penuh {
  color: #c0392b;
  background-color: #f8d7da;
}
.status-badge {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.8em;
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

/* Layar sempit: baris menumpuk vertikal */
@media (max-width: 640px) {
  .sesi-row {
    flex-direction: column;
    align-items: stretch;
  }
  .sesi-action {
    justify-content: flex-start;
    min-width: 0;
  }
}
</style>
