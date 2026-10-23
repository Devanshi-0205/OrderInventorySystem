import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getProducts, getProduct, createProduct } from '@/api/products.js'

export const useProductStore = defineStore('products', () => {
  const products       = ref([])
  const currentProduct = ref(null)
  const pagination     = ref(null)
  const loading        = ref(false)
  const error          = ref(null)

  async function fetchProducts(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res       = await getProducts(params)
      products.value  = res.data.data
      pagination.value = { links: res.data.links, meta: res.data.meta }
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Failed to load products.'
    } finally {
      loading.value = false
    }
  }

  async function fetchProduct(id) {
    loading.value        = true
    error.value          = null
    currentProduct.value = null
    try {
      const res            = await getProduct(id)
      currentProduct.value = res.data.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Product not found.'
    } finally {
      loading.value = false
    }
  }

  async function addProduct(data) {
    loading.value = true
    error.value   = null
    try {
      const res = await createProduct(data)
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Failed to create product.'
      throw err
    } finally {
      loading.value = false
    }
  }

  return { products, currentProduct, pagination, loading, error, fetchProducts, fetchProduct, addProduct }
})
