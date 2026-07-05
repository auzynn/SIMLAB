// Konfigurasi router aplikasi
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('../views/home-page.vue')
  },
  {
    path: '/login',
    name: 'login',
    // Halaman login tampil penuh tanpa navbar (punya branding sendiri)
    meta: { hideHeader: true },
    component: () => import('../views/login-page.vue')
  },
  {
    path: '/auth/callback',
    name: 'auth-callback',
    // Penerima token redirect Google OAuth dari backend (lihat GoogleAuthController@callback)
    // Halaman transisi, tanpa navbar
    meta: { hideHeader: true },
    component: () => import('../views/auth-callback.vue')
  },
  {
    path: '/kepalalab',
    name: 'kepalalab',
    component: () => import('../views/kepala-lab.vue')
  },
  {
    path: '/visimisi',
    name: 'visimisi',
    component: () => import('../views/visi-misi.vue')
  },
  {
    path: '/listdosen',
    name: 'listdosen',
    component: () => import('../views/list-dosen.vue')
  },
  {
    path: '/roadmaplab',
    name: 'roadmaplab',
    component: () => import('../views/roadmap-lab.vue')
  },
  {
    path: '/detaildosen/:id',
    name: 'detaildosen',
    component: () => import('../views/detail-dosen.vue')
  },
  {
    path: '/credential',
    name: 'credential',
    // Katalog sertifikasi: informasi umum, dapat diakses publik tanpa login
    component: () => import('../views/credential-info.vue')
  },
  {
    path: '/publikasi',
    name: 'publikasi',
    component: () => import('../views/penelitian-publikasi.vue')
  },
  {
    path: '/buku',
    name: 'buku',
    component: () => import('../views/buku-info.vue')
  },
  {
    path: '/roadmapdosen',
    name: 'roadmapdosen',
    component: () => import('../views/roadmap-dosen.vue')
  },
  {
    path: '/jadwallab',
    name: 'jadwallab',
    // Jadwal peminjaman lab: semua role wajib login (minimal akses baca)
    component: () => import('../views/jadwal-lab.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/profil',
    name: 'profil',
    // Akun pribadi: data diri + atur/ubah password — semua role yang sudah login
    component: () => import('../views/profil-page.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin',
    name: 'admin',
    // Panel Admin: menu pusat kelola seluruh modul — khusus role admin (SRS Bagian 1)
    component: () => import('../views/admin-page.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    // Kelola User & Role — khusus role admin
    component: () => import('../views/admin-users.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/info-lab',
    name: 'admin-info-lab',
    // Kelola konten halaman informasi lab — khusus role admin
    component: () => import('../views/admin-info-lab.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/aslab',
    name: 'admin-aslab',
    // Delegasi Asisten Lab (mahasiswa → supervisor) — khusus Admin
    component: () => import('../views/admin-aslab.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/data-master',
    name: 'admin-data-master',
    // Kelola Data Master (Ruangan & Mata Kuliah) — Admin & Supervisor (Gate manage-master-data)
    component: () => import('../views/admin-data-master.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'supervisor'] }
  },
  {
    path: '/peminjaman-saya',
    name: 'peminjaman-saya',
    // Status pengajuan peminjaman ruangan milik sendiri — Mahasiswa (Dosen tidak meminjam ruangan)
    component: () => import('../views/peminjaman-saya.vue'),
    meta: { requiresAuth: true, roles: ['mahasiswa'] }
  },
  {
    path: '/persetujuan-peminjaman',
    name: 'persetujuan-peminjaman',
    // Approve/reject pengajuan peminjaman ruangan — Admin & Supervisor (Gate approve-peminjaman-ruangan)
    component: () => import('../views/persetujuan-peminjaman.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'supervisor'] }
  },
  {
    path: '/perangkat',
    name: 'perangkat',
    // Katalog inventaris perangkat lab — semua role yang login (Mahasiswa dapat mengajukan pinjam)
    component: () => import('../views/perangkat.vue'),
    meta: { requiresAuth: true }
  },
  {
    // Peminjaman perangkat kini menyatu ke "Peminjaman Saya" (tab Perangkat).
    // Redirect menjaga tautan/bookmark lama tetap berfungsi.
    path: '/peminjaman-perangkat',
    redirect: '/peminjaman-saya?tab=perangkat'
  },
  {
    // Persetujuan perangkat kini menyatu ke halaman Persetujuan Peminjaman (tab Perangkat).
    path: '/persetujuan-perangkat',
    redirect: '/persetujuan-peminjaman?tab=perangkat'
  },
  {
    path: '/kelaslab',
    name: 'kelaslab',
    // Daftar Kelas Lab/Praktikum + pendaftaran peserta — semua role yang login
    component: () => import('../views/kelas-lab.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/kelaslab/katalog',
    name: 'katalog-kelas-lab',
    // Katalog pendaftaran Kelas Lab (semua role lihat; Mahasiswa mendaftar)
    component: () => import('../views/katalog-kelas-lab.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/kelaslab/persetujuan',
    name: 'persetujuan-kelas-lab',
    // Persetujuan pendaftaran Kelas Lab — Dosen (kelas miliknya) / Supervisor
    component: () => import('../views/persetujuan-kelas-lab.vue'),
    meta: { requiresAuth: true, roles: ['dosen', 'supervisor'] }
  },
  {
    path: '/kelaslab/kelola',
    name: 'kelola-kelas-lab',
    // Buka & kelola Kelas Lab — Dosen (milik sendiri) atau Supervisor (atas nama dosen)
    component: () => import('../views/kelola-kelas-lab.vue'),
    meta: { requiresAuth: true, roles: ['dosen', 'supervisor'] }
  },
  {
    path: '/kelaslab/:id/peserta',
    name: 'peserta-kelas-lab',
    // Daftar peserta satu sesi Kelas Lab — pemilik (Dosen) atau Supervisor
    component: () => import('../views/peserta-kelas-lab.vue'),
    meta: { requiresAuth: true, roles: ['dosen', 'supervisor'] }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  // Navigasi baru selalu mulai dari atas; tombol back/forward memulihkan posisi tersimpan.
  // Tanpa ini, scroll halaman lama "terbawa" ke halaman berikutnya.
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { top: 0 }
  }
})

// Guard RBAC: cek autentikasi & role sebelum masuk halaman (acuan matriks SRS Bagian 1)
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Pulihkan sesi saat refresh: token ada tapi data user belum dimuat
  if (localStorage.getItem('token') && !auth.isAuthenticated) {
    await auth.fetchUser()
  }

  // Halaman butuh login tapi user belum login → arahkan ke login (simpan tujuan)
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Halaman dibatasi role tertentu tapi role user tidak cocok → kembali ke beranda
  if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
    return { name: 'home' }
  }

  // Sudah login tapi membuka /login → arahkan ke beranda
  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'home' }
  }
})

export default router
