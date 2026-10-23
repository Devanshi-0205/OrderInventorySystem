import api from './axios.js'

export const login   = (credentials) => api.post('/login', credentials)
export const logout  = () => api.post('/logout')
export const getUser = () => api.get('/user')
