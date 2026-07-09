<template>
  <div>
    <JumbotronSmall title="Katalog Sertifikasi" />

    <div class="main-container">
      <div>
        <h1>Katalog Sertifikasi</h1>
        <div class="profil-title"></div>
      </div>

      <p class="mt-30" style="max-width: 680px">
        Informasi sertifikasi &amp; pelatihan eksternal (Mikrotik, Cisco, Oracle, EC-Council, dll)
        yang relevan sebagai referensi mahasiswa. SIM Lab. Riset hanya menampilkan informasi —
        pendaftaran dilakukan langsung ke pihak penyelenggara melalui tautan yang tersedia.
      </p>

      <p v-if="loading" class="mt-30">Memuat data...</p>
      <p v-else-if="listError" class="mt-30" style="color: #c0392b">{{ listError }}</p>

      <template v-else>
        <div v-if="!items.length" class="mt-30" style="color: #9aa0a6">
          Belum ada sertifikasi yang tercatat.
        </div>

        <div v-else class="cert-grid mt-30">
          <article v-for="s in items" :key="s.id" class="cert-card">
            <h3>{{ s.nama_sertifikasi }}</h3>
            <p class="penyelenggara">{{ s.penyelenggara }}</p>

            <dl class="cert-meta">
              <template v-if="s.jadwal">
                <dt>Jadwal</dt>
                <dd>{{ s.jadwal }}</dd>
              </template>
              <template v-if="s.persyaratan">
                <dt>Persyaratan</dt>
                <dd>{{ s.persyaratan }}</dd>
              </template>
            </dl>

            <a
              v-if="s.tautan_pendaftaran"
              :href="s.tautan_pendaftaran"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-navy-solid cert-link"
            >
              Info Pendaftaran ↗
            </a>
          </article>
        </div>
      </template>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Katalog sertifikasi eksternal (semua role login) — murni informasional (SRS UC-05).
import { ref, onMounted } from 'vue'
import { sertifikasiService } from '@/services/sertifikasi'
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const items = ref([])
const loading = ref(false)
const listError = ref('')

async function load() {
  loading.value = true
  listError.value = ''
  try {
    const res = await sertifikasiService.list()
    items.value = res.data.data
  } catch (err) {
    listError.value = err.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.cert-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.cert-card {
  display: flex;
  flex-direction: column;
  padding: 24px;
  background-color: white;
  border-radius: 8px;
  border-left: 6px solid var(--bs-navy);
  box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 0.1);
}

.cert-card h3 {
  color: var(--bs-navy);
  margin-bottom: 4px;
}

.penyelenggara {
  font-weight: 600;
  color: var(--bs-yellow);
  margin-bottom: 12px;
}

.cert-meta {
  margin: 0 0 16px;
  flex: 1;
}

.cert-meta dt {
  font-weight: 700;
  font-size: 0.85em;
  color: #5f6368;
  margin-top: 10px;
}

.cert-meta dd {
  margin: 2px 0 0;
  line-height: 1.4em;
}

.cert-link {
  width: auto;
  align-self: flex-start;
  padding: 8px 20px;
}
</style>
