<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useProductStore } from '@/stores/products.js'
import AppLayout from '@/components/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const auth = useAuthStore()
const store = useProductStore()
const router = useRouter()

const search = ref('')
const stockFilter = ref(null)

const stockOptions = [
  { label: 'All Stock', value: null },
  { label: 'In Stock',     value: 'in_stock' },
  { label: 'Low Stock',    value: 'low_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
]

const stockSeverity = { in_stock: 'success', low_stock: 'warn', out_of_stock: 'danger' }
const stockLabel    = { in_stock: 'In Stock', low_stock: 'Low Stock', out_of_stock: 'Out of Stock' }

function load(page = 1) {
  store.fetchProducts({
    page,
    search: search.value || undefined,
    stock_status: stockFilter.value || undefined,
  })
}

onMounted(() => load())

let debounceTimer
watch(search, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => load(), 350)
})
watch(stockFilter, () => load())

function onPage(e) { load(e.page + 1) }
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="page-title">Products</h1>
      <Button
        v-if="auth.isAdmin"
        label="Add Product"
        icon="pi pi-plus"
        size="small"
        @click="router.push({ name: 'product-create' })"
        style="margin-left: auto;"
      />
    </template>

    <!-- Filters -->
    <div class="filters">
      <InputText v-model="search" placeholder="Search name or SKU…" class="search-input" />
      <Select
        v-model="stockFilter"
        :options="stockOptions"
        optionLabel="label"
        optionValue="value"
        placeholder="Stock status"
        class="stock-select"
      />
    </div>

    <!-- Table -->
    <div class="card">
      <DataTable
        :value="store.products"
        :loading="store.loading"
        lazy
        :totalRecords="store.pagination?.meta?.total"
        :rows="20"
        paginator
        @page="onPage"
        rowHover
        dataKey="id"
        emptyMessage="No products found."
      >
        <Column field="sku"  header="SKU"   style="width: 130px;" />
        <Column field="name" header="Name" />
        <Column field="price" header="Price" style="width: 110px;">
          <template #body="{ data }">$ {{ Number(data.price).toFixed(2) }}</template>
        </Column>
        <Column field="stock_quantity" header="Stock" style="width: 90px; text-align: right;" />
        <Column header="Status" style="width: 130px;">
          <template #body="{ data }">
            <Tag
              :value="stockLabel[data.stock_status] ?? data.stock_status"
              :severity="stockSeverity[data.stock_status] ?? 'info'"
            />
          </template>
        </Column>
        <Column header="" style="width: 80px;">
          <template #body="{ data }">
            <Button
              icon="pi pi-eye"
              text
              rounded
              size="small"
              @click="router.push({ name: 'product-detail', params: { id: data.id } })"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <p v-if="store.error" class="error-msg">{{ store.error }}</p>
  </AppLayout>
</template>

<style scoped>
.page-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b; }
.filters { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-input { flex: 1; min-width: 200px; }
.stock-select { min-width: 160px; }
.card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
.error-msg { color: #ef4444; margin-top: 0.75rem; }
</style>
