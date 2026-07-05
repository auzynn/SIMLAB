<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall title="Panel Admin" />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <!-- ---------- MENU KELOLA ---------- -->
    <div class="main-container">
      <div>
        <h1>Panel Admin</h1>
        <div class="profil-title"></div>
      </div>

      <p class="mt-30" style="max-width: 600px">
        Pusat kelola SIM Lab. Riset. Pilih area yang ingin dikelola di bawah ini.
      </p>

      <div class="admin-grid mt-30">
        <!-- Area aktif → kartu yang bisa diklik -->
        <component
          :is="area.to ? 'router-link' : 'div'"
          v-for="area in areas"
          :key="area.title"
          :to="area.to"
          class="admin-card"
          :class="{ 'admin-card-disabled': !area.to }"
        >
          <h3>{{ area.title }}</h3>
          <p>{{ area.desc }}</p>
          <span v-if="!area.to" class="badge-soon">Segera hadir</span>
        </component>
      </div>
    </div>
    <!-- ---------- MENU KELOLA END ---------- -->

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman menu Panel Admin — entri kelola seluruh modul (mengikuti matriks RBAC Admin, 2_SRS.md Bagian 1).
// Hanya "Kelola User" yang aktif; area lain menyusul saat modul backend-nya dibuat.
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'

const areas = [
  { title: 'Kelola User & Role', desc: 'Tambah, ubah role, dan hapus akun pengguna lintas role.', to: '/admin/users' },
  { title: 'Konten Informasi Lab', desc: 'Pengumuman, Visi-Misi, Profil Kepala Lab, dan Roadmap KK.', to: '/admin/info-lab' },
  { title: 'Data Master', desc: 'Kelola ruangan, mata kuliah/praktikum, dan bidang minat.', to: '/admin/data-master' },
  { title: 'Delegasi Aslab', desc: 'Tetapkan mahasiswa menjadi Asisten Lab (Supervisor).', to: '/admin/aslab' },
  { title: 'Persetujuan Peminjaman', desc: 'Setujui/tolak peminjaman ruangan & perangkat serta perpanjangan.', to: '/persetujuan-peminjaman' },
  { title: 'Katalog Sertifikasi', desc: 'Kelola katalog sertifikasi eksternal untuk mahasiswa.' },
  { title: 'Rekap Presensi', desc: 'Rekap kehadiran mahasiswa di laboratorium.' },
  { title: 'Laporan', desc: 'Rekap aktivitas lab dan unduh laporan PDF.' },
]
</script>

<style scoped>
.admin-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.admin-card {
  display: block;
  padding: 24px;
  background-color: white;
  border-radius: 8px;
  border-left: 6px solid var(--bs-navy);
  box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 0.1);
  color: var(--bs-black);
  transition: 0.2s ease-in;
}

.admin-card h3 {
  color: var(--bs-navy);
  margin-bottom: 8px;
}

.admin-card p {
  font-size: 0.95em;
  line-height: 1.4em;
}

/* Kartu aktif (router-link) terangkat saat hover */
.admin-card:not(.admin-card-disabled):hover {
  border-left-color: var(--bs-yellow);
  transform: translateY(-3px);
}

.admin-card-disabled {
  border-left-color: var(--bs-grey2);
  color: #9aa0a6;
  cursor: not-allowed;
}

.admin-card-disabled h3 {
  color: #9aa0a6;
}

.badge-soon {
  display: inline-block;
  margin-top: 12px;
  padding: 2px 10px;
  font-size: 0.75em;
  color: var(--bs-navy);
  background-color: var(--bs-grey2);
  border-radius: 20px;
}
</style>
