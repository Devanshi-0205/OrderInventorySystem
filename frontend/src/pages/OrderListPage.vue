<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/orders.js'
import AppLayout from '@/components/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const store  = useOrderStore()
const router = useRouter()

const statusFilter = ref(null)

const statusOptions = [
  { label: 'All Statuses', value: null },
  { label: 'Pending',    value: 'pending' },
  { label: 'Confirmed',  value: 'confirmed' },
  { label: 'Cancelled',  value: 'cancelled' },
]
const statusSeverity = { pending: 'warn', confirmed: 'success', cancelled: 'danger' }

function load(page = 1) {
  store.fetchOrders({ page, status: statusFilter.value || undefined })
}

onMounted(() => load())
watch(statusFilter, () => load())
function onPage(e) { load(e.page + 1) }
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="page-title">Orders</h1>
      <Button
        label="New Order"
        icon="pi pi-plus"
        size="small"
        @click="router.push({ name: 'order-create' })"
        style="margin-left: auto;"
      />
    </template>

    <div class="filters">
      <Select
        v-model="statusFilter"
        :options="statusOptions"
        optionLabel="label"
        optionValue="value"
        placeholder="Filter by status"
      />
    </div>

    <div class="card">
      <DataTable
        :value="store.orders"
        :loading="store.loading"
        lazy
        :totalRecords="store.pagination?.meta?.total"
        :rows="20"
        paginator
        @page="onPage"
        rowHover
        dataKey="id"
        emptyMessage="No orders found."
      >
        <Column field="id" header="#" style="width: 70px;" />
        <Column header="Customer">
          <template #body="{ data }">{{ data.user?.name ?? '—' }}</template>
        </Column>
        <Column header="Status" style="width: 130px;">
          <template #body="{ data }">
            <Tag :value="data.status" :severity="statusSeverity[data.status] ?? 'info'" style="text-transform: capitalize;" />
          </template>
        </Column>
        <Column header="Total" style="width: 110px;">
          <template #body="{ data }">$ {{ Number(data.total_amount).toFixed(2) }}</template>
        </Column>
        <Column header="Date" style="width: 160px;">
          <template #body="{ data }">{{ new Date(data.created_at).toLocaleString() }}</template>
        </Column>
        <Column header="" style="width: 80px;">
          <template #body="{ data }">
            <Button icon="pi pi-eye" text rounded size="small" @click="router.push({ name: 'order-detail', params: { id: data.id } })" />
          </template>
        </Column>
      </DataTable>
    </div>

    <p v-if="store.error" class="error-msg">{{ store.error }}</p>
  </AppLayout>
</template>

<style scoped>
.page-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b; }
.filters { margin-bottom: 1rem; }
.card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
.error-msg { color: #ef4444; margin-top: 0.75rem; }
</style>
