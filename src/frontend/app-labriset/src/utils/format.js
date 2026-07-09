// Util format tampilan untuk modul jadwal (Peminjaman Ruangan & Kelas Lab).

const BULAN = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

// '2026-09-07' → '7 September 2026'
export function formatTanggalId(tanggal) {
  if (!tanggal) return '-'
  const d = new Date(tanggal)
  if (Number.isNaN(d.getTime())) return tanggal
  return `${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`
}

// '08:00:00' / '08:00' → '08:00'
export function formatJam(jam) {
  if (!jam) return '-'
  return String(jam).slice(0, 5)
}

// 'senin' → 'Senin'
export function hariLabel(hari) {
  if (!hari) return '-'
  return hari.charAt(0).toUpperCase() + hari.slice(1)
}

const NAMA_HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

// '2026-06-30' → 'Senin' (nama hari dari tanggal, parse lokal agar tak bergeser timezone)
export function namaHari(tanggal) {
  if (!tanggal) return ''
  const [y, m, d] = String(tanggal).slice(0, 10).split('-').map(Number)
  if (!y || !m || !d) return ''
  return NAMA_HARI[new Date(y, m - 1, d).getDay()]
}

// Deadline (Date | 'YYYY-MM-DD HH:mm:ss' | ISO) → 'Selasa, 7 September 2026, 12:00'.
export function formatDeadline(dt) {
  if (!dt) return '-'
  let d = dt
  if (!(dt instanceof Date)) {
    const m = String(dt).match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/)
    if (!m) return '-'
    const [, y, mo, da, h, mi] = m.map(Number)
    d = new Date(y, mo - 1, da, h, mi)
  }
  if (Number.isNaN(d.getTime())) return '-'
  const jam = String(d.getHours()).padStart(2, '0')
  const menit = String(d.getMinutes()).padStart(2, '0')
  return `${NAMA_HARI[d.getDay()]}, ${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}, ${jam}:${menit}`
}

// Ubah deadline (wall-clock WIB 'YYYY-MM-DD HH:mm') → Date lokal (browser diasumsikan WIB).
function parseDeadline(dt) {
  if (!dt) return null
  const m = String(dt).match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/)
  if (!m) return null
  const [, y, mo, d, h, mi] = m.map(Number)
  return new Date(y, mo - 1, d, h, mi)
}

// Apakah deadline ('YYYY-MM-DD HH:mm:ss' / ISO / Date) sudah terlewati dari sekarang (waktu lokal WIB).
export function sudahLewatDeadline(dt) {
  const d = parseDeadline(dt)
  return d ? d.getTime() < Date.now() : false
}

// Apakah tugas dikirim setelah deadline. `createdAt` = timestamp kirim (ISO/UTC dari backend),
// `deadline` = wall-clock WIB. Keduanya dibandingkan sebagai instant absolut.
export function dikirimTerlambat(createdAt, deadline) {
  const dl = parseDeadline(deadline)
  if (!createdAt || !dl) return false
  const kirim = new Date(createdAt)
  return !Number.isNaN(kirim.getTime()) && kirim.getTime() > dl.getTime()
}

// Ubah datetime backend ('YYYY-MM-DD HH:mm:ss' / ISO) → nilai untuk <input type="datetime-local">.
export function toDatetimeLocal(dt) {
  if (!dt) return ''
  const m = String(dt).match(/(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/)
  return m ? `${m[1]}T${m[2]}` : ''
}

// Label status pengajuan + kelas warna badge (dipakai class status-<status>).
export function statusLabel(status) {
  return {
    menunggu: 'Menunggu',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
    dikembalikan: 'Dikembalikan',
  }[status] ?? status
}

// Label status perangkat (data master inventaris).
export function statusPerangkatLabel(status) {
  return {
    tersedia: 'Tersedia',
    dipinjam: 'Dipinjam',
    perbaikan: 'Perbaikan',
  }[status] ?? status
}
