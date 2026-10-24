import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { requiresAuth: false, title: 'Login' },
  },
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/pages/DashboardPage.vue'),
    meta: { requiresAuth: true, title: 'Dashboard' },
  },
  {
    path: '/products',
    name: 'products',
    component: () => import('@/pages/ProductListPage.vue'),
    meta: { requiresAuth: true, title: 'Products' },
  },
  {
    path: '/products/new',
    name: 'product-create',
    component: () => import('@/pages/ProductFormPage.vue'),
    meta: { requiresAuth: true, role: 'admin', title: 'Add Product' },
  },
  {
    path: '/products/:id',
    name: 'product-detail',
    component: () => import('@/pages/ProductDetailPage.vue'),
    meta: { requiresAuth: true, title: 'Product Detail' },
  },
  {
    path: '/products/:id/edit',
    name: 'product-edit',
    component: () => import('@/pages/ProductFormPage.vue'),
    meta: { requiresAuth: true, role: 'admin', title: 'Edit Product' },
  },
  {
    path: '/orders',
    name: 'orders',
    component: () => import('@/pages/OrderListPage.vue'),
    meta: { requiresAuth: true, title: 'Orders' },
  },
  {
    path: '/orders/new',
    name: 'order-create',
    component: () => import('@/pages/OrderFormPage.vue'),
    meta: { requiresAuth: true, title: 'New Order' },
  },
  {
    path: '/orders/:id',
    name: 'order-detail',
    component: () => import('@/pages/OrderDetailPage.vue'),
    meta: { requiresAuth: true, title: 'Order Detail' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.role === 'admin' && !authStore.isAdmin) {
    return { name: 'dashboard' }
  }
})

export default router
