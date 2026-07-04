<template>
  <div>
    <JumbotronSmall title="Kelas Lab/Praktikum" />

    <div class="main-container">
      <div class="flex-h between" style="align-items: flex-start; gap: 12px; flex-wrap: wrap">
        <div>
          <h1>Kelas Lab/Praktikum</h1>
          <div class="profil-title"></div>
        </div>
        <div class="flex-h" style="gap: 12px; flex-wrap: wrap">
          <router-link v-if="isMahasiswa" to="/kelaslab/katalog" class="btn btn-navy-solid" style="width: auto; padding: 8px 20px">
            + Daftar Kelas Lab
          </router-link>
          <router-link v-if="bisaKelola" to="/kelaslab/persetujuan" class="btn btn-navy-border" style="width: auto; padding: 8px 20px">
            Persetujuan Pendaftaran
          </router-link>
          <router-link v-if="bisaKelola" to="/kelaslab/kelola" class="btn btn-navy-solid" style="width: auto; padding: 8px 20px">
            Kelola Kelas Lab
          </router-link>
        </div>
      </div>

      <p v-if="loading" class="mt-30">Memuat data...</p>
      <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>

      <!-- ===== Mahasiswa: Kelas Lab Saya ===== -->
      <template v-else-if="isMahasiswa">
        <p class="mt-30" style="max-width: 680px">Sesi Kelas Lab/Praktikum yang Anda daftarkan beserta statusnya.</p>
        <div v-if="kelasSaya.length" class="sesi-grid mt-20">
          <div v-for="k in kelasSaya" :key="k.id" class="sesi-card">
            <div class="flex-h between">
              <strong>{{ k.mata_kuliah?.nama_mk }}</strong>
              <span :class="['status-badge', `status-${k.status_pendaftaran}`]">{{ statusLabel(k.status_pendaftaran) }}</span>
            </div>
            <p class="sesi-info"><span class="sesi-label">Sesi:</span> {{ k.nama_sesi }}</p>
            <p class="sesi-info"><span class="sesi-label">Jadwal:</span> {{ hariLabel(k.hari) }}, {{ formatJam(k.jam_mulai) }}–{{ formatJam(k.jam_selesai) }}</p>
            <p class="sesi-info"><span class="sesi-label">Ruangan:</span> {{ k.ruangan?.nama_ruangan }}</p>
            <button
              v-if="k.status_pendaftaran === 'menunggu'"
              class="btn btn-navy-border sesi-btn"
              style="width: 100%; padding: 8px"
              :disabled="busyId === k.id"
              @click="batalkan(k)"
            >
              Batalkan Pendaftaran
            </button>
            <p v-else-if="k.status_pendaftaran === 'disetujui'" class="sesi-note">
              Sudah disetujui — pembatalan hanya lewat dosen/supervisor.
            </p>
          </div>
        </div>
        <p v-else class="mt-20" style="color: #9aa0a6">
          Anda belum mendaftar Kelas Lab. Klik <strong>"+ Daftar Kelas Lab"</strong> untuk memilih sesi.
        </p>
      </template>

      <!-- ===== Dosen/Supervisor: katalog read-only ===== -->
      <template v-else>
        <p class="mt-30" style="max-width: 680px">Daftar seluruh sesi Kelas Lab/Praktikum yang dibuka.</p>
        <div v-for="grup in grouped" :key="grup.mataKuliahId" class="mk-group mt-30">
          <h3>{{ grup.namaMk }}</h3>
          <div class="sesi-grid mt-10">
            <div v-for="k in grup.sesi" :key="k.id" class="sesi-card">
              <div class="flex-h between">
                <strong>{{ k.nama_sesi }}</strong>
                <span :class="['kuota-badge', { penuh: k.sisa_kuota <= 0 }]">Sisa {{ k.sisa_kuota }}/{{ k.kuota }}</span>
              </div>
              <p class="sesi-info"><span class="sesi-label">Jadwal:</span> {{ hariLabel(k.hari) }}, {{ formatJam(k.jam_mulai) }}–{{ formatJam(k.jam_selesai) }}</p>
              <p class="sesi-info"><span class="sesi-label">Ruangan:</span> {{ k.ruangan?.nama_ruangan }}</p>
              <p class="sesi-info"><span class="sesi-label">Pengampu:</span> {{ k.dosen?.user?.name ?? '-' }}</p>
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
// Landing Kelas Lab: Mahasiswa → "Kelas Lab Saya" (status pendaftaran) + tombol ke katalog;
// Dosen/Supervisor → katalog read-only + tombol Kelola & Persetujuan Pendaftaran.
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { kelasLabService } from '@/services/kelas-lab'
import { formatJam, hariLabel, statusLabel } from '@/utils/format'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const auth = useAuthStore()
const isMahasiswa = computed(() => auth.user?.role === 'mahasiswa')
const bisaKelola = computed(() => ['dosen', 'supervisor'].includes(auth.user?.role))

const items = ref([])
const loading = ref(false)
const listError = ref('')
const busyId = ref(null)

// Pendaftaran milik mahasiswa ini (status menunggu/disetujui/ditolak).
const kelasSaya = computed(() => items.value.filter((k) => k.status_pendaftaran))

const grouped = computed(() => {
  const map = new Map()
  for (const k of items.value) {
    const id = k.mata_kuliah_id
    if (!map.has(id)) map.set(id, { mataKuliahId: id, namaMk: k.mata_kuliah?.nama_mk ?? 'Mata Kuliah', sesi: [] })
    map.get(id).sesi.push(k)
  }
  return [...map.values()]
})

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await kelasLabService.list()
    items.value = res.data.data
  } catch (err) {
    listError.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

async function batalkan(k) {
  if (!confirm(`Batalkan pendaftaran dari ${k.mata_kuliah?.nama_mk} — ${k.nama_sesi}?`)) return
  busyId.value = k.id
  try {
    await kelasLabService.batalDaftar(k.id)
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
  margin-bottom: 16px;
}
.sesi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}
.sesi-card {
  padding: 18px 18px 14px;
  background-color: white;
  border-radius: 8px;
  border-left: 5px solid var(--bs-navy);
  box-shadow: 3px 3px 8px rgba(0, 0, 0, 0.08);
}
.sesi-card > .flex-h {
  margin-bottom: 10px;
}
.sesi-btn {
  margin-top: 18px;
}
.sesi-note {
  margin-top: 18px;
  font-size: 0.82em;
  color: #5f6368;
  text-align: center;
}
.sesi-info {
  font-size: 0.9em;
  color: #3c4043;
  margin-top: 6px;
  line-height: 1.4;
}
.sesi-label {
  font-weight: 600;
  color: var(--bs-navy);
  margin-right: 2px;
}
.kuota-badge {
  font-size: 0.8em;
  font-weight: 600;
  padding: 2px 10px;
  border-radius: 20px;
  color: #1e7e34;
  background-color: #d4edda;
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
</style>
