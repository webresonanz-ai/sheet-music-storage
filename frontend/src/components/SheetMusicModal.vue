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

const store = useSheetMusicStore()
const { state, closeModal } = useSheetMusicModal()

const genres = [
  'Medieval & Renaissance (500 - 1600)',
  'Baroque (1600 - 1750)',
  'Classical (1750 - 1820)',
  'Romantic, Modern & Contemporary (1820 - now)'
]

const isEdit = computed(() => state.mode === 'edit')
const currentYear = new Date().getFullYear()

const form = reactive({ title: '', subtitle: '', composer: '', arranger: '', year: '', genre: '' })
const errors = reactive({})
const isSubmitting = ref(false)
const submitError = ref('')

const resetForm = () => {
  Object.keys(form).forEach(k => { form[k] = '' })
  Object.keys(errors).forEach(k => { delete errors[k] })
  submitError.value = ''
}

const populateForm = () => {
  resetForm()
  if (isEdit.value && state.item) {
    form.title = state.item.title
    form.subtitle = state.item.subtitle || ''
    form.composer = state.item.composer
    form.arranger = state.item.arranger || ''
    form.year = state.item.year
    form.genre = state.item.genre
  }
}

watch(() => state.open, (opened) => {
  if (opened) populateForm()
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
  isSubmitting.value = true
  submitError.value = ''

  const data = {
    title: form.title.trim(),
    subtitle: form.subtitle.trim() || null,
    composer: form.composer.trim(),
    arranger: form.arranger.trim() || null,
    year: form.year.trim(),
    genre: form.genre
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