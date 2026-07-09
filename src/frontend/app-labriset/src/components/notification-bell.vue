<template>
  <div class="bell-wrap">
    <!-- Ikon lonceng + badge jumlah belum dibaca -->
    <button class="bell-btn" title="Notifikasi" @click="toggle">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
      </svg>
      <span v-if="store.unreadCount > 0" class="bell-badge">{{ store.unreadCount > 99 ? '99+' : store.unreadCount }}</span>
    </button>

    <!-- Overlay penangkap klik-di-luar + panel -->
    <template v-if="open">
      <div class="bell-overlay" @click="open = false"></div>
      <div class="bell-panel">
        <div class="bell-head">
          <strong>Notifikasi</strong>
          <button v-if="store.unreadCount > 0" class="link-btn" @click="store.markAllRead()">Tandai semua dibaca</button>
        </div>

        <div v-if="store.items.length === 0" class="bell-empty">Belum ada notifikasi.</div>

        <ul v-else class="bell-list">
          <li v-for="n in store.items" :key="n.id" :class="{ unread: !n.is_read }">
            <span class="bell-item-ic" :class="iconMeta(n).cls">
              <component :is="iconMeta(n).comp" />
            </span>
            <div class="bell-item-main" @click="!n.is_read && store.markRead(n.id)">
              <div class="bell-item-title">{{ n.judul }}</div>
              <div class="bell-item-msg">{{ n.pesan }}</div>
              <div class="bell-item-time">{{ waktu(n.created_at) }}</div>
            </div>
            <button class="bell-del" title="Hapus" @click.stop="store.remove(n.id)">&times;</button>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
// Lonceng notifikasi navbar (FASE 9, SRS UC-07). Badge dari store (diseed via me()),
// daftar dimuat saat panel dibuka.
import { ref, watch, h } from 'vue'
import { useNotifikasiStore } from '@/stores/notifikasi'
import { useAuthStore } from '@/stores/auth'

const store = useNotifikasiStore()
const auth = useAuthStore()
const open = ref(false)

// Ikon garis (gaya Feather/Lucide, konsisten dengan ikon lonceng) — didefinisikan inline
// tanpa dependency. Lihat catatan di response: bisa diganti library `lucide-vue-next`.
const svg = (children) =>
  h('svg', { viewBox: '0 0 24 24', width: 18, height: 18, fill: 'none', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, children)

// Pengajuan masuk (ke approver/dosen) → inbox: "ada yang masuk untuk ditinjau".
const InboxIcon = () => svg([
  h('polyline', { points: '22 12 16 12 14 15 10 15 8 12 2 12' }),
  h('path', { d: 'M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z' }),
])
// Pendaftaran terkirim (konfirmasi ke mahasiswa) → clipboard bercentang.
const ClipboardCheckIcon = () => svg([
  h('path', { d: 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2' }),
  h('rect', { x: 9, y: 3, width: 6, height: 4, rx: 1 }),
  h('path', { d: 'm9 14 2 2 4-4' }),
])
// Status disetujui → check-circle (hijau).
const CheckCircleIcon = () => svg([
  h('path', { d: 'M22 11.08V12a10 10 0 1 1-5.93-9.14' }),
  h('polyline', { points: '22 4 12 14.01 9 11.01' }),
])
// Status ditolak → x-circle (merah).
const XCircleIcon = () => svg([
  h('circle', { cx: 12, cy: 12, r: 10 }),
  h('line', { x1: 15, y1: 9, x2: 9, y2: 15 }),
  h('line', { x1: 9, y1: 9, x2: 15, y2: 15 }),
])
// Pengingat tenggat → jam (amber): "batas waktu pengembalian".
const ClockIcon = () => svg([
  h('circle', { cx: 12, cy: 12, r: 10 }),
  h('polyline', { points: '12 6 12 12 16 14' }),
])

// Pemetaan tipe (+ konteks pesan) → ikon & warna badge yang sesuai isi notifikasi.
function iconMeta(n) {
  if (n.tipe === 'pengajuan_masuk') return { comp: InboxIcon, cls: 'ic-blue' }
  if (n.tipe === 'pendaftaran') return { comp: ClipboardCheckIcon, cls: 'ic-amber' }
  if (n.tipe === 'pengingat') return { comp: ClockIcon, cls: 'ic-amber' }
  // status_pengajuan: bedakan disetujui vs ditolak dari teks agar ikon cocok dengan pesan.
  const teks = `${n.judul || ''} ${n.pesan || ''}`.toLowerCase()
  if (teks.includes('ditolak')) return { comp: XCircleIcon, cls: 'ic-red' }
  return { comp: CheckCircleIcon, cls: 'ic-green' }
}

// Seed badge dari unread_notifications_count pada response me() saat user tersedia.
watch(
  () => auth.user?.unread_notifications_count,
  (count) => {
    if (typeof count === 'number') store.seed(count)
  },
  { immediate: true },
)

function toggle() {
  open.value = !open.value
  if (open.value) store.fetch()
}

// Format waktu ringkas Indonesia.
function waktu(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
  })
}
</script>

<style scoped>
.bell-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.bell-btn {
  position: relative;
  background: transparent;
  border: none;
  color: var(--bs-navy);
  cursor: pointer;
  padding: 6px;
  display: flex;
  align-items: center;
}

.bell-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  background-color: #dc2626;
  color: #fff;
  font-size: 0.7em;
  font-weight: 700;
  line-height: 18px;
  border-radius: 999px;
  text-align: center;
}

/* Overlay transparan menutup panel saat klik di luar */
.bell-overlay {
  position: fixed;
  inset: 0;
  z-index: 30;
}

.bell-panel {
  position: absolute;
  top: 100%;
  right: 0;
  width: 340px;
  max-height: 440px;
  overflow-y: auto;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
  z-index: 31;
}

.bell-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 14px;
  border-bottom: 1px solid var(--bs-grey2);
  position: sticky;
  top: 0;
  background-color: #fff;
}

.link-btn {
  background: none;
  border: none;
  color: var(--bs-navy);
  font-size: 0.82em;
  cursor: pointer;
  text-decoration: underline;
}

.bell-empty {
  padding: 24px 14px;
  color: #6b7280;
  text-align: center;
  font-size: 0.9em;
}

.bell-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.bell-list li {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 12px 14px;
  border-bottom: 1px solid var(--bs-grey2);
}

.bell-list li.unread {
  background-color: #eff6ff;
}

/* Badge ikon bulat per-tipe notifikasi (warna mengikuti isi pesan) */
.bell-item-ic {
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 1px;
}
.ic-blue {
  color: var(--bs-navy);
  background-color: #eef1f7;
}
.ic-green {
  color: #1e7e34;
  background-color: #e7f4ec;
}
.ic-red {
  color: #c0392b;
  background-color: #fdecea;
}
.ic-amber {
  color: #c47408;
  background-color: #fff6e9;
}

.bell-item-main {
  flex: 1;
  cursor: pointer;
  min-width: 0;
}

.bell-item-title {
  font-weight: 700;
  color: var(--bs-navy);
  font-size: 0.9em;
}

.bell-item-msg {
  font-size: 0.85em;
  color: #374151;
  margin: 2px 0;
}

.bell-item-time {
  font-size: 0.75em;
  color: #9ca3af;
}

.bell-del {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 1.2em;
  cursor: pointer;
  line-height: 1;
}

.bell-del:hover {
  color: #dc2626;
}
</style>
