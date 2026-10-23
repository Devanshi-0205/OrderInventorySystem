<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = ref({ email: '', password: '' })
const error = ref(null)

async function handleLogin() {
  error.value = null
  try {
    await auth.login(form.value)
    const redirect = route.query.redirect || '/'
    router.push(redirect)
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Login failed. Please check your credentials.'
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <i class="pi pi-box" style="font-size: 2rem; color: #60a5fa;" />
        <h1>OrderInventory</h1>
        <p>Sign in to your account</p>
      </div>

      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="field">
          <label for="email">Email</label>
          <InputText
            id="email"
            v-model="form.email"
            type="email"
            placeholder="admin@example.com"
            autocomplete="email"
            required
            class="w-full"
          />
        </div>

        <div class="field">
          <label for="password">Password</label>
          <Password
            id="password"
            v-model="form.password"
            placeholder="••••••••"
            :feedback="false"
            toggleMask
            inputClass="w-full"
            required
          />
        </div>

        <Button
          type="submit"
          label="Sign In"
          icon="pi pi-sign-in"
          :loading="auth.loading"
          class="w-full"
        />
      </form>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
}

.login-card {
  background: #fff;
  border-radius: 12px;
  padding: 2.5rem;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}

.login-brand {
  text-align: center;
  margin-bottom: 1.75rem;
}
.login-brand h1 { margin: 0.5rem 0 0.25rem; font-size: 1.5rem; font-weight: 700; color: #1e293b; }
.login-brand p  { margin: 0; color: #64748b; font-size: 0.9rem; }

.login-form { display: flex; flex-direction: column; gap: 1.1rem; }

.field { display: flex; flex-direction: column; gap: 0.35rem; }
.field label { font-size: 0.85rem; font-weight: 600; color: #374151; }

.w-full { width: 100%; }
:deep(.p-password) { width: 100%; }
:deep(.p-password input) { width: 100%; }

.login-hint {
  margin-top: 1.5rem;
  font-size: 0.78rem;
  color: #94a3b8;
  text-align: center;
  line-height: 1.6;
}
code { background: #f1f5f9; padding: 0.1rem 0.3rem; border-radius: 4px; }
</style>
