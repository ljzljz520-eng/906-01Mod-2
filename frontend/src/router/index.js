import { createRouter, createWebHistory } from 'vue-router'
import SearchPage from '../views/SearchPage.vue'
import FavoritesPage from '../views/FavoritesPage.vue'

const routes = [
  {
    path: '/',
    name: 'Search',
    component: SearchPage
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
