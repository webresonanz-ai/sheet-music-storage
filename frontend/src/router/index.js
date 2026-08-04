import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import AuthView from '../views/AuthView.vue'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: true }
    },
    {
      path: '/login',
      name: 'login',
      component: AuthView
    },
    {
      path: '/register',
      name: 'register',
      component: AuthView
    },
    {
      path: '/add',
      redirect: '/'
    },
    {
      path: '/edit/:id',
      redirect: '/'
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  const isAuthRoute = to.name === 'login' || to.name === 'register'

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (isAuthRoute && auth.isAuthenticated) {
    return { name: 'home' }
  }

  return true
})

export default router
