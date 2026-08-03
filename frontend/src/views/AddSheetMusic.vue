<template>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card-custom p-4 p-md-5">
        <div class="text-center mb-4">
          <i class="bi bi-plus-circle display-4 text-primary mb-3"></i>
          <h2 class="fw-bold">Add New Sheet Music</h2>
          <p class="text-muted">Enter the details of your sheet music piece</p>
        </div>

        <form @submit.prevent="handleSubmit">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-music-note me-1"></i>Title *
              </label>
              <input
                v-model="form.title"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.title }"
                placeholder="e.g., Symphony No. 5"
                required
              />
              <div v-if="errors.title" class="invalid-feedback">{{ errors.title }}</div>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-subtract me-1"></i>Subtitle
              </label>
              <input
                v-model="form.subtitle"
                type="text"
                class="form-control"
                placeholder="e.g., Allegro con brio"
              />
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-person me-1"></i>Composer *
              </label>
              <input
                v-model="form.composer"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.composer }"
                placeholder="e.g., Ludwig van Beethoven"
                required
              />
              <div v-if="errors.composer" class="invalid-feedback">{{ errors.composer }}</div>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-person-gear me-1"></i>Arranger
              </label>
              <input
                v-model="form.arranger"
                type="text"
                class="form-control"
                placeholder="e.g., Franz Liszt"
              />
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-calendar me-1"></i>Year *
              </label>
              <input
                v-model="form.year"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.year }"
                placeholder="e.g., 1808"
                required
              />
              <div v-if="errors.year" class="invalid-feedback">{{ errors.year }}</div>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-tag me-1"></i>Genre *
              </label>
              <select
                v-model="form.genre"
                class="form-select"
                :class="{ 'is-invalid': errors.genre }"
                required
              >
                <option value="">Select a genre...</option>
                <option v-for="genre in genres" :key="genre" :value="genre">
                  {{ genre }}
                </option>
              </select>
              <div v-if="errors.genre" class="invalid-feedback">{{ errors.genre }}</div>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <router-link to="/" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left me-2"></i>Back to Collection
            </router-link>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-lg me-2"></i>
              {{ isSubmitting ? 'Saving...' : 'Save Sheet Music' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useSheetMusicStore } from '../stores/sheetMusic'

const router = useRouter()
const store = useSheetMusicStore()

const genres = [
  'Medieval & Renaissance (500 - 1600)',
  'Baroque (1600 - 1750)',
  'Classical (1750 - 1820)',
  'Romantic, Modern & Contemporary (1820 - now)'
]

const form = reactive({
  title: '',
  subtitle: '',
  composer: '',
  arranger: '',
  year: '',
  genre: ''
})

const errors = reactive({})
const isSubmitting = ref(false)

const validateForm = () => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.title.trim()) {
    errors.title = 'Title is required'
  }

  if (!form.composer.trim()) {
    errors.composer = 'Composer is required'
  }

  if (!form.year.trim()) {
    errors.year = 'Year is required'
  } else if (isNaN(form.year) || form.year < 500 || form.year > new Date().getFullYear()) {
    errors.year = 'Please enter a valid year'
  }

  if (!form.genre) {
    errors.genre = 'Please select a genre'
  }

  return Object.keys(errors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return

  isSubmitting.value = true

  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500))

    store.addSheetMusic({
      title: form.title.trim(),
      subtitle: form.subtitle.trim() || null,
      composer: form.composer.trim(),
      arranger: form.arranger.trim() || null,
      year: form.year.trim(),
      genre: form.genre
    })

    router.push('/')
  } catch (error) {
    console.error('Error saving sheet music:', error)
    alert('An error occurred while saving. Please try again.')
  } finally {
    isSubmitting.value = false
  }
}
</script>