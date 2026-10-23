<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProductStore } from '@/stores/products.js'
import { useToast } from 'primevue/usetoast'
import AppLayout from '@/components/AppLayout.vue'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'

const store  = useProductStore()
const router = useRouter()
const toast  = useToast()

const form = ref({ name: '', sku: '', description: '', price: null, stock_quantity: 0 })
const fieldErrors = ref({})

async function handleSubmit() {
  fieldErrors.value = {}
  try {
    await store.addProduct(form.value)
    toast.add({ severity: 'success', summary: 'Product created successfully.', life: 3000 })
    router.push({ name: 'products' })
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors ?? {}
    } else {
      toast.add({ severity: 'error', summary: store.error ?? 'Failed to create product.', life: 4000 })
    }
  }
}

function fieldError(key) {
  return fieldErrors.value[key]?.[0]
}
</script>

<template>
  <AppLayout>
    <template #header>
      <Button icon="pi pi-arrow-left" text @click="router.back()" style="margin-right: 0.5rem;" />
      <h1 class="page-title">Add Product</h1>
    </template>

    <div class="form-card">
      <form @submit.prevent="handleSubmit" novalidate>
        <div class="fields">
          <!-- Name -->
          <div class="field">
            <label for="name">Product Name <span class="req">*</span></label>
            <InputText id="name" v-model="form.name" placeholder="e.g. Wireless Mouse" />
            <small class="error" v-if="fieldError('name')">{{ fieldError('name') }}</small>
          </div>

          <!-- SKU -->
          <div class="field">
            <label for="sku">SKU <span class="req">*</span></label>
            <InputText id="sku" v-model="form.sku" placeholder="e.g. ELC-001" />
            <small class="error" v-if="fieldError('sku')">{{ fieldError('sku') }}</small>
          </div>

          <!-- Price -->
          <div class="field">
            <label for="price">Price ($) <span class="req">*</span></label>
            <InputNumber id="price" v-model="form.price" mode="decimal" :minFractionDigits="2" :maxFractionDigits="2" :min="0" placeholder="0.00" />
            <small class="error" v-if="fieldError('price')">{{ fieldError('price') }}</small>
          </div>

          <!-- Stock -->
          <div class="field">
            <label for="stock">Initial Stock <span class="req">*</span></label>
            <InputNumber id="stock" v-model="form.stock_quantity" :min="0" :useGrouping="false" placeholder="0" />
            <small class="error" v-if="fieldError('stock_quantity')">{{ fieldError('stock_quantity') }}</small>
          </div>

          <!-- Description -->
          <div class="field full">
            <label for="desc">Description</label>
            <Textarea id="desc" v-model="form.description" rows="3" placeholder="Optional product description…" autoResize />
          </div>
        </div>

        <div class="actions">
          <Button label="Cancel" severity="secondary" text @click="router.back()" />
          <Button type="submit" label="Create Product" icon="pi pi-check" :loading="store.loading" />
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b; }
.form-card { background: #fff; border-radius: 10px; padding: 1.75rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); max-width: 700px; }
.fields { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field.full { grid-column: 1 / -1; }
label { font-size: 0.85rem; font-weight: 600; color: #374151; }
.req { color: #ef4444; }
.error { color: #ef4444; font-size: 0.78rem; }
.actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; }
:deep(.p-inputnumber) { width: 100%; }
:deep(.p-inputnumber input) { width: 100%; }
:deep(.p-textarea) { width: 100%; }
</style>
