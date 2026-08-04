import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
const TOKEN_KEY = 'sheetMusicVault.token'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY) || '')
  const user = ref(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  const request = async (path, options = {}) => {
    const response = await fetch(`${API_BASE}${path}`, {
      headers: { 'Content-Type': 'application/json', ...options.headers },
      ...options
    })

    if (!response.ok) {
      const body = await response.json().catch(() => ({}))
      const err = new Error(body.error || `Request failed (${response.status})`)
      err.fields = body.fields || null
      err.status = response.status
      throw err
    }

    return response.json()
  }

  const setSession = (session) => {
    token.value = session.token
    user.value = session.user
    localStorage.setItem(TOKEN_KEY, session.token)
  }

  const register = async ({ name, email, password }) => {
    loading.value = true
    try {
      const session = await request('/api/auth/register', {
        method: 'POST',
        body: JSON.stringify({ name, email, password })
      })
      setSession(session)
      return session
    } finally {
      loading.value = false
    }
  }

  const login = async ({ email, password }) => {
    loading.value = true
    try {
      const session = await request('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password })
      })
      setSession(session)
      return session
    } finally {
      loading.value = false
    }
  }

  const fetchMe = async () => {
    if (!token.value) return null
    try {
      const { user: me } = await request('/api/auth/me', {
        headers: { Authorization: `Bearer ${token.value}` }
      })
      user.value = me
      return me
    } catch (e) {
      if (e.status === 401) logout()
      throw e
    }
  }

  const logout = async () => {
    const current = token.value
    token.value = ''
    user.value = null
    localStorage.removeItem(TOKEN_KEY)

    if (current) {
      try {
        await request('/api/auth/logout', {
          method: 'POST',
          headers: { Authorization: `Bearer ${current}` }
        })
      } catch {
        // Best-effort: local session is cleared regardless of network outcome.
      }
    }
  }

  return {
    token,
    user,
    loading,
    isAuthenticated,
    isAdmin,
    register,
    login,
    fetchMe,
    logout
  }
})
