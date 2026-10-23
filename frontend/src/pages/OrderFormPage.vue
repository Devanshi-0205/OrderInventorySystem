<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/orders.js'
import { useProductStore } from '@/stores/products.js'
import { useToast } from 'primevue/usetoast'
import AppLayout from '@/components/AppLayout.vue'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Message from 'primevue/message'

const orderStore   = useOrderStore()
const productStore = useProductStore()
const router       = useRouter()
const toast        = useToast()

const items    = ref([])   // [{ product: {...}, quantity: 1 }]
const selected = ref(null) // product chosen in dropdown
const qty      = ref(1)
const error    = ref(null)

onMounted(() => productStore.fetchProducts({ stock_status: 'in_stock', page: 1 }))

const availableProducts = computed(() =>
  productStore.products.filter(p => !items.value.find(i => i.product.id === p.id))
)

function addItem() {
  if (!selected.value || qty.value < 1) return
  if (qty.value > selected.value.stock_quantity) {
    toast.add({ severity: 'warn', summary: `Only ${selected.value.stock_quantity} units available.`, life: 3000 })
    return
  }
  items.value.push({ product: selected.value, quantity: qty.value })
  selected.value = null
  qty.value = 1
}

function removeItem(index) { items.value.splice(index, 1) }

const estimatedTotal = computed(() =>
  items.value.reduce((sum, i) => sum + i.product.price * i.quantity, 0)
)

async function handleSubmit() {
  error.value = null
  if (!items.value.length) {
    error.value = 'Add at least one product to place an order.'
    return
  }
  try {
    await orderStore.placeOrder({
      items: items.value.map(i => ({ product_id: i.product.id, quantity: i.quantity })),
    })
    toast.add({ severity: 'success', summary: 'Order placed successfully!', life: 3000 })
    router.push({ name: 'orders' })
  } catch (err) {
    if (err.response?.status === 409) {
      error.value = err.response.data.message
    } else if (err.response?.status === 422) {
      error.value = Object.values(err.response.data.errors ?? {}).flat().join(' ')
    } else {
      error.value = orderStore.error ?? 'Failed to place order.'
    }
  }
}
</script>

<template>
  <AppLayout>
    <template #header>
      <Button icon="pi pi-arrow-left" text @click="router.back()" style="margin-right: 0.5rem;" />
      <h1 class="page-title">New Order</h1>
    </template>

    <div class="form-card">
      <!-- Product selector -->
      <h3 class="section-title">Add Items</h3>
      <div class="item-row">
        <Select
          v-model="selected"
          :options="availableProducts"
          optionLabel="name"
          placeholder="Select product…"
          class="product-select"
          filter
        >
          <template #option="{ option }">
            <div>
              <span>{{ option.name }}</span>
              <small class="opt-meta">{{ option.sku }} · ${{ Number(option.price).toFixed(2) }} · {{ option.stock_quantity }} in stock</small>
            </div>
          </template>
        </Select>

        <InputNumber v-model="qty" :min="1" :max="selected?.stock_quantity ?? 9999" showButtons class="qty-input" />
        <Button label="Add" icon="pi pi-plus" @click="addItem" :disabled="!selected" />
      </div>

      <!-- Order items table -->
      <DataTable :value="items" class="items-table" emptyMessage="No items added yet.">
        <Column header="Product">
          <template #body="{ data }">
            <div>
              <strong>{{ data.product.name }}</strong>
              <small style="color: #64748b; display: block;">{{ data.product.sku }}</small>
            </div>
          </template>
        </Column>
        <Column header="Unit Price" style="width: 110px;">
          <template #body="{ data }">$ {{ Number(data.product.price).toFixed(2) }}</template>
        </Column>
        <Column header="Qty" style="width: 80px;">
          <template #body="{ data }">{{ data.quantity }}</template>
        </Column>
        <Column header="Line Total" style="width: 110px;">
          <template #body="{ data }">$ {{ (data.product.price * data.quantity).toFixed(2) }}</template>
        </Column>
        <Column style="width: 60px;">
          <template #body="{ _, index }">
            <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="removeItem(index)" />
          </template>
        </Column>
      </DataTable>

      <!-- Estimated total (informational only — backend recalculates) -->
      <div class="total-row" v-if="items.length">
        <span>Estimated Total</span>
        <span class="total-value">$ {{ estimatedTotal.toFixed(2) }}</span>
      </div>
      <p class="total-note">* Final total is calculated by the server using current prices.</p>

      <Message v-if="error" severity="error" :closable="false" style="margin-top: 1rem;">{{ error }}</Message>

      <div class="actions">
        <Button label="Cancel" severity="secondary" text @click="router.back()" />
        <Button
          type="button"
          label="Place Order"
          icon="pi pi-check"
          :loading="orderStore.loading"
          :disabled="!items.length"
          @click="handleSubmit"
        />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b; }
.form-card { background: #fff; border-radius: 10px; padding: 1.75rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); max-width: 800px; }
.section-title { margin: 0 0 0.75rem; font-size: 0.95rem; font-weight: 600; color: #374151; }
.item-row { display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; }
.product-select { flex: 1; min-width: 220px; }
.qty-input { width: 130px; }
.opt-meta { color: #94a3b8; display: block; font-size: 0.78rem; }
.items-table { margin-bottom: 0.5rem; }
.total-row { display: flex; justify-content: flex-end; gap: 1rem; align-items: center; padding: 0.75rem 0; border-top: 1px solid #e2e8f0; font-weight: 600; color: #1e293b; }
.total-value { font-size: 1.2rem; color: #10b981; }
.total-note { font-size: 0.75rem; color: #94a3b8; margin: 0.25rem 0 0; text-align: right; }
.actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; }
:deep(.p-inputnumber) { width: 100%; }
</style>
