<template>
  <div class="app-container">
    <nav class="navbar navbar-expand-lg navbar-custom">
      <div class="container-fluid align-items-center">
        <router-link class="navbar-brand d-flex align-items-center gap-2 brand-link" to="/">
          <span class="brand-mark"><i class="bi bi-music-note-list"></i></span>
          <span class="fw-bold fs-5">SheetMusic <span class="text-primary">Vault</span></span>
        </router-link>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto align-items-lg-center">
            <li class="nav-item">
              <router-link class="nav-link" to="/">
                <i class="bi bi-collection me-1"></i>Collection
              </router-link>
            </li>
            <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
              <button type="button" class="btn btn-primary btn-sm px-4" @click="openAdd">
                <i class="bi bi-plus-lg me-1"></i>Add Piece
              </button>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="main-content mt-4">
      <router-view v-slot="{ Component }">
        <transition name="fade-slide" mode="out-in">
          <component :is="Component" :key="$route.path" />
        </transition>
      </router-view>
    </main>

    <footer class="app-footer">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="footer-brand">
          <i class="bi bi-music-note-list me-1"></i>SheetMusic Vault
        </div>
        <div>
          A curated digital library of classical music &middot;
          <i class="bi bi-lock me-1"></i>Stored securely in your database
        </div>
      </div>
    </footer>

    <!-- Global Add/Edit modal -->
    <SheetMusicModal />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import SheetMusicModal from './components/SheetMusicModal.vue'
import { useSheetMusicModal } from './composables/sheetMusicModal'

const { openAdd } = useSheetMusicModal()

onMounted(() => {
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'
  document.head.appendChild(link)
})
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
@media (max-width: 991px) {
  .ms-lg-remote {
    margin-left: 0;
  }
}
</style>