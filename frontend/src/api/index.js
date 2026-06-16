import axios from 'axios'

const TOKEN_KEY = 'admin_token'
const TOKEN_EXPIRES_KEY = 'admin_token_expires'
const ADMIN_INFO_KEY = 'admin_info'

const api = axios.create({
  baseURL: '/api',
  timeout: 30000
})

api.interceptors.request.use(
  config => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
      config.headers['X-Admin-Token'] = token
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

api.interceptors.response.use(
  response => {
    return response.data
  },
  error => {
    if (error.response?.status === 401) {
      clearAuth()
    }
    console.error('API Error:', error)
    return Promise.reject(error)
  }
)

export const setAuth = (token, expiresAt, adminInfo) => {
  localStorage.setItem(TOKEN_KEY, token)
  localStorage.setItem(TOKEN_EXPIRES_KEY, expiresAt)
  localStorage.setItem(ADMIN_INFO_KEY, JSON.stringify(adminInfo))
}

export const clearAuth = () => {
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(TOKEN_EXPIRES_KEY)
  localStorage.removeItem(ADMIN_INFO_KEY)
}

export const getAdminInfo = () => {
  try {
    const info = localStorage.getItem(ADMIN_INFO_KEY)
    return info ? JSON.parse(info) : null
  } catch {
    return null
  }
}

export const isAuthenticated = () => {
  const token = localStorage.getItem(TOKEN_KEY)
  const expires = localStorage.getItem(TOKEN_EXPIRES_KEY)
  if (!token || !expires) return false
  return new Date(expires) > new Date()
}

export default {
  getProviders() {
    return api.get('/providers')
  },
  
  search(keyword, query, page = 1) {
    return api.get(`/search/${keyword}/${encodeURIComponent(query)}/${page}`)
  },
  
  getHistory(limit = 20) {
    return api.get('/history', { params: { limit } })
  },
  
  clearHistory() {
    return api.delete('/history')
  },
  
  getFavorites() {
    return api.get('/favorites')
  },
  
  addFavorite(data) {
    return api.post('/favorites', data)
  },
  
  deleteFavorite(id) {
    return api.delete(`/favorites/${id}`)
  },

  adminLogin(username, password) {
    return api.post('/admin/login', { username, password })
  },

  adminLogout() {
    return api.post('/admin/logout')
  },

  getCurrentAdmin() {
    return api.get('/admin/me')
  },

  getResources(category = null) {
    return api.get('/resources', { params: category ? { category } : {} })
  },

  getResource(id) {
    return api.get(`/resources/${id}`)
  },

  searchResources(keyword, page = 1, perPage = 10) {
    return api.get(`/resources/search/${encodeURIComponent(keyword)}`, { params: { page, perPage } })
  },

  getResourceCategories() {
    return api.get('/resources/categories')
  },

  checkResourceMirrors(resourceId) {
    return api.post(`/resources/${resourceId}/check-all`)
  },

  getMirrors(status = null) {
    return api.get('/mirrors', { params: status ? { status } : {} })
  },

  getMirror(id) {
    return api.get(`/mirrors/${id}`)
  },

  checkMirror(mirrorId) {
    return api.post(`/mirrors/${mirrorId}/check`)
  },

  toggleMirrorAvailable(mirrorId) {
    return api.post(`/mirrors/${mirrorId}/toggle-available`)
  },

  getRadarSummary() {
    return api.get('/mirrors/radar/summary')
  },

  getAdminLogs(limit = 100, admin = null, action = null) {
    const params = { limit }
    if (admin) params.admin = admin
    if (action) params.action = action
    return api.get('/admin/logs', { params })
  },

  getAdminLogStats() {
    return api.get('/admin/logs/stats')
  },

  _auth: { setAuth, clearAuth, getAdminInfo, isAuthenticated }
}
