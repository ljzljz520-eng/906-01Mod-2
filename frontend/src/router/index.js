import { createRouter, createWebHistory } from 'vue-router'
import SearchPage from '../views/SearchPage.vue'
import FavoritesPage from '../views/FavoritesPage.vue'
import MirrorRadarPage from '../views/MirrorRadarPage.vue'
import HistoryPage from '../views/HistoryPage.vue'

const routes = [
  {
    path: '/',
    name: 'Search',
    component: SearchPage
  },
  {
    path: '/radar',
    name: 'MirrorRadar',
    component: MirrorRadarPage,
    meta: { title: '镜像健康雷达' }
  },
  {
    path: '/history',
    name: 'History',
    component: HistoryPage
  },
  {
    path: '/favorites',
    name: 'Favorites',
    component: FavoritesPage
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
