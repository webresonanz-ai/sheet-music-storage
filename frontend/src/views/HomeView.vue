<template>
  <div class="container">
    <!-- Header Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card-custom p-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
              <h1 class="mb-2 fw-bold">
                <i class="bi bi-collection-play me-2"></i>My Sheet Music Collection
              </h1>
              <p class="text-muted mb-0">
                {{ store.totalCount }} {{ store.totalCount === 1 ? 'piece' : 'pieces' }} in your collection
              </p>
            </div>
            <router-link to="/add" class="btn btn-primary btn-lg mt-3 mt-md-0">
              <i class="bi bi-plus-lg me-2"></i>Add New Sheet Music
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Filter -->
    <div class="row mb-4">
      <div class="col-md-6 mb-3 mb-md-0">
        <div class="input-group">
          <span class="input-group-text bg-white">
            <i class="bi bi-search"></i>
          </span>
          <input
            v-model="searchQuery"
            type="text"
            class="form-control"
            placeholder="Search by title, composer, or arranger..."
          />
        </div>
      </div>
      <div class="col-md-3 mb-3 mb-md-0">
        <select v-model="filterGenre" class="form-select">
          <option value="">All Genres</option>
          <option v-for="genre in genres" :key="genre" :value="genre">{{ genre }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <select v-model="sortBy" class="form-select">
          <option value="newest">Newest First</option>
          <option value="oldest">Oldest First</option>
          <option value="title">Title A-Z</option>
          <option value="composer">Composer A-Z</option>
        </select>
      </div>
    </div>

    <!-- Sheet Music List -->
    <div v-if="filteredAndSortedList.length > 0" class="row">
      <div class="col-12">
        <div class="table-responsive">
          <table class="table table-hover table-custom">
            <thead>
              <tr>
                <th>Title</th>
                <th>Composer</th>
                <th>Genre</th>
                <th>Year</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredAndSortedList" :key="item.id">
                <td>
                  <div class="fw-bold">{{ item.title }}</div>
                  <small class="text-muted" v-if="item.subtitle">{{ item.subtitle }}</small>
                </td>
                <td>
                  <div>{{ item.composer }}</div>
                  <small class="text-muted" v-if="item.arranger">
                    Arr: {{ item.arranger }}
                  </small>
                </td>
                <td>
                  <span :class="getGenreClass(item.genre)" class="genre-badge">
                    {{ item.genre }}
                  </span>
                </td>
                <td>{{ item.year }}</td>
                <td>
                  <div class="btn-group">
                    <router-link
                      :to="`/edit/${item.id}`"
                      class="btn btn-sm btn-outline-primary"
                      title="Edit"
                    >
                      <i class="bi bi-pencil"></i>
                    </router-link>
                    <button
                      @click="confirmDelete(item)"
                      class="btn btn-sm btn-outline-danger"
                      title="Delete"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="row">
      <div class="col-12">
        <div class="card-custom p-5 text-center">
          <i class="bi bi-music-note-list display-1 text-muted mb-3"></i>
          <h3 class="text-muted mb-3">
            {{ searchQuery || filterGenre ? 'No matching sheet music found' : 'Your collection is empty' }}
          </h3>
          <p class="text-muted mb-4">
            {{ searchQuery || filterGenre ? 'Try adjusting your search or filters' : 'Start by adding your first sheet music piece' }}
          </p>
          <router-link
            v-if="!searchQuery && !filterGenre"
            to="/add"
            class="btn btn-primary btn-lg"
          >
            <i class="bi bi-plus-lg me-2"></i>Add Your First Piece
          </router-link>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete "{{ itemToDelete?.title }}"?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button @click="deleteItem" type="button" class="btn btn-danger">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { Modal } from 'bootstrap'

const store = useSheetMusicStore()
const searchQuery = ref('')
const filterGenre = ref('')
const sortBy = ref('newest')
const itemToDelete = ref(null)
let deleteModal = null

const genres = [
  'Medieval & Renaissance (500 - 1600)',
  'Baroque (1600 - 1750)',
  'Classical (1750 - 1820)',
  'Romantic, Modern & Contemporary (1820 - now)'
]

onMounted(() => {
  deleteModal = new Modal(document.getElementById('deleteModal'))
})

const filteredAndSortedList = computed(() => {
  let list = [...store.sheetMusicList]

  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    list = list.filter(
      item =>
        item.title.toLowerCase().includes(query) ||
        item.composer.toLowerCase().includes(query) ||
        (item.arranger && item.arranger.toLowerCase().includes(query)) ||
        (item.subtitle && item.subtitle.toLowerCase().includes(query))
    )
  }

  // Filter by genre
  if (filterGenre.value) {
    list = list.filter(item => item.genre === filterGenre.value)
  }

  // Sort
  switch (sortBy.value) {
    case 'newest':
      list.sort((a, b) => b.createdAt.localeCompare(a.createdAt))
      break
    case 'oldest':
      list.sort((a, b) => a.createdAt.localeCompare(b.createdAt))
      break
    case 'title':
      list.sort((a, b) => a.title.localeCompare(b.title))
      break
    case 'composer':
      list.sort((a, b) => a.composer.localeCompare(b.composer))
      break
  }

  return list
})

const getGenreClass = (genre) => {
  const classes = {
    'Medieval & Renaissance (500 - 1600)': 'bg-primary text-white',
    'Baroque (1600 - 1750)': 'bg-success text-white',
    'Classical (1750 - 1820)': 'bg-info text-dark',
    'Romantic, Modern & Contemporary (1820 - now)': 'bg-warning text-dark'
  }
  return classes[genre] || 'bg-secondary text-white'
}

const confirmDelete = (item) => {
  itemToDelete.value = item
  deleteModal.show()
}

const deleteItem = () => {
  if (itemToDelete.value) {
    store.deleteSheetMusic(itemToDelete.value.id)
    deleteModal.hide()
    itemToDelete.value = null
  }
}
</script>