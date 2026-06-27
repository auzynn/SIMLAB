<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

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

    <div class="main-container flex-h between">
      <SidemenuDosen />

      <div class="profil-container">
        <div>
          <h1>Biografi</h1>
          <div class="profil-title"></div>
        </div>

        <div class="mt-30">
          <p v-if="dosen?.biografi">{{ dosen.biografi }}</p>
          <p v-else-if="!loading">Biografi belum tersedia.</p>
        </div>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman detail biografi dosen — data dinamis dari /api/dosen/{id} (T2.10)
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuDosen from '@/components/sidemenu-dosen.vue'
import FooterComponent from '@/components/footer-component.vue'
import { dosenService } from '@/services/dosen'
import fotoFallback from '@/assets/foto-dosen/nur-widiyasono.jpg'

const route = useRoute()
const dosen = ref(null)
const loading = ref(true)

const fotoUrl = computed(() => dosen.value?.foto || dosen.value?.user?.avatar || fotoFallback)

// Bidang Minat = relasi master many-to-many → gabung nama jadi teks
const bidangMinat = computed(() => {
  const v = dosen.value?.bidang_minat
  return Array.isArray(v) && v.length ? v.map((b) => b.nama).join(', ') : ''
})

// Gabungan "Tempat, DD Bulan YYYY" — parse string Y-m-d manual agar bebas zona waktu
const ttl = computed(() => {
  const tempat = dosen.value?.tempat_lahir
  const tgl = formatTanggal(dosen.value?.tanggal_lahir)
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

onMounted(async () => {
  try {
    const res = await dosenService.get(route.params.id)
    dosen.value = res.data.data
  } catch {
    dosen.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.bio-table {
  width: 100%;
  margin-top: 20px;
  border-collapse: collapse;
}

/* 3 kolom: label · pemisah ":" · nilai — agar nilai yang membungkus tetap
   sejajar di bawah teks (bukan di bawah titik dua). */
.bio-table td {
  vertical-align: top;
  padding: 2px 0;
}

.bio-label {
  width: 30%;
  white-space: nowrap;
}

.bio-sep {
  width: 18px;
  padding-right: 8px;
}
</style>
