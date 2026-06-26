<template>
  <div class="side-menu-container">
    <div class="menu-group">
      <router-link to="/admin" class="menu" style="display: block" active-class="activemenu">Dashboard</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/admin/users" class="menu" style="display: block" active-class="activemenu">Kelola User &amp; Role</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/admin/info-lab" class="menu" style="display: block" active-class="activemenu">Konten Informasi Lab</router-link>
    </div>

    <!-- Area kelola lain menyusul per fase — modul backend-nya belum dibuat -->
    <div v-for="item in segeraHadir" :key="item" class="menu-group">
      <span class="menu menu-disabled" title="Segera hadir">{{ item }}</span>
    </div>
  </div>
</template>

<script setup>
// Menu samping Panel Admin. Hanya "Kelola User" yang aktif;
// area lain ditandai disabled (segera hadir) sesuai matriks RBAC Admin (2_SRS.md Bagian 1).
const segeraHadir = [
  'Data Master',
  'Persetujuan Peminjaman',
  'Katalog Sertifikasi',
  'Rekap Presensi',
  'Laporan',
]
</script>

<style scoped>
.menu-disabled {
  display: block;
  color: #9aa0a6;
  cursor: not-allowed;
}

/* Tahan efek hover global .menu agar item disabled terlihat non-aktif */
.menu-disabled:hover {
  font-weight: normal;
  border-left: none;
}
</style>
