<template>
  <div>
    <JumbotronDefault />

    <div class="main-container">
      <div class="dash-wrap">
        <!-- ===== Kartu profil user (sesuai akun) ===== -->
        <section v-if="auth.isAuthenticated && user" class="card profil-card">
          <div class="profil-head">
            <div class="profil-avatar">
              <img v-if="user.avatar" :src="user.avatar" referrerpolicy="no-referrer" alt="Foto profil" />
              <span v-else>{{ userInisial }}</span>
            </div>
            <div>
              <div class="profil-hello">Selamat datang,</div>
              <div class="profil-name">{{ user.name }}</div>
              <span class="role-badge">{{ roleLabel }}</span>
            </div>
            <router-link to="/profil" class="profil-link">Profil Saya &rarr;</router-link>
          </div>
          <div class="profil-grid">
            <div v-for="f in profilFields" :key="f.label" class="pf-item">
              <span class="pf-label">{{ f.label }}</span>
              <span class="pf-value">{{ f.value }}</span>
            </div>
          </div>
        </section>

        <!-- ===== Strip pengajuan menunggu (Admin/Supervisor) ===== -->
        <router-link v-if="bisaApprove && pendingCount > 0" to="/persetujuan-peminjaman" class="pending-strip">
          <span class="pending-icon">!</span>
          <span><strong>{{ pendingCount }}</strong> pengajuan peminjaman menunggu persetujuan</span>
          <span class="pending-cta">Tinjau &rarr;</span>
        </router-link>

        <!-- ===== Baris 1: Jadwal Hari Ini | Kelas Lab | Kepala Lab ===== -->
        <div class="dash-3">
          <section class="card">
            <div class="card-head">
              <h3>Jadwal Hari Ini</h3>
              <span class="card-date">{{ hariIniLabel }}</span>
            </div>
            <ul v-if="jadwalHariIni.length" class="mini-list mt-10">
              <li v-for="j in jadwalHariIni" :key="j.key" :class="['mini-item', j.tipe]">
                <span class="mini-time">{{ j.jam }}</span>
                <div>
                  <div class="mini-title">{{ j.judul }}</div>
                  <div class="mini-sub">{{ j.sub }}</div>
                </div>
              </li>
            </ul>
            <p v-else class="mini-empty mt-10">{{ jadwalEmptyMsg }}</p>
          </section>

          <section class="card">
            <div class="card-head">
              <h3>{{ kelasCardTitle }}</h3>
              <router-link to="/kelaslab" class="card-link">Semua</router-link>
            </div>
            <ul v-if="kelasPreview.length" class="mini-list mt-10">
              <li v-for="k in kelasPreview" :key="k.id" class="mini-item kelas">
                <div style="flex: 1; min-width: 0">
                  <div class="mini-title">{{ k.mata_kuliah?.nama_mk }} — {{ k.nama_sesi }}</div>
                  <div class="mini-sub">{{ hariLabel(k.hari) }}, {{ formatJam(k.jam_mulai) }}–{{ formatJam(k.jam_selesai) }}</div>
                </div>
              </li>
            </ul>
            <p v-else class="mini-empty mt-10">{{ kelasEmptyMsg }}</p>
          </section>

          <section class="card kepala-card">
            <div class="card-head">
              <h3>Kepala Lab</h3>
              <router-link to="/kepalalab" class="card-link">Profil</router-link>
            </div>
            <template v-if="kepala">
              <div class="kepala-top mt-10">
                <div class="kepala-avatar">
                  <img v-if="kepalaFoto" :src="kepalaFoto" referrerpolicy="no-referrer" alt="Kepala Lab" />
                  <span v-else>{{ kepalaInisial }}</span>
                </div>
                <div>
                  <div class="kepala-nama">{{ kepala.user?.name }}</div>
                  <div class="kepala-jabatan">{{ kepala.jabatan_fungsional }}<span v-if="kepala.nidn"> · NIDN {{ kepala.nidn }}</span></div>
                </div>
              </div>
              <div v-if="kepalaBidang.length" class="kepala-chips">
                <span v-for="b in kepalaBidang" :key="b" class="chip">{{ b }}</span>
              </div>
            </template>
            <p v-else class="mini-empty mt-10">Data kepala lab belum tersedia.</p>
          </section>
        </div>

        <!-- ===== Baris 2: Pengumuman | Mitra ===== -->
        <div class="dash-2">
          <section class="card">
            <div class="card-head"><h3>Pengumuman</h3></div>
            <ul v-if="pengumuman.length" class="mini-list mt-10">
              <li v-for="(p, i) in pengumuman" :key="i" class="mini-item info">
                <div>
                  <div class="mini-title">{{ p.judul }}</div>
                  <div class="mini-sub">{{ p.isi }}</div>
                  <a v-if="p.lampiran && p.lampiran.url" :href="p.lampiran.url" target="_blank" rel="noopener" class="peng-attach">
                    &#128206; {{ p.lampiran.label || p.lampiran.url }}
                  </a>
                  <div v-if="p.tanggal" class="mini-meta">{{ formatTanggalId(p.tanggal) }}</div>
                </div>
              </li>
            </ul>
            <p v-else class="mini-empty mt-10">Belum ada pengumuman.</p>
          </section>

          <section class="card">
            <div class="card-head"><h3>Mitra Kerja Sama</h3></div>
            <div class="mitra-grid mt-10">
              <span v-for="m in mitra" :key="m" class="mitra-item">{{ m }}</span>
            </div>
          </section>
        </div>
      </div>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Beranda dashboard — kartu profil user + ringkasan jadwal, kelas lab, kepala lab, pengumuman & mitra.
// Data publik (kepala lab) dimuat untuk semua; data ber-auth aman-gagal untuk tamu.
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { peminjamanRuanganService } from '@/services/peminjaman-ruangan'
import { kelasLabService } from '@/services/kelas-lab'
import { infoLabService } from '@/services/info-lab'
import { formatJam, hariLabel, formatTanggalId } from '@/utils/format'
import JumbotronDefault from '@/components/jumbotron-default.vue'
import FooterComponent from '@/components/footer-component.vue'

const auth = useAuthStore()
const user = computed(() => auth.user)
const bisaApprove = computed(() => ['admin', 'supervisor'].includes(auth.user?.role))

const ROLE_LABEL = { admin: 'Administrator', supervisor: 'Supervisor', dosen: 'Dosen', mahasiswa: 'Mahasiswa' }
const roleLabel = computed(() => ROLE_LABEL[user.value?.role] ?? '-')
const userInisial = computed(() =>
  (user.value?.name || '?').split(' ').filter(Boolean).slice(0, 2).map((s) => s[0]).join('').toUpperCase(),
)

// Kolom profil menyesuaikan role akun yang login.
const profilFields = computed(() => {
  const u = user.value
  if (!u) return []
  if (u.role === 'mahasiswa') {
    const m = u.mahasiswa ?? {}
    return [
      { label: 'NIM', value: m.npm ?? '-' },
      { label: 'Program Studi', value: m.prodi ? `S1 ${m.prodi}` : '-' },
      { label: 'Angkatan', value: m.angkatan ?? '-' },
      { label: 'Status Mahasiswa', value: 'Aktif' },
    ]
  }
  if (u.role === 'dosen') {
    const d = u.dosen ?? {}
    return [
      { label: 'NIDN', value: d.nidn ?? '-' },
      { label: 'Jabatan Fungsional', value: d.jabatan_fungsional ?? '-' },
      { label: 'Email', value: u.email ?? '-' },
      { label: 'No. Telepon', value: u.no_telp ?? '-' },
    ]
  }
  return [
    { label: 'Peran', value: roleLabel.value },
    { label: 'Email', value: u.email ?? '-' },
    { label: 'No. Telepon', value: u.no_telp ?? '-' },
  ]
})

const HARI_ENUM = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']
const now = new Date()
const todayEnum = HARI_ENUM[now.getDay()]
const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
const todayStr = ymd(now)
const hariIniLabel = `${hariLabel(todayEnum)}, ${formatTanggalId(todayStr)}`
const tglStr = (t) => String(t).slice(0, 10)

const peminjaman = ref([])
const kelasLabJadwal = ref([])
const kelasList = ref([])
const pendingCount = ref(0)
const kepala = ref(null)

const pinjamItem = (p) => ({
  key: 'p' + p.id,
  tipe: 'pinjam',
  jam: `${formatJam(p.jam_mulai)}–${formatJam(p.jam_selesai)}`,
  judul: p.ruangan?.nama_ruangan ?? 'Ruangan',
  sub: `Peminjaman — ${p.user?.name ?? '-'}`,
})
const kelasItem = (k) => ({
  key: 'k' + k.id,
  tipe: 'kelas',
  jam: `${formatJam(k.jam_mulai)}–${formatJam(k.jam_selesai)}`,
  judul: `${k.mata_kuliah?.nama_mk ?? 'Kelas'} — ${k.nama_sesi}`,
  sub: k.ruangan?.nama_ruangan ?? '',
  hari: k.hari,
})

const jadwalHariIni = computed(() => {
  const kelas = kelasLabJadwal.value.filter((k) => k.hari === todayEnum).map(kelasItem)
  const pinjam = peminjaman.value.filter((p) => tglStr(p.tanggal) === todayStr).map(pinjamItem)
  return [...kelas, ...pinjam].sort((a, b) => a.jam.localeCompare(b.jam)).slice(0, 5)
})

// Kelas Lab: Mahasiswa → yang diikuti; Dosen → yang diampu; Admin/Supervisor → semua.
const kelasPreview = computed(() => {
  const u = auth.user
  let list = kelasList.value
  if (u?.role === 'mahasiswa') list = list.filter((k) => k.terdaftar)
  else if (u?.role === 'dosen') list = list.filter((k) => k.dosen_id === u.dosen?.id)
  return list.slice(0, 4)
})

const isGuest = computed(() => !auth.isAuthenticated)
const kelasCardTitle = computed(() => {
  if (auth.user?.role === 'mahasiswa') return 'Kelas Lab Saya'
  if (auth.user?.role === 'dosen') return 'Kelas Lab yang Diampu'
  return 'Kelas Lab / Praktikum'
})
const kelasEmptyMsg = computed(() => {
  if (isGuest.value) return 'Login untuk melihat Kelas Lab.'
  if (auth.user?.role === 'mahasiswa') return 'Anda belum mengikuti Kelas Lab.'
  if (auth.user?.role === 'dosen') return 'Anda belum mengampu Kelas Lab.'
  return 'Belum ada Kelas Lab dibuka.'
})
const jadwalEmptyMsg = computed(() => (isGuest.value ? 'Login untuk melihat jadwal.' : 'Tidak ada jadwal hari ini.'))

const kepalaFoto = computed(() => kepala.value?.foto || kepala.value?.user?.avatar || '')
const kepalaInisial = computed(() =>
  (kepala.value?.user?.name || '?').split(' ').filter(Boolean).slice(0, 2).map((s) => s[0]).join('').toUpperCase(),
)
const kepalaBidang = computed(() => (kepala.value?.bidang_minat || []).map((b) => b.nama).slice(0, 7))

// Pengumuman disunting Admin lewat panel Konten Info Lab (tipe `beranda`),
// disimpan sebagai JSON array [{judul, isi, tanggal}]. Dimuat publik saat halaman dibuka.
const pengumuman = ref([])
function parsePengumuman(raw) {
  if (!raw) return []
  try {
    const arr = JSON.parse(raw)
    return Array.isArray(arr) ? arr.filter((x) => x && (x.judul || x.isi)) : []
  } catch {
    return []
  }
}

const mitra = ['Microsoft', 'Oracle', 'Mikrotik', 'Pearson VUE', 'Red Hat', 'Cisco']

async function safe(promise) {
  try {
    return await promise
  } catch {
    return null
  }
}

onMounted(async () => {
  const [kepalaRes, pengRes] = await Promise.all([
    safe(infoLabService.get('kepala_lab')),
    safe(infoLabService.get('beranda')),
  ])
  if (kepalaRes) kepala.value = kepalaRes.data.data?.dosen ?? null
  if (pengRes) pengumuman.value = parsePengumuman(pengRes.data.data?.konten)

  if (auth.isAuthenticated) {
    const [kalRes, kelasRes] = await Promise.all([
      safe(peminjamanRuanganService.kalender()),
      safe(kelasLabService.list()),
    ])
    if (kalRes) {
      peminjaman.value = kalRes.data.data.peminjaman
      kelasLabJadwal.value = kalRes.data.data.kelas_lab
    }
    if (kelasRes) kelasList.value = kelasRes.data.data
    if (bisaApprove.value) {
      const pRes = await safe(peminjamanRuanganService.list())
      if (pRes) pendingCount.value = pRes.data.data.filter((p) => p.status === 'menunggu').length
    }
  }
})
</script>

<style scoped>
.dash-wrap {
  display: flex;
  flex-direction: column;
  gap: 22px;
}
.dash-3 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 22px;
  align-items: start;
}
.dash-2 {
  display: grid;
  grid-template-columns: 1.7fr 1fr;
  gap: 22px;
}
@media (max-width: 980px) {
  .dash-2,
  .dash-3 {
    grid-template-columns: 1fr;
  }
}

.card {
  background-color: #fff;
  border-radius: 12px;
  padding: 22px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  border: 1px solid var(--bs-grey2);
}
.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.card-head h3 {
  color: var(--bs-navy);
}
.card-date {
  font-size: 0.8em;
  color: #9aa0a6;
}
.card-link {
  font-size: 0.85em;
  font-weight: 600;
  color: var(--bs-yellow);
}

/* Kartu profil user */
.profil-head {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.profil-avatar {
  flex-shrink: 0;
  width: 58px;
  height: 58px;
  border-radius: 50%;
  overflow: hidden;
  background-color: var(--bs-navy);
  color: #fff;
  font-weight: 700;
  font-size: 1.2em;
  display: flex;
  align-items: center;
  justify-content: center;
}
.profil-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.profil-hello {
  font-size: 0.85em;
  color: #9aa0a6;
}
.profil-name {
  font-size: 1.25em;
  font-weight: 700;
  color: var(--bs-navy);
  line-height: 1.2;
}
.role-badge {
  display: inline-block;
  margin-top: 4px;
  padding: 2px 12px;
  border-radius: 20px;
  background-color: #eef1f7;
  color: var(--bs-navy);
  font-size: 0.75em;
  font-weight: 700;
}
.profil-link {
  margin-left: auto;
  font-size: 0.85em;
  font-weight: 600;
  color: var(--bs-yellow);
}
.profil-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px 24px;
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid var(--bs-grey2);
}
@media (max-width: 720px) {
  .profil-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
.pf-item {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.pf-label {
  font-size: 0.72em;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--bs-navy);
}
.pf-value {
  margin-top: 3px;
  color: #3c4043;
  font-size: 0.95em;
}

.mini-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.mini-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 10px 12px;
  border-radius: 8px;
  background-color: #f6f7f9;
  border-left: 4px solid var(--bs-navy);
}
.mini-item.pinjam {
  border-left-color: #ed8b00;
  background-color: #fff6e9;
}
.mini-item.info {
  border-left-color: var(--bs-yellow);
  background-color: #fffaf0;
}
.mini-time {
  flex-shrink: 0;
  font-size: 0.82em;
  font-weight: 700;
  color: var(--bs-navy);
  min-width: 88px;
}
.mini-title {
  font-weight: 600;
  color: var(--bs-navy);
  font-size: 0.92em;
}
.mini-sub {
  font-size: 0.82em;
  color: #5f6368;
  margin-top: 2px;
}
.mini-meta {
  font-size: 0.75em;
  color: #9aa0a6;
  margin-top: 4px;
}
.peng-attach {
  display: inline-block;
  margin-top: 4px;
  font-size: 0.8em;
  font-weight: 600;
  color: var(--bs-navy);
  text-decoration: underline;
  word-break: break-all;
}
.mini-empty {
  color: #9aa0a6;
  font-size: 0.9em;
}
.kuota-badge {
  flex-shrink: 0;
  align-self: center;
  font-size: 0.78em;
  font-weight: 700;
  padding: 2px 10px;
  border-radius: 20px;
  color: #1e7e34;
  background-color: #d4edda;
}
.kuota-badge.penuh {
  color: #c0392b;
  background-color: #f8d7da;
}

.pending-strip {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  border-radius: 10px;
  background-color: #fff3cd;
  border: 1px solid #ffe69c;
  color: #856404;
}
.pending-icon {
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background-color: #ed8b00;
  color: #fff;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pending-cta {
  margin-left: auto;
  font-weight: 700;
  color: var(--bs-navy);
}

.kepala-top {
  display: flex;
  gap: 14px;
  align-items: center;
}
.kepala-avatar {
  flex-shrink: 0;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  overflow: hidden;
  background-color: var(--bs-navy);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}
.kepala-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.kepala-nama {
  font-weight: 700;
  color: var(--bs-navy);
  font-size: 0.95em;
  line-height: 1.3;
}
.kepala-jabatan {
  font-size: 0.82em;
  color: #5f6368;
  margin-top: 2px;
}
.kepala-chips,
.mitra-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
/* Jarak jelas antara blok profil (nama/jabatan/NIDN) dan chip bidang minat. */
.kepala-chips {
  margin-top: 24px;
}
.chip {
  padding: 3px 12px;
  border-radius: 20px;
  background-color: #eef1f7;
  color: var(--bs-navy);
  font-size: 0.78em;
  font-weight: 600;
}
.mitra-item {
  padding: 8px 16px;
  border-radius: 8px;
  background-color: #f6f7f9;
  border: 1px solid var(--bs-grey2);
  color: #3c4043;
  font-weight: 600;
  font-size: 0.88em;
}
</style>
