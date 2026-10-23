import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as apiLogin, logout as apiLogout, getUser } from '@/api/auth.js'

export const useAuthStore = defineStore('auth', () => {
  const user    = ref(null)
  const token   = ref(localStorage.getItem('auth_token') ?? null)
  const loading = ref(false)
  const error   = ref(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin  = computed(() => user.value?.role === 'admin')
  const isStaff  = computed(() => user.value?.role === 'staff')

  async function login(credentials) {
    loading.value = true
    error.value   = null
    try {
      const res = await apiLogin(credentials)
      token.value = res.data.data.token
      user.value  = res.data.data.user
      localStorage.setItem('auth_token', token.value)
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Login failed.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await apiLogout() } catch { /* ignore */ }
    user.value  = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  async function fetchUser() {
    try {
      const res  = await getUser()
      user.value = res.data.data
    } catch {
      user.value = null
    }
  }

  return { user, token, loading, error, isAuthenticated, isAdmin, isStaff, login, logout, fetchUser }
})
