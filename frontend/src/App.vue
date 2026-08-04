<template>
  <div class="app-container">
    <nav class="navbar navbar-custom">
      <div class="navbar-inner">
        <router-link
          class="navbar-brand d-flex align-items-center gap-2 brand-link"
          to="/"
          @click="closeNav"
        >
          <span class="brand-mark"><i class="bi bi-music-note-list"></i></span>
          <span class="brand-text">
            <span class="fw-bold">SheetMusic</span>
            <span class="text-primary fw-bold">Vault</span>
          </span>
        </router-link>

        <div class="navbar-right">
          <button
            class="navbar-toggler"
            :class="{ open: navOpen }"
            type="button"
            @click="toggleNav"
            :aria-label="navOpen ? 'Close menu' : 'Open menu'"
            :aria-expanded="navOpen"
          >
            <span></span>
            <span></span>
            <span></span>
          </button>

          <div class="navbar-menu" :class="{ open: navOpen }">
            <template v-if="auth.isAuthenticated">
              <router-link class="nav-link" to="/" @click="closeNav">
                <i class="bi bi-collection me-1"></i>Collection
              </router-link>
              <button
                v-if="auth.isAdmin"
                type="button"
                class="btn btn-primary btn-sm btn-nav-add"
                @click="handleAdd"
              >
                <i class="bi bi-plus-lg me-1"></i>Add Piece
              </button>
              <span class="nav-user">
                <span class="nav-user-avatar">{{ initials }}</span>
                <span class="nav-user-name d-none d-lg-inline">{{ auth.user?.name }}</span>
                <span v-if="!auth.isAdmin" class="nav-role-badge">Guest</span>
                <button
                  type="button"
                  class="nav-logout"
                  title="Sign out"
                  @click="handleLogout"
                >
                  <i class="bi bi-box-arrow-right"></i>
                </button>
              </span>
            </template>

            <template v-else>
              <router-link class="nav-link" to="/login" @click="closeNav">
                <i class="bi bi-box-arrow-in-right me-1"></i>Sign in
              </router-link>
              <router-link class="btn btn-primary btn-sm btn-nav-add" to="/register" @click="closeNav">
                <i class="bi bi-person-plus me-1"></i>Register
              </router-link>
            </template>
          </div>
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
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SheetMusicModal from './components/SheetMusicModal.vue'
import { useSheetMusicModal } from './composables/sheetMusicModal'
import { useAuthStore } from './stores/auth'

const { openAdd } = useSheetMusicModal()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const navOpen = ref(false)
const toggleNav = () => { navOpen.value = !navOpen.value }
const closeNav = () => { navOpen.value = false }
const handleAdd = () => { closeNav(); openAdd() }

const initials = computed(() => {
  const name = auth.user?.name || ''
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  return (parts[0][0] + (parts[1]?.[0] || '')).toUpperCase()
})

const handleLogout = async () => {
  await auth.logout()
  closeNav()
  router.push({ name: 'login' })
}

watch(() => route.path, () => closeNav())

onMounted(async () => {
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'
  document.head.appendChild(link)

  if (auth.token) {
    auth.fetchMe().catch(() => {})
  }
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

/* ---------- Navbar layout ---------- */
.navbar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}

.navbar-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Hamburger toggler */
.navbar-toggler {
  display: none;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  width: 42px;
  height: 42px;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 10px;
  transition: background 0.2s ease;
}
.navbar-toggler:hover {
  background: var(--primary-soft);
}
.navbar-toggler span {
  display: block;
  width: 22px;
  height: 2px;
  background: var(--navy);
  border-radius: 2px;
  transition: transform 0.3s ease, opacity 0.2s ease;
}
.navbar-toggler.open span:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}
.navbar-toggler.open span:nth-child(2) {
  opacity: 0;
}
.navbar-toggler.open span:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

/* Nav links container (desktop: inline) */
.navbar-menu {
  display: flex;
  align-items: center;
  gap: 6px;
}

.navbar-menu .nav-link {
  display: inline-flex;
  align-items: center;
}

/* Auth user chip */
.nav-user {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-left: 6px;
  padding: 5px 8px;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: #fbfdff;
}

.nav-user-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--gradient);
  color: #fff;
  font-weight: 700;
  font-size: 0.8rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.nav-user-name {
  color: var(--ink);
  font-weight: 600;
  font-size: 0.85rem;
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.nav-logout {
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: var(--muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.18s ease;
}

.nav-logout:hover {
  background: #fdeaea;
  color: #d4380d;
}

.nav-role-badge {
  padding: 2px 8px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary);
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

@media (max-width: 991.98px) {
  .navbar-toggler {
    display: inline-flex;
  }

  .navbar-menu {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    right: 0;
    flex-direction: column;
    align-items: stretch;
    gap: 4px;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(16px) saturate(160%);
    -webkit-backdrop-filter: blur(16px) saturate(160%);
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: var(--shadow-lg);
    padding: 12px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px) scale(0.98);
    transition: opacity 0.25s ease, transform 0.25s ease, visibility 0.25s;
    z-index: 1029;
  }

  .navbar-menu.open {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
  }

  .navbar-menu .nav-link {
    justify-content: center;
    text-align: center;
    padding: 11px 14px;
    border-radius: 10px;
  }

  .navbar-menu .nav-link:hover {
    background: var(--primary-soft);
  }

  .navbar-menu .btn-nav-add {
    width: 100%;
    margin-top: 6px;
    padding: 9px 14px;
    border-radius: 10px;
  }

  .navbar-menu .nav-user {
    justify-content: center;
    margin: 8px 0 2px;
    padding: 7px 10px;
    width: 100%;
  }

  .navbar-menu .nav-logout {
    margin-left: auto;
  }
}
</style>