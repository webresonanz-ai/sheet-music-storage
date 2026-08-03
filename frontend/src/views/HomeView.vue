<template>
  <div>
    <!-- Hero -->
    <section class="hero p-4 p-md-5 stagger">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative">
        <div class="d-flex align-items-center gap-3">
          <span class="hero-icon p-3 fs-3 d-inline-flex align-items-center">
            <i class="bi bi-collection"></i>
          </span>
          <div>
            <h1 class="fw-bold mb-1 display-6 fs-2">
              My Sheet Music Collection
            </h1>
            <p class="lead__muted mb-0">
              <span class="fw-semibold">{{ store.totalCount }}</span>
              {{ store.totalCount === 1 ? 'piece' : 'pieces' }}
              &middot; spanning {{ erasCovered }} of {{ totalEras }} eras
            </p>
          </div>
        </div>
        <button type="button" class="btn btn-primary btn-lg px-xl-4" @click="openAdd">
          <i class="bi bi-plus-lg me-2"></i>Add New Sheet Music
        </button>
      </div>
    </section>

    <!-- Stats -->
    <section class="stat-grid mt-3 stagger">
      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <span class="stat-icon" style="background: #e9f2ff; color: #1d6ed2"><i class="bi bi-music-note-list"></i></span>
          <div>
            <div class="stat-value">{{ store.totalCount }}</div>
            <div class="stat-label">Musical pieces</div>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <span class="stat-icon" style="background: #e2f6f4; color: #0f9d8f"><i class="bi bi-people"></i></span>
          <div>
            <div class="stat-value">{{ uniqueComposers }}</div>
            <div class="stat-label">Unique composers</div>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <span class="stat-icon" style="background: #efecfc; color: #6d5bd0"><i class="bi bi-hourglass-split"></i></span>
          <div>
            <div class="stat-value">{{ erasCovered }}</div>
            <div class="stat-label">Eras covered</div>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <span class="stat-icon" style="background: #fdf0e1; color: #b96a2c"><i class="bi bi-calendar-range"></i></span>
          <div>
            <div class="stat-value">{{ yearSpanLabel }}</div>
            <div class="stat-label">Year range</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Toolbar: search + era chips + sort -->
    <section class="toolbar p-3 p-md-4 mt-3">
      <div class="row g-3 align-items-center">
        <div class="col-lg-5">
          <div class="search-wrap">
            <i class="bi bi-search search-icon"></i>
            <input
              v-model="searchQuery"
              type="text"
              class="form-control"
              placeholder="Search by title, composer, or arranger..."
            />
          </div>
        </div>
        <div class="col-lg-4">
          <div class="d-flex align-items-center gap-2 flex-sm-wrap">
            <i class="bi bi-tags text-muted"></i>
            <div class="era-chips">
              <span
                class="era-chip clear"
                :class="{ active: filterGenre === '' }"
                @click="setGenre('')"
              >
                All
              </span>
              <span
                v-for="era in genreList"
                :key="era.name"
                class="era-chip"
                :class="{ active: filterGenre === era.name }"
                @click="toggleGenre(era.name)"
              >
                <span class="chip-dot" :style="{ background: era.dot }"></span>
                <span>{{ era.short }}</span>
                <span class="chip-count">{{ era.count }}</span>
              </span>
            </div>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-sort-down text-muted"></i></span>
            <select v-model="sortBy" class="form-select">
              <option value="newest">Newest first</option>
              <option value="oldest">Oldest first</option>
              <option value="year-asc">Year: earliest</option>
              <option value="year-desc">Year: latest</option>
              <option value="title">Title A–Z</option>
              <option value="composer">Composer A–Z</option>
            </select>
          </div>
        </div>
      </div>
    </section>

    <!-- Results summary -->
    <section class="d-flex align-items-center justify-content-between mt-4 px-1">
      <h6 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 0.6px; color: var(--navy)">
        <i class="bi bi-grid me-2"></i>Pieces
      </h6>
      <span class="badge rounded-pill px-3 py-2" style="background: var(--primary-soft); color: var(--primary)">
        {{ filteredCount }} shown
      </span>
    </section>

    <!-- Loading -->
    <div v-if="store.loading" class="table-panel mt-2">
      <div class="d-flex flex-column align-items-center justify-content-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted mt-3 mb-0">Gathering your collection...</p>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="store.error" class="table-panel mt-2 p-4">
      <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <span>Unable to load from the server: <strong>{{ store.error }}</strong></span>
      </div>
    </div>

    <!-- List -->
    <div v-else-if="filteredAndSortedList.length > 0" class="table-panel mt-2">
      <div class="table-responsive">
        <table class="table table-hover table-custom mb-0">
          <thead>
            <tr>
              <th>Title</th>
              <th>Composer</th>
              <th class="d-none d-sm-table-cell">Era</th>
              <th>Year</th>
              <th class="d-none d-lg-table-cell">Added</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filteredAndSortedList" :key="item.id">
              <td>
                <div class="piece-title">{{ item.title }}</div>
                <div class="piece-subtitle" v-if="item.subtitle">{{ item.subtitle }}</div>
              </td>
              <td>
                <div class="person-name">{{ item.composer }}</div>
                <div class="piece-subtitle" v-if="item.arranger">Arr: {{ item.arranger }}</div>
              </td>
              <td class="d-none d-sm-table-cell">
                <span :class="getGenreClass(item.genre)">
                  <span class="chip-dot" style="width: 7px; height: 7px"></span>
                  {{ shortGenre(item.genre) }}
                </span>
              </td>
              <td>
                <span class="year-pill"><i class="bi bi-calendar3 text-muted"></i>{{ item.year }}</span>
              </td>
              <td class="d-none d-lg-table-cell">
                <span class="piece-subtitle">{{ formatDate(item.createdAt) }}</span>
              </td>
              <td class="text-end">
                <div class="btn-group gap-2">
                  <button
                    @click="openEdit(item)"
                    class="btn btn-outline-primary btn-icon edit"
                    title="Edit"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    @click="confirmDelete(item)"
                    class="btn btn-outline-secondary btn-icon trash"
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

    <!-- Empty state -->
    <div v-else class="table-panel mt-2">
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-music-note-list"></i></div>
        <h4 class="fw-bold mb-2" style="color: var(--ink)">
          {{ hasQuery ? 'No matching pieces' : 'Your collection is empty' }}
        </h4>
        <p class="text-muted mb-4">
          {{ hasQuery ? 'Try adjusting your search or clearing era filters.' : 'Start by adding your first sheet music piece.' }}
        </p>
        <button
          v-if="!hasQuery"
          type="button"
          class="btn btn-primary btn-lg"
          @click="openAdd"
        >
          <i class="bi bi-plus-lg me-2"></i>Add Your First Piece
        </button>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Confirm deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to remove
            <strong>"{{ itemToDelete?.title }}"</strong> from your collection?
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button @click="deleteItem" type="button" class="btn btn-primary px-4" style="background: #d7383c">
              <i class="bi bi-trash me-1"></i>Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { useSheetMusicModal } from '../composables/sheetMusicModal'
import { Modal } from 'bootstrap'

const store = useSheetMusicStore()
const { openAdd, openEdit } = useSheetMusicModal()
const searchQuery = ref('')
const filterGenre = ref('')
const sortBy = ref('newest')
const itemToDelete = ref(null)
let deleteModal = null

const genres = {
  medieval: { name: 'Medieval & Renaissance (500 - 1600)', short: 'Medieval', dot: '#b96a2c' },
  baroque: { name: 'Baroque (1600 - 1750)', short: 'Baroque', dot: '#1d6ed2' },
  classical: { name: 'Classical (1750 - 1820)', short: 'Classical', dot: '#0f9d8f' },
  romantic: { name: 'Romantic, Modern & Contemporary (1820 - now)', short: 'Modern', dot: '#6d5bd0' }
}

const totalEras = Object.values(genres).length

const genreList = computed(() =>
  Object.values(genres).map(g => ({
    ...g,
    count: store.sheetMusicList.filter(i => i.genre === g.name).length
  }))
)

const hasQuery = computed(
  () => !!(searchQuery.value || filterGenre.value)
)

const uniqueComposers = computed(() =>
  new Set(store.sheetMusicList.map(i => i.composer)).size
)

const erasCovered = computed(() =>
  new Set(store.sheetMusicList.map(i => i.genre)).size
)

const yearSpanLabel = computed(() => {
  const years = store.sheetMusicList.map(i => Number(i.year))
  if (years.length === 0) return '—'
  const min = Math.min(...years)
  const max = Math.max(...years)
  return min === max ? String(min) : `${min}–${max}`
})

const filteredCount = computed(() => filteredAndSortedList.value.length)

onMounted(() => {
  deleteModal = new Modal(document.getElementById('deleteModal'))
  store.loadFromApi()
})

const setGenre = (genre) => { filterGenre.value = genre }

const toggleGenre = (genre) => {
  filterGenre.value = filterGenre.value === genre ? '' : genre
}

const shortGenre = (name) => {
  for (const g of Object.values(genres)) if (g.name === name) return g.short
  return name
}

const getGenreClass = (genre) => {
  const map = {
    'Medieval & Renaissance (500 - 1600)': 'genre-badge era-medieval',
    'Baroque (1600 - 1750)': 'genre-badge era-baroque',
    'Classical (1750 - 1820)': 'genre-badge era-classical',
    'Romantic, Modern & Contemporary (1820 - now)': 'genre-badge era-romantic'
  }
  return map[genre] || 'genre-badge era-neutral'
}

const formatDate = (iso) => {
  if (!iso) return '—'
  const d = new Date(String(iso).replace(' ', 'T'))
  if (isNaN(d)) return '—'
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

const filteredAndSortedList = computed(() => {
  let list = [...store.sheetMusicList]

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      item =>
        item.title.toLowerCase().includes(q) ||
        item.composer.toLowerCase().includes(q) ||
        (item.arranger && item.arranger.toLowerCase().includes(q)) ||
        (item.subtitle && item.subtitle.toLowerCase().includes(q))
    )
  }

  if (filterGenre.value) {
    list = list.filter(item => item.genre === filterGenre.value)
  }

  switch (sortBy.value) {
    case 'newest':
      list.sort((a, b) => b.createdAt.localeCompare(a.createdAt))
      break
    case 'oldest':
      list.sort((a, b) => a.createdAt.localeCompare(b.createdAt))
      break
    case 'year-asc':
      list.sort((a, b) => a.year - b.year)
      break
    case 'year-desc':
      list.sort((a, b) => b.year - a.year)
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

const confirmDelete = (item) => {
  itemToDelete.value = item
  deleteModal.show()
}

const deleteItem = async () => {
  if (itemToDelete.value) {
    await store.deleteSheetMusic(itemToDelete.value.id).catch(() => {})
    deleteModal.hide()
    itemToDelete.value = null
  }
}
</script>