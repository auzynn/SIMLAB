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
    component: () => import('../views/login-page.vue')
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
    path: '/detaildosen',
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
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
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
