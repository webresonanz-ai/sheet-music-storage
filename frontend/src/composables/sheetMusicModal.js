import { reactive } from 'vue'

// Module-scoped state so the modal can be opened from anywhere (navbar,
// dashboard hero, table actions) without a router or an event bus.
const state = reactive({
  open: false,
  mode: 'add', // 'add' | 'edit'
  item: null   // the piece being edited; null when adding
})

export function useSheetMusicModal() {
  const openAdd = () => {
    state.mode = 'add'
    state.item = null
    state.open = true
  }

  const openEdit = (item) => {
    state.mode = 'edit'
    state.item = item
    state.open = true
  }

  const closeModal = () => {
    state.open = false
  }

  return {
    state,
    openAdd,
    openEdit,
    closeModal
  }
}