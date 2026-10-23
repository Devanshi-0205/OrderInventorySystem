<script setup>
import { useAuthStore } from '@/stores/auth.js'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="app-shell">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-brand">
        <span class="brand-icon pi pi-box" />
        <span class="brand-name">OrderInventory</span>
      </div>

      <nav class="sidebar-nav">
        <RouterLink :to="{ name: 'dashboard' }" class="nav-item">
          <i class="pi pi-home" /> Dashboard
        </RouterLink>
        <RouterLink :to="{ name: 'products' }" class="nav-item">
          <i class="pi pi-box" /> Products
        </RouterLink>
        <RouterLink :to="{ name: 'product-create' }" class="nav-item nav-sub" v-if="auth.isAdmin">
          <i class="pi pi-plus-circle" /> Add Product
        </RouterLink>
        <RouterLink :to="{ name: 'orders' }" class="nav-item">
          <i class="pi pi-shopping-cart" /> Orders
        </RouterLink>
        <RouterLink :to="{ name: 'order-create' }" class="nav-item nav-sub">
          <i class="pi pi-plus-circle" /> New Order
        </RouterLink>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <i class="pi pi-user" />
          <span>{{ auth.user?.name }}</span>
          <span class="role-badge">{{ auth.user?.role }}</span>
        </div>
        <Button
          label="Logout"
          icon="pi pi-sign-out"
          severity="secondary"
          size="small"
          text
          @click="handleLogout"
        />
      </div>
    </aside>

    <!-- Main content -->
    <div class="main-area">
      <header class="topbar">
        <slot name="header">
          <h1 class="page-title">{{ $route.meta.title ?? 'Dashboard' }}</h1>
        </slot>
      </header>
      <main class="page-content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.app-shell {
  display: flex;
  height: 100vh;
  overflow: hidden;
  font-family: var(--p-font-family, system-ui, sans-serif);
}

/* ── Sidebar ── */
.sidebar {
  width: 240px;
  min-width: 240px;
  background: #1e293b;
  color: #f1f5f9;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 1.25rem 1.25rem 1rem;
  font-size: 1.1rem;
  font-weight: 700;
  border-bottom: 1px solid #334155;
}

.brand-icon { font-size: 1.3rem; color: #60a5fa; }

.sidebar-nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 0.75rem 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 1.25rem;
  color: #cbd5e1;
  text-decoration: none;
  font-size: 0.9rem;
  border-left: 3px solid transparent;
  transition: background 0.15s, border-color 0.15s;
}

.nav-item:hover { background: #334155; color: #f1f5f9; }
.nav-item.router-link-active { background: #0f172a; border-left-color: #60a5fa; color: #fff; }

.nav-sub { padding-left: 2.5rem; font-size: 0.85rem; }

.sidebar-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #334155;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #94a3b8;
}

.role-badge {
  background: #334155;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  font-size: 0.75rem;
  text-transform: capitalize;
}

/* ── Main area ── */
.main-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #f8fafc;
}

.topbar {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 1.75rem;
  display: flex;
  align-items: center;
}

.page-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b; }

.page-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.75rem;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .sidebar { display: none; }
}
</style>
