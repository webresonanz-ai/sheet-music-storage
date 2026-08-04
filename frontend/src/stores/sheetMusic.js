import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'

export const useSheetMusicStore = defineStore('sheetMusic', () => {
  const sheetMusicList = ref([])
  const loading = ref(false)
  const error = ref(null)
  const meta = ref({ total: 0, page: 1, pageSize: 8, totalPages: 1 })
  const genreCounts = ref({})
  const stats = ref({ total: 0, uniqueComposers: 0, erasCovered: 0, minYear: null, maxYear: null })

  const currentParams = ref({ search: '', genre: '', sort: 'newest', page: 1 })

  const authHeaders = () => {
    const auth = useAuthStore()
    return auth.token ? { Authorization: `Bearer ${auth.token}` } : {}
  }

  const request = async (path, options = {}) => {
    const response = await fetch(`${API_BASE}${path}`, {
      headers: { 'Content-Type': 'application/json', ...authHeaders(), ...options.headers },
      ...options
    })

    if (!response.ok) {
      const body = await response.json().catch(() => ({}))
      throw new Error(body.error || `Request failed (${response.status})`)
    }

    return response.json()
  }

  const buildQuery = (params = {}) => {
    const merged = { ...currentParams.value, ...params }
    currentParams.value = merged
    const query = new URLSearchParams()
    if (merged.search) query.set('search', merged.search)
    if (merged.genre) query.set('genre', merged.genre)
    if (merged.sort) query.set('sort', merged.sort)
    query.set('page', String(merged.page))
    query.set('page_size', String(meta.value.pageSize || 8))
    return query.toString()
  }

  const loadFromApi = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const result = await request(`/api/sheet-music?${buildQuery(params)}`)
      sheetMusicList.value = result.data
      meta.value = result.meta
      genreCounts.value = result.counts.genres
      stats.value = result.stats
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  const refresh = async () => {
    await loadFromApi({ page: currentParams.value.page })
    if (meta.value.total > 0 && currentParams.value.page > meta.value.totalPages) {
      await loadFromApi({ page: meta.value.totalPages })
    }
  }

  const addSheetMusic = async (data) => {
    const newItem = await request('/api/sheet-music', {
      method: 'POST',
      body: JSON.stringify(data)
    })
    currentParams.value.page = 1
    await refresh()
    return newItem
  }

  const updateSheetMusic = async (id, data) => {
    const updated = await request(`/api/sheet-music/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    })
    await refresh()
    return updated
  }

  const deleteSheetMusic = async (id) => {
    await request(`/api/sheet-music/${id}`, { method: 'DELETE' })
    await refresh()
  }

  const getSheetMusicById = (id) => {
    return sheetMusicList.value.find(item => item.id === id)
  }

  const uploadScoreImage = async (file) => {
    const formData = new FormData()
    formData.append('scoreImage', file)
    const response = await fetch(`${API_BASE}/api/uploads/score-img`, {
      method: 'POST',
      headers: authHeaders(),
      body: formData
    })
    if (!response.ok) {
      const body = await response.json().catch(() => ({}))
      throw new Error(body.error || `Upload failed (${response.status})`)
    }
    return response.json()
  }

  const sortedSheetMusic = computed(() => {
    return [...sheetMusicList.value].sort((a, b) => b.createdAt.localeCompare(a.createdAt))
  })

  const totalCount = computed(() => meta.value.total)

  return {
    sheetMusicList,
    loading,
    error,
    meta,
    genreCounts,
    stats,
    sortedSheetMusic,
    totalCount,
    currentParams,
    loadFromApi,
    refresh,
    addSheetMusic,
    updateSheetMusic,
    deleteSheetMusic,
    uploadScoreImage,
    getSheetMusicById
  }
})