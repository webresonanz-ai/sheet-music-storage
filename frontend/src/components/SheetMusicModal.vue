<template>
  <Teleport to="body">
    <transition name="modal-fade">
      <div
        v-if="state.open"
        class="modal-overlay"
        role="dialog"
        aria-modal="true"
        @keydown.esc="closeModal"
        @click.self="closeModal"
      >
        <div
          class="modal-panel"
          role="document"
          :aria-label="isEdit ? 'Edit sheet music' : 'Add sheet music'"
        >
          <!-- Header -->
          <div class="modal-panel-header">
            <div>
              <h3 class="fw-bold mb-0" style="color: var(--ink)">
                {{ isEdit ? 'Edit Sheet Music' : 'Add New Sheet Music' }}
              </h3>
              <p class="text-muted mb-0 mt-1 small">
                {{ isEdit ? 'Update the details of your collection piece' : 'Enter the details of your collection piece' }}
              </p>
            </div>
            <button type="button" class="modal-close" aria-label="Close" @click="closeModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="modal-body">
            <!-- Global submit error -->
            <div v-if="submitError" class="alert alert-danger d-flex align-items-center mb-3 py-2" role="alert">
              <i class="bi bi-exclamation-circle me-2"></i>
              <span>{{ submitError }}</span>
            </div>

            <form id="modal-sheet-music-form" @submit.prevent="handleSubmit">
              <!-- Score image uploader -->
              <div class="score-image-uploader mb-3">
                <img
                  v-if="previewUrl"
                  :src="previewUrl"
                  alt="Score sheet preview"
                  class="score-image-preview"
                />
                <div v-else class="score-image-placeholder">
                  <i class="bi bi-file-earmark-image"></i>
                  <span>No score sheet image</span>
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    @click="handleFileInput"
                  >
                    <i class="bi bi-upload me-1"></i>Upload
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="openCamera"
                  >
                    <i class="bi bi-camera me-1"></i>Take photo
                  </button>
                  <button
                    v-if="previewUrl"
                    type="button"
                    class="btn btn-sm btn-outline-danger ms-auto"
                    @click="clearScoreImage"
                  >
                    <i class="bi bi-x-lg me-1"></i>Remove
                  </button>
                </div>

                <input
                  ref="fileInput"
                  type="file"
                  accept="image/*"
                  class="d-none"
                  @change="onFileSelected"
                />
                <input
                  ref="cameraInput"
                  type="file"
                  accept="image/*"
                  capture="environment"
                  class="d-none"
                  @change="onFileSelected"
                />

                <div v-if="imageUploading" class="form-hint mt-2">
                  <span class="spinner-border spinner-border-sm me-1"></span>Uploading image...
                </div>
                <div v-if="imageError" class="invalid-feedback d-block mt-2">{{ imageError }}</div>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-music-note field-icon me-1"></i>Title <span class="req-star">*</span>
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

                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-subtract field-icon me-1"></i>Subtitle
                    <span class="req-star ms-1">optional</span>
                  </label>
                  <input
                    v-model="form.subtitle"
                    type="text"
                    class="form-control"
                    placeholder="e.g., Allegro con brio"
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-person field-icon me-1"></i>Composer <span class="req-star">*</span>
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

                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-person-gear field-icon me-1"></i>Arranger
                    <span class="req-star">- optional</span>
                  </label>
                  <input
                    v-model="form.arranger"
                    type="text"
                    class="form-control"
                    placeholder="e.g., Franz Liszt"
                  />
                  <div class="form-hint"><i class="bi bi-info-circle me-1"></i>The person who arranged this piece, if any.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-geo-alt field-icon me-1"></i>Location
                    <span class="req-star">- optional</span>
                  </label>
                  <input
                    v-model="form.location"
                    type="text"
                    class="form-control"
                    placeholder="e.g., Cabinet A"
                  />
                  <div class="form-hint"><i class="bi bi-info-circle me-1"></i>Where this piece is stored.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-box field-icon me-1"></i>Shelf ID
                    <span class="req-star">- optional</span>
                  </label>
                  <input
                    v-model="form.shelf_id"
                    type="text"
                    class="form-control"
                    placeholder="e.g., S-01"
                  />
                  <div class="form-hint"><i class="bi bi-info-circle me-1"></i>Shelf or box identifier.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-collection field-icon me-1"></i>Category
                    <span class="req-star">- optional</span>
                  </label>
                  <input
                    v-model="form.category"
                    type="text"
                    class="form-control"
                    placeholder="e.g., Repertoire, Etudes, Recital"
                  />
                  <div class="form-hint"><i class="bi bi-info-circle me-1"></i>How this piece is categorized.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-calendar field-icon me-1"></i>Year <span class="req-star">*</span>
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
                  <div class="form-hint"><i class="bi bi-info-circle me-1"></i>Composition year, between 500 and {{ currentYear }}.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">
                    <i class="bi bi-tag field-icon me-1"></i>Era / Genre <span class="req-star">*</span>
                  </label>
                  <select
                    v-model="form.genre"
                    class="form-select"
                    :class="{ 'is-invalid': errors.genre }"
                    @change="genreAuto = false"
                    required
                  >
                    <option value="" disabled>Select an era...</option>
                    <option v-for="genre in genres" :key="genre" :value="genre">{{ genre }}</option>
                  </select>
                  <div v-if="errors.genre" class="invalid-feedback">{{ errors.genre }}</div>
                </div>
              </div>
            </form>
          </div>

          <!-- Footer -->
          <div class="modal-panel-footer">
            <button type="button" class="btn btn-outline-secondary px-4" @click="closeModal">
              Cancel
            </button>
            <button
              type="submit"
              form="modal-sheet-music-form"
              class="btn btn-primary px-4"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-lg me-1"></i>
              {{ isSubmitting ? 'Saving...' : (isEdit ? 'Save changes' : 'Add to collection') }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { useSheetMusicModal } from '../composables/sheetMusicModal'
import { compressImage } from '../utils/imageCompressor'

const store = useSheetMusicStore()
const { state, closeModal } = useSheetMusicModal()

const genres = [
  'Early/Medieval Era (550 - 1400)',
  'Renaissance Era (1400 - 1600)',
  'Baroque Era (1600 - 1750)',
  'Classical Era (1750 - 1820)',
  'Romantic Era (1820 - 1910)',
  'Contemporary/Modern Era (1910 - Present)'
]

const isEdit = computed(() => state.mode === 'edit')
const currentYear = new Date().getFullYear()

const form = reactive({ title: '', subtitle: '', composer: '', arranger: '', year: '', genre: '', location: '', shelf_id: '', category: '' })
const errors = reactive({})
const isSubmitting = ref(false)
const submitError = ref('')

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
const fileInput = ref(null)
const cameraInput = ref(null)
const uploadedUrl = ref('')
const previewUrl = ref('')
const imageUploading = ref(false)
const imageError = ref('')

const resetForm = () => {
  Object.keys(form).forEach(k => { form[k] = '' })
  Object.keys(errors).forEach(k => { delete errors[k] })
  submitError.value = ''
  uploadedUrl.value = ''
  previewUrl.value = ''
  imageError.value = ''
  imageUploading.value = false
  genreAuto = false
  if (fileInput.value) fileInput.value.value = ''
  if (cameraInput.value) cameraInput.value.value = ''
}

const populateForm = () => {
  resetForm()
  if (isEdit.value && state.item) {
    form.title = state.item.title
    form.subtitle = state.item.subtitle || ''
    form.composer = state.item.composer
    form.arranger = state.item.arranger || ''
    form.year = state.item.year != null ? String(state.item.year) : ''
    form.genre = genres.find(g => shortGenreName(g) === state.item.genre) || state.item.genre
    form.location = state.item.location || ''
    form.shelf_id = state.item.shelfId || ''
    form.category = state.item.category || ''
    genreAuto = true
    uploadedUrl.value = state.item.scoreImg || ''
    previewUrl.value = state.item.scoreImg ? `${API_BASE}${state.item.scoreImg}` : ''
  }
}

const onFileSelected = (event) => {
  const file = event.target.files && event.target.files[0]
  if (!file) return
  imageError.value = ''
  imageUploading.value = true
  uploadImage(file)
}

const handleFileInput = () => {
  if (fileInput.value) fileInput.value.click()
}

const openCamera = () => {
  if (cameraInput.value) cameraInput.value.click()
}

const clearScoreImage = () => {
  uploadedUrl.value = ''
  previewUrl.value = ''
  imageError.value = ''
  if (fileInput.value) fileInput.value.value = ''
  if (cameraInput.value) cameraInput.value.value = ''
}

const uploadImage = async (file) => {
  imageUploading.value = true
  try {
    const compressed = await compressImage(file, { maxBytes: 100 * 1024 })
    previewUrl.value = URL.createObjectURL(compressed)
    const result = await store.uploadScoreImage(compressed)
    uploadedUrl.value = result.url
  } catch (e) {
    imageError.value = e.message || 'Image upload failed.'
    previewUrl.value = ''
  } finally {
    imageUploading.value = false
  }
}

watch(() => state.open, (opened) => {
  if (opened) {
    clearTimeout(yearSuggestionTimer)
    populateForm()
  }
})

const suggestGenreFromYear = (year) => {
  const num = parseInt(year, 10)
  if (!year || isNaN(num)) return ''
  const ranges = [
    { min: 550, max: 1400, genre: 'Early/Medieval Era (550 - 1400)' },
    { min: 1400, max: 1600, genre: 'Renaissance Era (1400 - 1600)' },
    { min: 1600, max: 1750, genre: 'Baroque Era (1600 - 1750)' },
    { min: 1750, max: 1820, genre: 'Classical Era (1750 - 1820)' },
    { min: 1820, max: 1910, genre: 'Romantic Era (1820 - 1910)' },
    { min: 1910, max: Infinity, genre: 'Contemporary/Modern Era (1910 - Present)' }
  ]
  const match = ranges.find(r => num >= r.min && num <= r.max)
  return match ? match.genre : ''
}

const shortGenreName = (genre) => {
  if (!genre) return ''
  return genre.split(' (')[0].replace(/\s*Era$/, '')
}

let yearSuggestionTimer = null
let genreAuto = false
watch(() => form.year, (year) => {
  clearTimeout(yearSuggestionTimer)
  if (!year || !form.genre || genreAuto) {
    yearSuggestionTimer = setTimeout(() => {
      const suggested = suggestGenreFromYear(year)
      if (suggested) {
        form.genre = suggested
        genreAuto = true
      }
    }, 1000)
  }
})

const validateForm = () => {
  Object.keys(errors).forEach(k => { delete errors[k] })

  if (!form.title.trim()) errors.title = 'Title is required.'
  if (!form.composer.trim()) errors.composer = 'Composer is required.'
  if (!form.year.trim()) {
    errors.year = 'Year is required.'
  } else if (isNaN(form.year) || form.year < 500 || form.year > currentYear) {
    errors.year = 'Please enter a valid year.'
  }
  if (!form.genre) errors.genre = 'Please select an era.'

  return Object.keys(errors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  await submitForm()
}

const submitForm = async () => {
  if (imageUploading.value) return
  isSubmitting.value = true
  submitError.value = ''

  const data = {
    title: form.title.trim(),
    subtitle: form.subtitle.trim() || null,
    composer: form.composer.trim(),
    arranger: form.arranger.trim() || null,
    year: form.year.trim(),
    genre: shortGenreName(form.genre),
    location: form.location.trim() || null,
    shelf_id: form.shelf_id.trim() || null,
    category: form.category.trim() || null,
    score_img: uploadedUrl.value || null
  }

  try {
    if (isEdit.value) {
      await store.updateSheetMusic(state.item.id, data)
    } else {
      await store.addSheetMusic(data)
    }
    closeModal()
  } catch (e) {
    submitError.value = e.message || 'Something went wrong. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>
