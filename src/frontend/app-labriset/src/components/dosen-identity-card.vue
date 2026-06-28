<template>
  <div class="main-container bg-grey">
    <div class="card-bio flex-h between">
      <div class="dosen-photo-250">
        <img :src="fotoUrl" :alt="dosen?.user?.name" referrerpolicy="no-referrer" />
      </div>

      <div class="side-table">
        <h2>{{ dosen?.user?.name }}</h2>
        <table class="bio-table">
          <tbody>
            <tr v-if="dosen?.jenis_kelamin">
              <td class="bio-label">Jenis Kelamin</td>
              <td class="bio-sep">:</td>
              <td>{{ dosen.jenis_kelamin }}</td>
            </tr>
            <tr v-if="dosen?.jabatan_fungsional">
              <td class="bio-label">Jabatan Fungsional</td>
              <td class="bio-sep">:</td>
              <td>{{ dosen.jabatan_fungsional }}</td>
            </tr>
            <tr v-if="dosen?.nidn">
              <td class="bio-label">NIDN</td>
              <td class="bio-sep">:</td>
              <td>{{ dosen.nidn }}</td>
            </tr>
            <tr v-if="ttl">
              <td class="bio-label">Tempat dan Tanggal Lahir</td>
              <td class="bio-sep">:</td>
              <td>{{ ttl }}</td>
            </tr>
            <tr v-if="dosen?.user?.email">
              <td class="bio-label">Email</td>
              <td class="bio-sep">:</td>
              <td>{{ dosen.user.email }}</td>
            </tr>
            <tr v-if="dosen?.user?.no_telp">
              <td class="bio-label">Nomor Telepon</td>
              <td class="bio-sep">:</td>
              <td>{{ dosen.user.no_telp }}</td>
            </tr>
            <tr v-if="bidangMinat">
              <td class="bio-label">Bidang Minat</td>
              <td class="bio-sep">:</td>
              <td>{{ bidangMinat }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
// Kartu identitas dosen — dipakai seragam di semua sub-halaman dosen
// (Biografi, Credential, Penelitian, Buku, Roadmap) agar tampilan & spasi konsisten.
import { computed } from 'vue'
import fotoFallback from '@/assets/foto-dosen/nur-widiyasono.jpg'

const props = defineProps({
  dosen: { type: Object, default: null },
})

const fotoUrl = computed(() => props.dosen?.foto || props.dosen?.user?.avatar || fotoFallback)

// Gabungan "Tempat, DD Bulan YYYY" — parse string Y-m-d manual agar bebas zona waktu
const ttl = computed(() => {
  const tempat = props.dosen?.tempat_lahir
  const tgl = formatTanggal(props.dosen?.tanggal_lahir)
  if (tempat && tgl) return `${tempat}, ${tgl}`
  return tempat || tgl || null
})

function formatTanggal(iso) {
  if (!iso) return null
  const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
  const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number)
  if (!y || !m || !d) return null
  return `${d} ${bulan[m - 1]} ${y}`
}

// Bidang Minat = relasi master many-to-many → gabung nama jadi teks
const bidangMinat = computed(() => {
  const v = props.dosen?.bidang_minat
  return Array.isArray(v) && v.length ? v.map((b) => b.nama).join(', ') : ''
})
</script>

<style scoped>
.bio-table {
  width: 100%;
  margin-top: 16px;
  border-collapse: collapse;
}

/* 3 kolom: label · ":" · nilai — nilai yang membungkus tetap sejajar di bawah teks.
   Kolom label & pemisah dibuat selebar isinya (width:1px + nowrap) agar jarak rapat. */
/* Padding atas/bawah saja (jangan shorthand) agar tak menimpa padding-right kolom.
   Selector .bio-table .bio-label/.bio-sep dibuat lebih spesifik dari .bio-table td. */
.bio-table td {
  vertical-align: top;
  padding-top: 3px;
  padding-bottom: 3px;
  line-height: 1.5;
}

.bio-table .bio-label {
  width: 1px;
  white-space: nowrap;
  padding-right: 32px;
}

.bio-table .bio-sep {
  width: 1px;
  padding-right: 8px;
}
</style>
