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
          <router-link v-if="isMahasiswa" to="/tugas" class="btn btn-navy-border" style="width: auto; padding: 8px 20px">
            Kirim Tugas
          </router-link>
          <router-link v-if="isMahasiswa" to="/kelaslab/katalog" class="btn btn-navy-solid" style="width: auto; padding: 8px 20px">
            + Daftar Kelas Lab
          </router-link>
          <router-link v-if="bisaKelola" to="/tugas" class="btn btn-navy-border" style="width: auto; padding: 8px 20px">
            Tugas Masuk
          </router-link>
          <router-link v-if="bisaKelola" to="/rekap-tugas" class="btn btn-navy-border" style="width: auto; padding: 8px 20px">
            Rekap Tugas
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

      <!-- ===== Mahasiswa: Kelas Lab Saya (baris daftar) ===== -->
      <template v-else-if="isMahasiswa">
        <p class="mt-30" style="max-width: 680px">Sesi Kelas Lab/Praktikum yang Anda daftarkan beserta statusnya.</p>
        <div v-if="kelasSaya.length" class="sesi-list mt-20">
          <div v-for="k in kelasSaya" :key="k.id" class="sesi-row">
            <div class="sesi-main">
              <div class="sesi-title-line">
                <strong class="sesi-title">{{ k.mata_kuliah?.nama_mk }}</strong>
                <span :class="['status-badge', `status-${k.status_pendaftaran}`]">{{ statusLabel(k.status_pendaftaran) }}</span>
              </div>
              <div class="sesi-meta">
                <span>{{ k.nama_sesi }}</span>
                <span class="meta-sep">·</span>
                <span>{{ hariLabel(k.hari) }} {{ formatJam(k.jam_mulai) }}–{{ formatJam(k.jam_selesai) }}</span>
                <span class="meta-sep">·</span>
                <span>{{ k.ruangan?.nama_ruangan }}</span>
              </div>
            </div>
            <!-- Kolom tengah: aksi/keterangan pembatalan -->
            <div class="sesi-mid">
              <button
                v-if="k.status_pendaftaran === 'menunggu'"
                class="btn btn-navy-border sesi-btn-sm"
                :disabled="busyId === k.id"
                @click="batalkan(k)"
              >
                Batalkan Pendaftaran
              </button>
              <span v-else-if="k.status_pendaftaran === 'disetujui'" class="sesi-note-sm">
                Pembatalan hanya lewat dosen / asisten lab.
              </span>
            </div>
            <!-- Kolom kanan: tautan ke halaman Detail Kelas Lab (berisi list tugas) -->
            <div class="sesi-action">
              <router-link :to="`/kelaslab/${k.id}/detail`" class="sesi-detail-link">
                Lihat Detail Kelas Lab &rarr;
              </router-link>
            </div>
          </div>
        </div>
        <p v-else class="mt-20" style="color: #9aa0a6">
          Anda belum mendaftar Kelas Lab. Klik <strong>"+ Daftar Kelas Lab"</strong> untuk memilih sesi.
        </p>
      </template>

      <!-- ===== Dosen/Supervisor: katalog read-only (baris daftar) ===== -->
      <template v-else>
        <p class="mt-30" style="max-width: 680px">Daftar seluruh sesi Kelas Lab/Praktikum yang dibuka.</p>
        <div v-for="grup in grouped" :key="grup.mataKuliahId" class="mk-group mt-30">
          <h3>{{ grup.namaMk }}</h3>
          <div class="sesi-list mt-10">
            <div v-for="k in grup.sesi" :key="k.id" class="sesi-row">
              <div class="sesi-main">
                <div class="sesi-title-line">
                  <strong class="sesi-title">{{ k.nama_sesi }}</strong>
                </div>
                <div class="sesi-meta">
                  <span>{{ hariLabel(k.hari) }} {{ formatJam(k.jam_mulai) }}–{{ formatJam(k.jam_selesai) }}</span>
                  <span class="meta-sep">·</span>
                  <span>{{ k.ruangan?.nama_ruangan }}</span>
                  <span class="meta-sep">·</span>
                  <span>{{ k.dosen?.user?.name ?? '-' }}</span>
                </div>
              </div>
              <div class="sesi-mid">
                <span :class="['kuota-badge', { penuh: k.sisa_kuota <= 0 }]">Sisa {{ k.sisa_kuota }}/{{ k.kuota }}</span>
                <span :class="['tugas-badge', { kosong: !k.tugas_count }]">
                  {{ k.tugas_count ? `${k.tugas_count} pertemuan bertugas` : 'Belum ada tugas' }}
                </span>
                <template v-if="k.tugas_count">
                  <span v-if="rekap(k.id)?.status === 'perhatian'" class="pantau-badge perhatian">
                    ● Perlu perhatian · {{ rekap(k.id).tunggakan }} belum
                  </span>
                  <span v-else-if="rekap(k.id)?.status === 'berjalan'" class="pantau-badge berjalan">● Berjalan</span>
                  <span v-else class="pantau-badge beres">● Beres</span>
                </template>
              </div>
              <div class="sesi-action">
                <router-link :to="`/kelaslab/${k.id}/detail`" class="sesi-detail-link">
                  Lihat Detail Kelas Lab &rarr;
                </router-link>
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

// Rekap kepatuhan tugas per kelas (Opsi B) — dipetakan per kelas_lab_id untuk badge.
const rekapMap = ref({})
function rekap(id) {
  return rekapMap.value[id] || null
}

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
    // Muat rekap kepatuhan tugas untuk badge "Perlu perhatian / Beres".
    // Admin juga melihat katalog ini (bukan mahasiswa) & backend mengizinkannya —
    // sebelumnya hanya bisaKelola (dosen/supervisor) sehingga badge Admin selalu "Beres".
    if (!isMahasiswa.value) {
      try {
        const rk = await kelasLabService.rekapTugas()
        rekapMap.value = Object.fromEntries(rk.data.data.map((r) => [r.kelas_lab_id, r]))
      } catch {
        rekapMap.value = {}
      }
    }
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
/* Kolom tengah: tombol batal / keterangan pembatalan / badge kuota+tugas */
.sesi-mid {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 200px;
  text-align: center;
}
.tugas-badge {
  font-size: 0.78em;
  font-weight: 600;
  padding: 2px 10px;
  border-radius: 20px;
  color: var(--bs-navy);
  background-color: #eef1f7;
  white-space: nowrap;
}
.tugas-badge.kosong {
  color: #9aa0a6;
  background-color: #f0f2f4;
  font-weight: 500;
}
.pantau-badge {
  font-size: 0.76em;
  font-weight: 700;
  padding: 2px 10px;
  border-radius: 20px;
  white-space: nowrap;
}
.pantau-badge.perhatian {
  color: #c0392b;
  background-color: #fdecec;
}
.pantau-badge.berjalan {
  color: #3a5a8c;
  background-color: #e8eef7;
}
.pantau-badge.beres {
  color: #1e7e34;
  background-color: #e6f4ea;
}
.sesi-action {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  min-width: 180px;
  text-align: right;
}
.sesi-btn-sm {
  width: auto;
  padding: 6px 16px;
}
.sesi-note-sm {
  font-size: 0.78em;
  color: #5f6368;
}
.sesi-detail-link {
  font-size: 0.85em;
  font-weight: 600;
  color: var(--bs-navy);
  white-space: nowrap;
}
.sesi-detail-link:hover {
  text-decoration: underline;
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
  .sesi-mid,
  .sesi-action {
    justify-content: flex-start;
    min-width: 0;
    text-align: left;
  }
}
</style>
