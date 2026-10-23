<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/orders.js'
import { useAuthStore } from '@/stores/auth.js'
import { useToast } from 'primevue/usetoast'
import AppLayout from '@/components/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Button from 'primevue/button'

const route      = useRoute()
const router     = useRouter()
const orderStore = useOrderStore()
const auth       = useAuthStore()
const toast      = useToast()

const newStatus = ref(null)
const updating  = ref(false)

onMounted(() => orderStore.fetchOrder(route.params.id))

const statusOptions = [
  { label: 'Pending',   value: 'pending' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Cancelled', value: 'cancelled' },
]
const statusSeverity = { pending: 'warn', confirmed: 'success', cancelled: 'danger' }

async function handleStatusUpdate() {
  if (!newStatus.value) return
  updating.value = true
  try {
    await orderStore.changeOrderStatus(route.params.id, newStatus.value)
    toast.add({ severity: 'success', summary: 'Order status updated.', life: 3000 })
    newStatus.value = null
  } catch {
    toast.add({ severity: 'error', summary: orderStore.error ?? 'Failed to update status.', life: 4000 })
  } finally {
    updating.value = false
  }
}
</script>

<template>
  <AppLayout>
    <template #header>
      <Button icon="pi pi-arrow-left" text @click="router.back()" style="margin-right: 0.5rem;" />
      <h1 class="page-title">Order #{{ orderStore.currentOrder?.id ?? '…' }}</h1>
    </template>

    <div v-if="orderStore.loading" class="skeleton-card" />
    <p v-else-if="orderStore.error" class="error-msg">{{ orderStore.error }}</p>

    <template v-else-if="orderStore.currentOrder">
      <div class="detail-grid">
        <!-- Summary card -->
        <div class="card summary-card">
          <div class="summary-row">
            <span class="label">Customer</span>
            <span>{{ orderStore.currentOrder.user?.name ?? '—' }}</span>
          </div>
          <div class="summary-row">
            <span class="label">Status</span>
            <Tag
              :value="orderStore.currentOrder.status"
              :severity="statusSeverity[orderStore.currentOrder.status] ?? 'info'"
              style="text-transform: capitalize;"
            />
          </div>
          <div class="summary-row">
            <span class="label">Order Total</span>
            <span class="total">$ {{ Number(orderStore.currentOrder.total_amount).toFixed(2) }}</span>
          </div>
          <div class="summary-row">
            <span class="label">Placed At</span>
            <span>{{ new Date(orderStore.currentOrder.created_at).toLocaleString() }}</span>
          </div>

          <!-- Status update (admin only) -->
          <template v-if="auth.isAdmin">
            <hr class="divider" />
            <p class="label" style="margin-bottom: 0.5rem;">Update Status</p>
            <div class="status-form">
              <Select
                v-model="newStatus"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Choose status…"
                class="status-select"
              />
              <Button
                label="Update"
                icon="pi pi-check"
                size="small"
                :loading="updating"
                :disabled="!newStatus"
                @click="handleStatusUpdate"
              />
            </div>
          </template>
        </div>

        <!-- Items table -->
        <div class="card">
          <h3 class="card-title">Order Items</h3>
          <DataTable :value="orderStore.currentOrder.items" dataKey="id" emptyMessage="No items.">
            <Column header="Product">
              <template #body="{ data }">
                <div>
                  <strong>{{ data.product?.name ?? '—' }}</strong>
                  <small style="color: #64748b; display: block;">{{ data.product?.sku }}</small>
                </div>
              </template>
            </Column>
            <Column header="Unit Price" style="width: 110px;">
              <template #body="{ data }">$ {{ Number(data.unit_price).toFixed(2) }}</template>
            </Column>
            <Column field="quantity" header="Qty" style="width: 70px;" />
            <Column header="Line Total" style="width: 110px;">
              <template #body="{ data }">$ {{ (data.unit_price * data.quantity).toFixed(2) }}</template>
            </Column>
          </DataTable>
          <div class="order-total-row">
            <span>Order Total</span>
            <span class="total">$ {{ Number(orderStore.currentOrder.total_amount).toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>

<style scoped>
.page-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b; }
.detail-grid { display: grid; grid-template-columns: 280px 1fr; gap: 1.25rem; align-items: start; }
.card { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.summary-card { display: flex; flex-direction: column; gap: 0.75rem; }
.summary-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; }
.label { font-size: 0.78rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.total { font-size: 1.15rem; font-weight: 700; color: #10b981; }
.divider { border: none; border-top: 1px solid #e2e8f0; margin: 0.25rem 0; }
.status-form { display: flex; gap: 0.5rem; align-items: center; }
.status-select { flex: 1; }
.card-title { margin: 0 0 0.75rem; font-size: 0.95rem; font-weight: 600; color: #374151; }
.order-total-row { display: flex; justify-content: flex-end; gap: 1rem; padding: 0.75rem 0 0; border-top: 1px solid #e2e8f0; font-weight: 600; }
.skeleton-card { height: 300px; border-radius: 10px; background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.error-msg { color: #ef4444; }
@media (max-width: 700px) { .detail-grid { grid-template-columns: 1fr; } }
</style>
