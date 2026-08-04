<template>
  <div class="auth-page">
    <div class="auth-card card-custom">
      <!-- Branding panel -->
      <aside class="auth-aside">
        <div class="auth-brand d-flex align-items-center gap-2 mb-4">
          <span class="auth-brand-mark"><i class="bi bi-music-note-list"></i></span>
          <span class="auth-brand-text fw-bold">
            SheetMusic<span class="fw-bold">Vault</span>
          </span>
        </div>

        <h2 class="fw-bold text-white mb-2">
          {{ isRegister ? 'Build your library' : 'Welcome back' }}
        </h2>
        <p class="auth-aside-sub mb-4">
          {{ isRegister
            ? 'Create an account to save and organize your classical sheet music collection.'
            : 'Sign in to access your curated digital library of classical music.' }}
        </p>

        <ul class="auth-features list-unstyled mb-0 d-none d-md-block">
          <li><i class="bi bi-check2-circle"></i> Organize pieces by composer, era & year</li>
          <li><i class="bi bi-check2-circle"></i> Attach score sheet images</li>
          <li><i class="bi bi-check2-circle"></i> Your collection, securely stored</li>
        </ul>
      </aside>

      <!-- Form panel -->
      <section class="auth-form-panel">
        <div class="auth-form-inner">
          <div class="text-center text-md-start mb-4">
            <h3 class="fw-bold mb-1" style="color: var(--ink)">
              {{ isRegister ? 'Create account' : 'Sign in' }}
            </h3>
            <p class="text-muted mb-0">
              {{ isRegister
                ? 'Enter your details to get started.'
                : 'Use your email and password to continue.' }}
            </p>
          </div>

          <!-- General error -->
          <div v-if="formError" class="alert alert-danger d-flex align-items-start" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <span class="small">{{ formError }}</span>
          </div>

          <!-- Success (register) -->
          <div v-if="notice" class="alert alert-success d-flex align-items-start" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <span class="small">{{ notice }}</span>
          </div>

          <form @submit.prevent="submit" novalidate>
            <!-- Name (register only) -->
            <div v-if="isRegister" class="mb-3">
              <label class="form-label" for="auth-name">Name <span class="req-star">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-person field-icon"></i>
                </span>
                <input
                  id="auth-name"
                  v-model.trim="form.name"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': fieldError('name') }"
                  placeholder="e.g. Johann Sebastian"
                  autocomplete="name"
                  @input="clearFieldError('name')"
                />
              </div>
              <div v-if="fieldError('name')" class="invalid-feedback d-block">{{ fieldError('name') }}</div>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label class="form-label" for="auth-email">Email <span class="req-star">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-envelope field-icon"></i>
                </span>
                <input
                  id="auth-email"
                  v-model.trim="form.email"
                  type="email"
                  class="form-control"
                  :class="{ 'is-invalid': fieldError('email') }"
                  placeholder="you@example.com"
                  autocomplete="email"
                  @input="clearFieldError('email')"
                />
              </div>
              <div v-if="fieldError('email')" class="invalid-feedback d-block">{{ fieldError('email') }}</div>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label class="form-label" for="auth-password">Password <span class="req-star">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-lock field-icon"></i>
                </span>
                <input
                  id="auth-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-invalid': fieldError('password') }"
                  :placeholder="isRegister ? 'At least 8 characters' : 'Your password'"
                  :autocomplete="isRegister ? 'new-password' : 'current-password'"
                  @input="clearFieldError('password')"
                />
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-toggle-pass"
                  tabindex="-1"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  @click="showPassword = !showPassword"
                >
                  <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
              <div v-if="fieldError('password')" class="invalid-feedback d-block">{{ fieldError('password') }}</div>
              <div v-if="isRegister && !fieldError('password')" class="form-hint">
                Use 8+ characters with a mix of letters and numbers.
              </div>
            </div>

            <button
              type="submit"
              class="btn btn-primary w-100 btn-lg mt-2"
              :disabled="authStore.loading"
            >
              <span
                v-if="authStore.loading"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              <i v-else :class="isRegister ? 'bi bi-person-plus me-2' : 'bi bi-box-arrow-in-right me-2'"></i>
              {{ isRegister ? 'Create account' : 'Sign in' }}
            </button>
          </form>

          <div class="auth-switch text-center mt-4">
            <p class="mb-0 text-muted small">
              {{ isRegister ? 'Already have an account?' : "Don't have an account?" }}
              <a href="#" class="fw-semibold auth-link" @click.prevent="toggleMode">
                {{ isRegister ? 'Sign in' : 'Create one' }}
              </a>
            </p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const isRegister = ref(false)
const showPassword = ref(false)

const form = ref({ name: '', email: '', password: '' })
const fieldErrors = ref({})
const formError = ref('')
const notice = ref('')

const toggleMode = () => {
  isRegister.value = !isRegister.value
  formError.value = ''
  notice.value = ''
  fieldErrors.value = {}
}

const fieldError = (key) => fieldErrors.value[key]
const clearFieldError = (key) => {
  delete fieldErrors.value[key]
}

const validate = () => {
  const errors = {}
  if (isRegister.value && !form.value.name) errors.name = 'Name is required.'
  if (!form.value.email) errors.email = 'Email is required.'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) errors.email = 'Please enter a valid email address.'
  if (!form.value.password) errors.password = 'Password is required.'
  else if (form.value.password.length < 8) errors.password = 'Password must be at least 8 characters.'
  return errors
}

const submit = async () => {
  formError.value = ''
  notice.value = ''
  const errors = validate()
  fieldErrors.value = errors
  if (Object.keys(errors).length > 0) return

  try {
    if (isRegister.value) {
      await authStore.register(form.value)
      notice.value = 'Account created — welcome to SheetMusic Vault!'
      redirectAfterAuth()
    } else {
      await authStore.login(form.value)
      redirectAfterAuth()
    }
  } catch (e) {
    if (e.fields) {
      fieldErrors.value = e.fields
    } else {
      formError.value = e.message
    }
  }
}

const redirectAfterAuth = () => {
  const redirect = router.currentRoute.value.query.redirect
  router.replace(redirect || '/')
}
</script>

<style scoped>
.auth-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 200px);
  padding: 24px 0;
}

.auth-card {
  display: grid;
  grid-template-columns: 1fr 1.1fr;
  width: 100%;
  max-width: 920px;
  overflow: hidden;
  border-radius: 24px;
}

/* ---- Branding panel ---- */
.auth-aside {
  background:
    radial-gradient(500px 260px at 120% -20%, rgba(88, 166, 255, 0.5), transparent 60%),
    radial-gradient(400px 240px at -20% 120%, rgba(88, 166, 255, 0.28), transparent 55%),
    var(--gradient);
  color: #fff;
  padding: 44px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.auth-brand-mark {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.25);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.auth-brand-text {
  font-family: 'Manrope', sans-serif;
  font-size: 1.1rem;
  letter-spacing: -0.01em;
}

.auth-aside-sub {
  color: rgba(255, 255, 255, 0.82);
}

.auth-features {
  margin-top: 8px;
}

.auth-features li {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 0;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.92rem;
}

.auth-features i {
  color: #bcd9ff;
  font-size: 1.05rem;
}

/* ---- Form panel ---- */
.auth-form-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface);
  padding: 44px 40px;
}

.auth-form-inner {
  width: 100%;
  max-width: 380px;
}

.btn-toggle-pass {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
  border-color: var(--border);
  background: #fbfdff;
  padding: 0 14px;
}

.btn-toggle-pass:hover {
  border-color: var(--sky);
}

.auth-link {
  color: var(--primary);
  text-decoration: none;
}

.auth-link:hover {
  text-decoration: underline;
}

/* ---- Responsive ---- */
@media (max-width: 767px) {
  .auth-page {
    min-height: calc(100vh - 170px);
    padding: 10px 0;
  }

  .auth-card {
    grid-template-columns: 1fr;
    max-width: 460px;
  }

  .auth-aside {
    padding: 26px 24px;
    border-radius: 0 0 18px 18px;
  }

  .auth-aside h2 {
    font-size: 1.3rem;
  }

  .auth-aside-sub {
    font-size: 0.85rem;
    margin-bottom: 0;
  }

  .auth-form-panel {
    padding: 30px 24px;
  }
}

@media (max-width: 575px) {
  .auth-card {
    border-radius: 18px;
  }
}
</style>
