<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/components/AppLayout.vue'
import { getDashboard } from '@/api/dashboard.js'

const stats = ref(null)
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  try {
    const res = await getDashboard()
    stats.value = res.data.data
  } catch {
    error.value = 'Failed to load dashboard data.'
  } finally {
    loading.value = false
  }
})

const cards = [
  { key: 'total_products',     label: 'Total Products',    icon: 'pi-box',          color: '#3b82f6' },
  { key: 'total_orders',       label: 'Total Orders',      icon: 'pi-shopping-cart', color: '#10b981' },
  { key: 'pending_orders',     label: 'Pending Orders',    icon: 'pi-clock',         color: '#f59e0b' },
  { key: 'low_stock_products', label: 'Low Stock',         icon: 'pi-exclamation-triangle', color: '#ef4444' },
]
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="page-title">Dashboard</h1>
    </template>

    <div v-if="loading" class="loading-grid">
      <div v-for="n in 4" :key="n" class="stat-card skeleton" />
    </div>

    <p v-else-if="error" class="error-msg">{{ error }}</p>

    <div v-else class="stats-grid">
      <div
        v-for="card in cards"
        :key="card.key"
        class="stat-card"
        :style="{ '--accent': card.color }"
      >
        <div class="stat-icon">
          <i :class="`pi ${card.icon}`" />
        </div>
        <div class="stat-body">
          <span class="stat-value">{{ stats?.[card.key] ?? 0 }}</span>
          <span class="stat-label">{{ card.label }}</span>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b; }

.stats-grid, .loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.stat-card {
  background: #fff;
  border-radius: 10px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  border-left: 4px solid var(--accent, #3b82f6);
}

.stat-icon {
  width: 48px; height: 48px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--accent) 15%, white);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  color: var(--accent);
}

.stat-body { display: flex; flex-direction: column; }
.stat-value { font-size: 1.75rem; font-weight: 700; color: #1e293b; line-height: 1; }
.stat-label { font-size: 0.82rem; color: #64748b; margin-top: 0.25rem; }

.skeleton {
  height: 100px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.error-msg { color: #ef4444; }
</style>
