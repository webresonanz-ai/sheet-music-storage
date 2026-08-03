<template>
  <div class="app-container">
    <nav class="navbar navbar-expand-lg navbar-custom mb-4 rounded-3">
      <div class="container">
        <router-link class="navbar-brand d-flex align-items-center" to="/">
          <i class="bi bi-music-note-list fs-4 me-2"></i>
          <span class="fw-bold">SheetMusic Vault</span>
        </router-link>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <router-link class="nav-link px-3" to="/">
                <i class="bi bi-house-door me-1"></i>Home
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link px-3" to="/add">
                <i class="bi bi-plus-circle me-1"></i>Add Sheet Music
              </router-link>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <div class="main-content fade-in">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  // Add Bootstrap Icons
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'
  document.head.appendChild(link)
})
</script>