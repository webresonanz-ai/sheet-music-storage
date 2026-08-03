import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useSheetMusicStore = defineStore('sheetMusic', () => {
  const sheetMusicList = ref([])
  const nextId = ref(1)

  const loadFromStorage = () => {
    const stored = localStorage.getItem('sheetMusicList')
    if (stored) {
      sheetMusicList.value = JSON.parse(stored)
      if (sheetMusicList.value.length > 0) {
        nextId.value = Math.max(...sheetMusicList.value.map(item => item.id)) + 1
      }
    }
  }

  const saveToStorage = () => {
    localStorage.setItem('sheetMusicList', JSON.stringify(sheetMusicList.value))
  }

  const addSheetMusic = (data) => {
    const newItem = {
      id: nextId.value++,
      ...data,
      createdAt: new Date().toISOString()
    }
    sheetMusicList.value.push(newItem)
    saveToStorage()
    return newItem
  }

  const updateSheetMusic = (id, data) => {
    const index = sheetMusicList.value.findIndex(item => item.id === id)
    if (index !== -1) {
      sheetMusicList.value[index] = {
        ...sheetMusicList.value[index],
        ...data,
        updatedAt: new Date().toISOString()
      }
      saveToStorage()
    }
  }

  const deleteSheetMusic = (id) => {
    sheetMusicList.value = sheetMusicList.value.filter(item => item.id !== id)
    saveToStorage()
  }

  const getSheetMusicById = (id) => {
    return sheetMusicList.value.find(item => item.id === id)
  }

  const sortedSheetMusic = computed(() => {
    return [...sheetMusicList.value].sort((a, b) => b.createdAt.localeCompare(a.createdAt))
  })

  const totalCount = computed(() => sheetMusicList.value.length)

  // Initialize from localStorage
  loadFromStorage()

  return {
    sheetMusicList,
    sortedSheetMusic,
    totalCount,
    addSheetMusic,
    updateSheetMusic,
    deleteSheetMusic,
    getSheetMusicById
  }
})