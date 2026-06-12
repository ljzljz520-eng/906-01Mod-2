import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  timeout: 30000
})

api.interceptors.request.use(
  config => {
    const adminName = localStorage.getItem('admin_name')
    if (adminName) {
      config.headers['X-Admin-Name'] = adminName
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
    console.error('API Error:', error)
    return Promise.reject(error)
  }
)

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

  checkResourceMirrors(resourceId, adminName = null) {
    return api.post(`/resources/${resourceId}/check-all`, { admin_name: adminName })
  },

  getMirrors(status = null) {
    return api.get('/mirrors', { params: status ? { status } : {} })
  },

  getMirror(id) {
    return api.get(`/mirrors/${id}`)
  },

  checkMirror(mirrorId, adminName = null) {
    return api.post(`/mirrors/${mirrorId}/check`, { admin_name: adminName })
  },

  toggleMirrorAvailable(mirrorId, adminName = null) {
    return api.post(`/mirrors/${mirrorId}/toggle-available`, { admin_name: adminName })
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
  }
}
