<template>
  <div class="side-menu-container">
    <div v-for="m in menuTampil" :key="m.to" class="menu-group">
      <router-link :to="m.to" class="menu" style="display: block" active-class="activemenu">{{ m.label }}</router-link>
    </div>
  </div>
</template>

<script setup>
// Menu samping Panel kelola. Ditautkan sesuai matriks RBAC (2_SRS.md Bagian 1 revisi):
// Admin melihat semua; Supervisor hanya area yang diizinkan gate-nya.
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const menu = [
  { to: '/admin', label: 'Dashboard', roles: ['admin', 'supervisor'] },
  { to: '/admin/users', label: 'Kelola User & Role', roles: ['admin'] },
  { to: '/admin/info-lab', label: 'Konten Informasi Lab', roles: ['admin', 'supervisor'] },
  { to: '/admin/data-master', label: 'Data Master', roles: ['admin', 'supervisor'] },
  { to: '/admin/aslab', label: 'Delegasi Aslab', roles: ['admin'] },
  { to: '/admin/sertifikasi', label: 'Katalog Sertifikasi', roles: ['admin', 'supervisor'] },
  { to: '/persetujuan-peminjaman', label: 'Persetujuan Peminjaman', roles: ['admin', 'supervisor'] },
  { to: '/report', label: 'Laporan', roles: ['admin', 'supervisor'] },
  { to: '/rekap-tugas', label: 'Rekap Tugas', roles: ['admin', 'supervisor', 'dosen'] },
]

const menuTampil = computed(() => menu.filter((m) => m.roles.includes(auth.user?.role)))
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
