import api from './axios.js'

export const getProducts   = (params = {}) => api.get('/products', { params })
export const getProduct    = (id)           => api.get(`/products/${id}`)
export const createProduct = (data)         => api.post('/products', data)
