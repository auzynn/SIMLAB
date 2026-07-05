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
