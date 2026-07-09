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
    <div class="menu-group">
      <router-link to="/admin/data-master" class="menu" style="display: block" active-class="activemenu">Data Master</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/admin/aslab" class="menu" style="display: block" active-class="activemenu">Delegasi Aslab</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/admin/sertifikasi" class="menu" style="display: block" active-class="activemenu">Katalog Sertifikasi</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/persetujuan-peminjaman" class="menu" style="display: block" active-class="activemenu">Persetujuan Peminjaman</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/report" class="menu" style="display: block" active-class="activemenu">Laporan</router-link>
    </div>
    <div class="menu-group">
      <router-link to="/rekap-tugas" class="menu" style="display: block" active-class="activemenu">Rekap Tugas</router-link>
    </div>

    <!-- Area kelola lain menyusul per fase — modul backend-nya belum dibuat -->
    <div v-for="item in segeraHadir" :key="item" class="menu-group">
      <span class="menu menu-disabled" title="Segera hadir">{{ item }}</span>
    </div>
  </div>
</template>

<script setup>
// Menu samping Panel Admin. Area yang modul backend-nya sudah ada ditautkan;
// sisanya ditandai disabled (segera hadir) sesuai matriks RBAC Admin (2_SRS.md Bagian 1).
const segeraHadir = []
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
