<template>
  <div>
    <JumbotronSmall />

    <div class="main-container flex-h between">
      <SidemenuProfile />

      <div class="flex-v profil-container">
        <p v-if="loading" class="mt-30">Memuat data...</p>

        <!-- ===== Kartu identitas Kepala Lab (jika ditautkan ke dosen) ===== -->
        <div v-else-if="dosen" class="kepala-card">
          <div class="kepala-banner">
            <span class="banner-label">KEPALA LABORATORIUM</span>
          </div>

          <div class="kepala-head">
            <div class="kepala-avatar">
              <img v-if="fotoUrl" :src="fotoUrl" :alt="nama" referrerpolicy="no-referrer" />
              <span v-else>{{ initials }}</span>
            </div>
            <h2>{{ nama }}</h2>
            <p class="kepala-sub">Kepala Laboratorium Riset KK JKF · Universitas Siliwangi</p>
          </div>

          <div class="kepala-stats">
            <div class="stat stat-accent">
              <div class="stat-num">{{ dosen.jabatan_fungsional || '—' }}</div>
              <div class="stat-label">Jabatan fungsional</div>
            </div>
            <div class="stat">
              <div class="stat-num">{{ dosen.nidn || '—' }}</div>
              <div class="stat-label">NIDN</div>
            </div>
            <div class="stat">
              <div class="stat-num">{{ bidangMinat.length }}</div>
              <div class="stat-label">Bidang minat</div>
            </div>
          </div>

          <div class="kepala-grid">
            <div class="info-item">
              <span class="info-label">Jenis kelamin</span>
              <span class="info-val">{{ dosen.jenis_kelamin || '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Tempat &amp; tanggal lahir</span>
              <span class="info-val">{{ ttl || '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Email</span>
              <span class="info-val info-link">{{ dosen.user?.email || '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Nomor telepon</span>
              <span class="info-val">{{ dosen.user?.no_telp || '—' }}</span>
            </div>
          </div>

          <div v-if="bidangMinat.length" class="kepala-bidang">
            <p class="bidang-title">Bidang minat</p>
            <div class="chips">
              <span v-for="b in bidangMinat" :key="b.id ?? b.nama" class="chip">{{ b.nama }}</span>
            </div>
          </div>
        </div>

        <!-- ===== Fallback: konten lama (markdown/HTML) bila belum ditautkan dosen ===== -->
        <template v-else-if="info">
          <div v-if="info.gambar" class="flex-h around">
            <div class="dosen-photo-250"><img :src="info.gambar" alt="Foto Kepala Lab" referrerpolicy="no-referrer" /></div>
          </div>
          <div v-if="info.judul" class="flex-h around mt-30"><h2>{{ info.judul }}</h2></div>
          <MarkdownContent :source="info.konten" class="mt-30" />
        </template>

        <p v-else class="mt-30">Konten belum tersedia.</p>
      </div>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Halaman Profil Kepala Lab — kartu identitas dari dosen tertaut (info_lab.dosen_id),
// fallback ke konten bebas (markdown/HTML) bila belum ditautkan (T2.9, 3_SDD.md 3.15).
import { computed } from 'vue'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuProfile from '@/components/sidemenu-profile.vue'
import FooterComponent from '@/components/footer-component.vue'
import MarkdownContent from '@/components/markdown-content.vue'
import { useInfoLab } from '@/composables/use-info-lab'

const { data: info, loading } = useInfoLab('kepala_lab')

const dosen = computed(() => info.value?.dosen ?? null)
const nama = computed(() => dosen.value?.user?.name ?? '')
const fotoUrl = computed(() => dosen.value?.foto || dosen.value?.user?.avatar || null)

const initials = computed(() => {
  const name = (nama.value || '').trim()
  if (!name) return '?'
  const real = name
    .split(/\s+/)
    .filter((w) => !w.includes('.'))
    .map((w) => w.replace(/[^A-Za-z]/g, ''))
    .filter(Boolean)
  if (!real.length) return name[0].toUpperCase()
  return (real[0][0] + (real[1]?.[0] ?? '')).toUpperCase()
})

const bidangMinat = computed(() => {
  const v = dosen.value?.bidang_minat
  return Array.isArray(v) ? v : []
})

const ttl = computed(() => {
  const tempat = dosen.value?.tempat_lahir
  const tgl = formatTanggal(dosen.value?.tanggal_lahir)
  if (tempat && tgl) return `${tempat}, ${tgl}`
  return tempat || tgl || ''
})

function formatTanggal(iso) {
  if (!iso) return ''
  const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
  const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number)
  if (!y || !m || !d) return ''
  return `${d} ${bulan[m - 1]} ${y}`
}
</script>

<style scoped>
.dosen-photo-250,
.dosen-photo-250 img {
  width: 190px;
  height: 190px;
}

/* ===== Kartu Kepala Lab ===== */
.kepala-card {
  width: 100%;
  max-width: 640px;
  margin: 0 auto;
  background-color: white;
  border-radius: 14px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.kepala-banner {
  height: 120px;
  background: linear-gradient(120deg, var(--bs-navy) 0%, #1f6fb2 55%, #2bb3a3 100%);
  display: flex;
  align-items: flex-start;
  padding: 16px 20px;
}
.banner-label {
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.8em;
  font-weight: 700;
  letter-spacing: 0.12em;
}

.kepala-head {
  text-align: center;
  padding: 0 24px 8px;
  margin-top: -52px;
}
.kepala-avatar {
  width: 96px;
  height: 96px;
  margin: 0 auto 12px;
  border-radius: 50%;
  border: 4px solid white;
  background-color: var(--bs-navy);
  color: #fff;
  font-size: 2em;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}
.kepala-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.kepala-head h2 {
  font-size: 1.25em;
  margin: 0;
}
.kepala-sub {
  color: #5f6368;
  font-size: 0.9em;
  margin-top: 4px;
}

.kepala-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1px;
  margin: 20px 24px 0;
  background-color: var(--bs-grey2, #e3e6ea);
  border: 1px solid var(--bs-grey2, #e3e6ea);
  border-radius: 10px;
  overflow: hidden;
}
.stat {
  background-color: white;
  padding: 16px 10px;
  text-align: center;
}
.stat-accent {
  border-top: 3px solid var(--bs-yellow);
}
.stat-num {
  font-weight: 700;
  font-size: 1.05em;
  color: var(--bs-navy);
  word-break: break-word;
}
.stat-accent .stat-num {
  color: var(--bs-yellow);
}
.stat-label {
  margin-top: 4px;
  font-size: 0.8em;
  color: #5f6368;
}

.kepala-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px 20px;
  padding: 22px 24px 4px;
}
.info-item {
  display: flex;
  flex-direction: column;
}
.info-label {
  font-size: 0.78em;
  color: #9aa0a6;
  margin-bottom: 2px;
}
.info-val {
  font-weight: 600;
  color: var(--bs-black);
  font-size: 0.95em;
  word-break: break-word;
}
.info-link {
  color: var(--bs-navy);
}

.kepala-bidang {
  padding: 14px 24px 26px;
}
.bidang-title {
  font-size: 0.82em;
  color: #9aa0a6;
  margin-bottom: 10px;
}
.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.chip {
  padding: 5px 14px;
  border-radius: 20px;
  background-color: #eef1f7;
  color: var(--bs-navy);
  font-size: 0.85em;
  font-weight: 600;
}

@media (max-width: 540px) {
  .kepala-grid {
    grid-template-columns: 1fr;
  }
}
</style>
