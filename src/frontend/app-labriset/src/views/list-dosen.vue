<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container flex-h between">
      <!-- ---------- SIDE MENU ---------- -->
      <SidemenuProfile />
      <!-- ---------- SIDE MENU END ---------- -->

      <div class="profil-container">
        <div>
          <h1>Profil Dosen</h1>
          <div class="profil-title"></div>
        </div>

        <div class="flex-v mt-30">
          <div v-for="d in dosenList" :key="d.id" class="profil-dosen">
            <div class="dosen-photo-120">
              <img :src="fotoUrl(d)" :alt="d.user?.name" referrerpolicy="no-referrer" />
            </div>

            <div class="profil-desc flex-v between ml-30">
              <div>
                <h2>{{ d.user?.name }}</h2>
                <p>{{ deskripsi(d) }}</p>
              </div>
              <div class="flex-h between">
                <div class="publish-icon flex-h">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi" />
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi" />
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi" />
                </div>
                <div class="detail">
                  <router-link :to="`/detaildosen/${d.id}`">Detail</router-link>
                </div>
              </div>
            </div>
          </div>

          <p v-if="!loading && dosenList.length === 0" class="mt-30">Belum ada data dosen.</p>
        </div>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman daftar profil dosen — data dinamis dari /api/dosen (T2.10)
import { ref, onMounted } from 'vue'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuProfile from '@/components/sidemenu-profile.vue'
import FooterComponent from '@/components/footer-component.vue'
import { dosenService } from '@/services/dosen'
import fotoFallback from '@/assets/foto-dosen/nur-widiyasono.jpg'

const dosenList = ref([])
const loading = ref(true)

// Foto: kolom `foto` (disajikan dari public/), lalu avatar akun, terakhir fallback lokal
function fotoUrl(d) {
  return d.foto || d.user?.avatar || fotoFallback
}

// Subjudul kartu: gabungan Bidang Minat (relasi master) bila ada, jika tidak keterangan umum
function deskripsi(d) {
  const v = d.bidang_minat
  if (Array.isArray(v) && v.length) return v.map((b) => b.nama).join(', ')
  return 'Dosen Program Studi Informatika'
}

onMounted(async () => {
  try {
    const res = await dosenService.getAll()
    dosenList.value = res.data.data
  } catch {
    dosenList.value = []
  } finally {
    loading.value = false
  }
})
</script>
