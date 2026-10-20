
import { createRouter, createWebHistory } from 'vue-router'
import api from '../services/api'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
    },
    {
      path: '/',
      name: 'home',
      component: () => import('../App.vue'),
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const token = localStorage.getItem('auth_token')

  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }

  if (to.name === 'login' && token) {
    try {
      await api.get('/user')
      return { name: 'home' }
    } catch {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      return true
    }
  }

  if (to.meta.requiresAuth && token) {
    try {
      await api.get('/user')
    } catch {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      return { name: 'login' }
    }
  }

  return true
})

export default router
