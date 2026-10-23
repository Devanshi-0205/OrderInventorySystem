import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getOrders, getOrder, createOrder, updateOrderStatus } from '@/api/orders.js'

export const useOrderStore = defineStore('orders', () => {
  const orders       = ref([])
  const currentOrder = ref(null)
  const pagination   = ref(null)
  const loading      = ref(false)
  const error        = ref(null)

  async function fetchOrders(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res      = await getOrders(params)
      orders.value   = res.data.data
      pagination.value = { links: res.data.links, meta: res.data.meta }
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Failed to load orders.'
    } finally {
      loading.value = false
    }
  }

  async function fetchOrder(id) {
    loading.value      = true
    error.value        = null
    currentOrder.value = null
    try {
      const res          = await getOrder(id)
      currentOrder.value = res.data.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Order not found.'
    } finally {
      loading.value = false
    }
  }

  async function placeOrder(data) {
    loading.value = true
    error.value   = null
    try {
      const res = await createOrder(data)
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Failed to place order.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function changeOrderStatus(id, status) {
    loading.value = true
    error.value   = null
    try {
      const res = await updateOrderStatus(id, status)
      if (currentOrder.value?.id === Number(id)) {
        currentOrder.value = res.data.data
      }
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Failed to update order status.'
      throw err
    } finally {
      loading.value = false
    }
  }

  return { orders, currentOrder, pagination, loading, error, fetchOrders, fetchOrder, placeOrder, changeOrderStatus }
})
