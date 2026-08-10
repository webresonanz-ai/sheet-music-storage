<template>
  <Teleport to="body">
    <transition name="modal-fade">
      <div
        v-if="item"
        class="modal-overlay"
        role="dialog"
        aria-modal="true"
        :aria-label="`Detail of ${item.title}`"
        @keydown.esc="close"
        @click.self="close"
      >
        <div class="modal-panel" role="document">
          <div class="modal-panel-header">
            <div>
              <h3 class="fw-bold mb-0" style="color: var(--ink)">
                <i class="bi bi-music-note-beamed me-1"></i>Piece Details
              </h3>
              <p class="text-muted mb-0 mt-1 small">Full information for this collection piece</p>
            </div>
            <button type="button" class="modal-close" aria-label="Close" @click="close">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="modal-body">
            <!-- Score image -->
            <div v-if="scoreUrl" class="text-center mb-4">
              <img :src="scoreUrl" :alt="`Score sheet for ${item.title}`" class="score-img-detail" />
            </div>

            <!-- Title -->
            <div class="detail-block">
              <div class="detail-label">
                <i class="bi bi-music-note field-icon me-1"></i>Title
              </div>
              <div class="detail-value">{{ item.title }}</div>
              <div v-if="item.subtitle" class="detail-sub">{{ item.subtitle }}</div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <div class="detail-block">
                  <div class="detail-label">
                    <i class="bi bi-person field-icon me-1"></i>Composer
                  </div>
                  <div class="detail-value">{{ item.composer }}</div>
                </div>
              </div>

              <div class="col-md-6" v-if="item.arranger">
                <div class="detail-block">
                  <div class="detail-label">
                    <i class="bi bi-person-gear field-icon me-1"></i>Arranger
                  </div>
                  <div class="detail-value">{{ item.arranger }}</div>
                </div>
              </div>

              <div class="col-md-6" v-if="item.location">
                <div class="detail-block">
                  <div class="detail-label">
                    <i class="bi bi-geo-alt field-icon me-1"></i>Location
                  </div>
                  <div class="detail-value">{{ item.location }}</div>
                </div>
              </div>

              <div class="col-md-6" v-if="item.shelfId">
                <div class="detail-block">
                  <div class="detail-label">
                    <i class="bi bi-box field-icon me-1"></i>Shelf ID
                  </div>
                  <div class="detail-value">{{ item.shelfId }}</div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="detail-block">
                  <div class="detail-label">
                    <i class="bi bi-tag field-icon me-1"></i>Era / Genre
                  </div>
                  <div class="detail-value">
                    <span :class="getGenreClass(item.genre)">
                      <span class="chip-dot" style="width: 8px; height: 8px"></span>
                      {{ shortGenre(item.genre) }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="detail-block">
                  <div class="detail-label">
                    <i class="bi bi-calendar event field-icon me-1"></i>Year
                  </div>
                  <div class="detail-value">Composed in {{ item.year }}</div>
                </div>
              </div>

              <div class="col-12" v-if="item.createdAt">
                <div class="detail-block">
                  <div class="detail-label">
                    <i class="bi bi-clock-history field-icon me-1"></i>Added to collection
                  </div>
                  <div class="detail-value">{{ formatDate(item.createdAt) }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-panel-footer">
            <button type="button" class="btn btn-outline-secondary px-4" @click="close">
              Close
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  item: { type: Object, default: null }
})
const emit = defineEmits(['close'])

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'

const close = () => emit('close')

const scoreUrl = computed(() =>
  props.item && props.item.scoreImg ? `${API_BASE}${props.item.scoreImg}` : null
)

const shortGenre = (name) => {
  if (!name) return 'Uncategorized'
  const base = name.split(' (')[0].replace(/\s*Era$/, '')
  const map = {
    'Early/Medieval': 'Early/Medieval',
    'Renaissance': 'Renaissance',
    'Baroque': 'Baroque',
    'Classical': 'Classical',
    'Romantic': 'Romantic',
    'Contemporary/Modern': 'Modern'
  }
  return map[base] || base
}

const getGenreClass = (genre) => {
  const map = {
    'Early/Medieval': 'genre-badge era-medieval',
    'Renaissance': 'genre-badge era-renaissance',
    'Baroque': 'genre-badge era-baroque',
    'Classical': 'genre-badge era-classical',
    'Romantic': 'genre-badge era-romantic',
    'Contemporary/Modern': 'genre-badge era-modern'
  }
  const base = genre ? genre.split(' (')[0].replace(/\s*Era$/, '') : ''
  return map[base] || 'genre-badge era-neutral'
}

const formatDate = (iso) => {
  if (!iso) return '—'
  const d = new Date(String(iso).replace(' ', 'T'))
  if (isNaN(d)) return '—'
  return d.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })
}
</script>

<style scoped>
.score-img-detail {
  max-width: 100%;
  max-height: 240px;
  border-radius: 14px;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  object-fit: cover;
}

.detail-block {
  padding: 14px 16px;
  background: #fbfdff;
  border: 1px solid var(--border);
  border-radius: 12px;
  margin-bottom: 6px;
}

.detail-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--muted);
  margin-bottom: 4px;
}

.detail-value {
  font-size: 1.02rem;
  font-weight: 600;
  color: var(--ink);
}

.detail-sub {
  color: var(--muted);
  font-style: italic;
  margin-top: 2px;
}
</style>