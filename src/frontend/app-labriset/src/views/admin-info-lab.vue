<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall title="Konten Info Lab" />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container flex-h between">
      <!-- ---------- SIDE MENU ---------- -->
      <SidemenuAdmin />
      <!-- ---------- SIDE MENU END ---------- -->

      <div class="profil-container">
        <div>
          <h1>Konten Informasi Lab</h1>
          <div class="profil-title"></div>
        </div>

        <p class="mt-30" style="max-width: 600px">
          Pilih halaman yang ingin disunting. Konten mendukung teks/markdown.
        </p>

        <!-- ---------- TAB PILIH TIPE ---------- -->
        <div class="tab-row mt-30">
          <button
            v-for="t in tipes"
            :key="t.key"
            class="tab"
            :class="{ 'tab-active': activeTipe === t.key }"
            @click="selectTipe(t.key)"
          >
            {{ t.label }}
          </button>
        </div>

        <!-- ---------- FORM KONTEN ---------- -->
        <p v-if="loading" class="mt-30">Memuat konten...</p>

        <form v-else class="info-form mt-30" @submit.prevent="save">
          <div class="form-row">
            <label>Judul</label>
            <input type="text" class="form-ctrl input-border" v-model="form.judul" />
          </div>

          <div class="form-row">
            <label>Gambar (URL)</label>
            <input type="text" class="form-ctrl input-border" v-model="form.gambar" placeholder="https://..." />
          </div>

          <div class="form-row">
            <label>Konten</label>
            <textarea v-model="form.konten" rows="10" required></textarea>
          </div>

          <p v-if="error" style="color: #c0392b">{{ error }}</p>
          <p v-if="success" style="color: #2e7d32">{{ success }}</p>

          <button type="submit" class="btn btn-navy-solid mt-20" style="width: auto; padding: 8px 28px" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan Konten' }}
          </button>
        </form>
        <!-- ---------- FORM KONTEN END ---------- -->
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Panel kelola konten info lab (Admin only) — sunting 4 tipe halaman informasi.
// Terhubung ke /api/info-lab/{tipe} (3_SDD.md 5.12). Otorisasi backend via Gate manage-info-lab.
import { ref, onMounted } from 'vue'
import { infoLabService } from '@/services/info-lab'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuAdmin from '@/components/sidemenu-admin.vue'
import FooterComponent from '@/components/footer-component.vue'

const tipes = [
  { key: 'beranda', label: 'Beranda' },
  { key: 'visi_misi', label: 'Visi & Misi' },
  { key: 'kepala_lab', label: 'Profil Kepala Lab' },
  { key: 'roadmap_kk', label: 'Roadmap Lab' },
]

const activeTipe = ref('beranda')
const form = ref({ judul: '', konten: '', gambar: '' })
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

async function loadTipe() {
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    const res = await infoLabService.get(activeTipe.value)
    const d = res.data.data
    form.value = { judul: d.judul || '', konten: d.konten || '', gambar: d.gambar || '' }
  } catch (err) {
    // Belum ada baris (404) → mulai dari form kosong, simpan akan membuatnya
    if (err.response?.status === 404) {
      form.value = { judul: '', konten: '', gambar: '' }
    } else {
      error.value = extractError(err)
    }
  } finally {
    loading.value = false
  }
}

function selectTipe(key) {
  if (key === activeTipe.value) return
  activeTipe.value = key
  loadTipe()
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    await infoLabService.update(activeTipe.value, form.value)
    success.value = 'Konten berhasil disimpan.'
  } catch (err) {
    error.value = extractError(err)
  } finally {
    saving.value = false
  }
}

// Ambil pesan error ramah dari respons Laravel (422 / 403)
function extractError(err) {
  const res = err.response?.data
  if (res?.errors) {
    const first = Object.values(res.errors)[0]
    if (Array.isArray(first) && first.length) return first[0]
  }
  return res?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

onMounted(loadTipe)
</script>

<style scoped>
.tab-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.tab {
  padding: 8px 18px;
  background-color: var(--bs-grey2);
  border: none;
  border-radius: 5px;
  cursor: pointer;
  color: var(--bs-navy);
}

.tab:hover {
  font-weight: bold;
}

.tab-active {
  font-weight: bold;
  border-bottom: 3px solid var(--bs-yellow);
}

.info-form {
  max-width: 640px;
}

.form-row {
  margin-bottom: 16px;
}

.form-row label {
  display: block;
  margin-bottom: 6px;
}

.form-row .form-ctrl {
  width: 100%;
}
</style>
