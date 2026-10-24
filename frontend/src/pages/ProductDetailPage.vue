<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useProductStore } from '@/stores/products.js'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import AppLayout from '@/components/AppLayout.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'

const route   = useRoute()
const router  = useRouter()
const auth    = useAuthStore()
const store   = useProductStore()
const toast   = useToast()
const confirm = useConfirm()

onMounted(() => store.fetchProduct(route.params.id))

const stockSeverity = { in_stock: 'success', low_stock: 'warn', out_of_stock: 'danger' }
const stockLabel    = { in_stock: 'In Stock', low_stock: 'Low Stock', out_of_stock: 'Out of Stock' }

function confirmDelete() {
  confirm.require({
    message: `Are you sure you want to delete "${store.currentProduct?.name}"? This action cannot be undone.`,
    header: 'Delete Product',
    icon: 'pi pi-exclamation-triangle',
    acceptSeverity: 'danger',
    acceptLabel: 'Delete',
    rejectLabel: 'Cancel',
    accept: async () => {
      try {
        await store.removeProduct(route.params.id)
        toast.add({ severity: 'success', summary: 'Product deleted successfully.', life: 3000 })
        router.push({ name: 'products' })
      } catch {
        toast.add({ severity: 'error', summary: store.error ?? 'Failed to delete product.', life: 4000 })
      }
    },
  })
}
</script>

<template>
  <AppLayout>
    <template #header>
      <Button icon="pi pi-arrow-left" text @click="router.back()" style="margin-right: 0.5rem;" />
      <h1 class="page-title">Product Detail</h1>
      <!-- Admin actions -->
      <template v-if="auth.isAdmin && store.currentProduct">
        <Button
          label="Edit"
          icon="pi pi-pencil"
          severity="secondary"
          size="small"
          style="margin-left: auto; margin-right: 0.5rem;"
          @click="router.push({ name: 'product-edit', params: { id: route.params.id } })"
        />
        <Button
          label="Delete"
          icon="pi pi-trash"
          severity="danger"
          size="small"
          :loading="store.loading"
          @click="confirmDelete"
        />
      </template>
    </template>

    <div v-if="store.loading" class="skeleton-card" />

    <p v-else-if="store.error" class="error-msg">{{ store.error }}</p>

    <div v-else-if="store.currentProduct" class="detail-card">
      <div class="detail-header">
        <div>
          <h2 class="product-name">{{ store.currentProduct.name }}</h2>
          <span class="sku">SKU: {{ store.currentProduct.sku }}</span>
        </div>
        <Tag
          :value="stockLabel[store.currentProduct.stock_status] ?? store.currentProduct.stock_status"
          :severity="stockSeverity[store.currentProduct.stock_status] ?? 'info'"
          style="font-size: 0.85rem;"
        />
      </div>

      <div class="detail-grid">
        <div class="detail-item">
          <span class="detail-label">Price</span>
          <span class="detail-value price">$ {{ Number(store.currentProduct.price).toFixed(2) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Stock Quantity</span>
          <span class="detail-value" :class="{ 'low': store.currentProduct.stock_status !== 'in_stock' }">
            {{ store.currentProduct.stock_quantity }}
          </span>
        </div>
        <div class="detail-item full">
          <span class="detail-label">Description</span>
          <span class="detail-value">{{ store.currentProduct.description || '—' }}</span>
        </div>
      </div>
    </div>
  </AppLayout>

  <!-- Confirmation dialog (rendered outside AppLayout for proper overlay) -->
  <ConfirmDialog />
</template>

<style scoped>
.page-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b; }
.detail-card { background: #fff; border-radius: 10px; padding: 1.75rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); max-width: 700px; }
.detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.product-name { margin: 0 0 0.25rem; font-size: 1.4rem; font-weight: 700; color: #1e293b; }
.sku { font-size: 0.85rem; color: #64748b; font-family: monospace; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
.detail-item { display: flex; flex-direction: column; gap: 0.25rem; }
.detail-item.full { grid-column: 1 / -1; }
.detail-label { font-size: 0.78rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.detail-value { font-size: 1rem; color: #1e293b; }
.price { font-size: 1.3rem; font-weight: 700; color: #10b981; }
.low { color: #f59e0b; font-weight: 600; }
.skeleton-card { height: 220px; border-radius: 10px; background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.error-msg { color: #ef4444; }
</style>
