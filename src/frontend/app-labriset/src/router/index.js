// Konfigurasi router aplikasi
import { createRouter, createWebHistory } from 'vue-router'

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
    component: () => import('../views/jadwal-lab.vue')
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
