import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  timeout: 30000
})

// 请求拦截器
api.interceptors.request.use(
  config => {
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
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
  // 获取搜索源
  getProviders() {
    return api.get('/providers')
  },
  
  // 搜索 Torrent
  search(keyword, query, page = 1) {
    return api.get(`/search/${keyword}/${encodeURIComponent(query)}/${page}`)
  },
  
  // 获取搜索历史
  getHistory(limit = 20) {
    return api.get('/history', { params: { limit } })
  },
  
  // 清空搜索历史
  clearHistory() {
    return api.delete('/history')
  },
  
  // 获取收藏列表
  getFavorites() {
    return api.get('/favorites')
  },
  
  // 添加收藏
  addFavorite(data) {
    return api.post('/favorites', data)
  },
  
  // 删除收藏
  deleteFavorite(id) {
    return api.delete(`/favorites/${id}`)
  }
}
