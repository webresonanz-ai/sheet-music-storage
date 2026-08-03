import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'

export const useSheetMusicStore = defineStore('sheetMusic', () => {
  const sheetMusicList = ref([])
  const loading = ref(false)
  const error = ref(null)

  const request = async (path, options = {}) => {
    const response = await fetch(`${API_BASE}${path}`, {
      headers: { 'Content-Type': 'application/json' },
      ...options
    })

    if (!response.ok) {
      const body = await response.json().catch(() => ({}))
      throw new Error(body.error || `Request failed (${response.status})`)
    }

    return response.json()
  }

  const loadFromApi = async () => {
    loading.value = true
    error.value = null
    try {
      sheetMusicList.value = await request('/sheet-music')
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  const addSheetMusic = async (data) => {
    const newItem = await request('/sheet-music', {
      method: 'POST',
      body: JSON.stringify(data)
    })
    sheetMusicList.value.unshift(newItem)
    return newItem
  }

  const updateSheetMusic = async (id, data) => {
    const updated = await request(`/sheet-music/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    })
    const index = sheetMusicList.value.findIndex(item => item.id === id)
    if (index !== -1) {
      sheetMusicList.value[index] = updated
    }
    return updated
  }

  const deleteSheetMusic = async (id) => {
    await request(`/sheet-music/${id}`, { method: 'DELETE' })
    sheetMusicList.value = sheetMusicList.value.filter(item => item.id !== id)
  }

  const getSheetMusicById = (id) => {
    return sheetMusicList.value.find(item => item.id === id)
  }

  const sortedSheetMusic = computed(() => {
    return [...sheetMusicList.value].sort((a, b) => b.createdAt.localeCompare(a.createdAt))
  })

  const totalCount = computed(() => sheetMusicList.value.length)

  return {
    sheetMusicList,
    loading,
    error,
    sortedSheetMusic,
    totalCount,
    loadFromApi,
    addSheetMusic,
    updateSheetMusic,
    deleteSheetMusic,
    getSheetMusicById
  }
})