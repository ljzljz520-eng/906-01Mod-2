<template>
  <div class="space-y-8">
    <!-- Hero Section -->
    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 sm:p-12 text-center">
      <div class="flex justify-center mb-4">
        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <h2 class="text-4xl sm:text-5xl font-bold text-white mb-3">Torrent 资源搜索</h2>
      <p class="text-xl text-white/90">探索海量资源，一键搜索下载</p>
    </div>

    <!-- Search Card -->
    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">
      <div class="flex gap-3">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="输入电影、剧集、软件等关键词..."
            class="w-full px-6 py-4 text-lg border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
          />
        </div>
        <button
          @click="handleSearch"
          :disabled="loading"
          class="px-8 py-4 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold rounded-xl hover:from-purple-700 hover:to-purple-800 focus:ring-4 focus:ring-purple-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <svg v-if="!loading" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <svg v-else class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="hidden sm:inline">{{ loading ? '搜索中...' : '搜索' }}</span>
        </button>
      </div>
    </div>

    <!-- Loading Skeletons -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="bg-white rounded-xl p-6 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
        <div class="flex gap-2">
          <div class="h-8 bg-gray-200 rounded w-20"></div>
          <div class="h-8 bg-gray-200 rounded w-24"></div>
        </div>
      </div>
    </div>

    <!-- Search Results -->
    <div v-else-if="results.length > 0" class="space-y-6">
      <div class="flex justify-between items-center px-2">
        <h3 class="text-2xl font-bold text-white">搜索结果</h3>
        <span class="text-white/80">找到 {{ results.length }} 个相关资源</span>
      </div>

      <transition-group name="list" tag="div" class="space-y-4">
        <div
          v-for="(item, index) in results"
          :key="index"
          class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 overflow-hidden"
        >
          <div class="p-6">
            <div class="flex justify-between items-start gap-4 mb-4">
              <div class="flex-1">
                <h4 class="text-xl font-semibold text-gray-900 mb-3 leading-tight">
                  {{ item.Name || item.title }}
                </h4>
                <div class="flex flex-wrap gap-2 items-center">
                  <span v-if="item.Category" class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium whitespace-nowrap">
                    {{ item.Category }}
                  </span>
                  <span v-if="item.Size" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium whitespace-nowrap flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ item.Size }}</span>
                  </span>
                </div>
              </div>
              <button
                @click="addToFavorites(item)"
                class="flex-shrink-0 p-3 transition-all duration-200 hover:scale-110 hover:rotate-12 rounded-full"
                :class="item.isFavorited ? 'bg-red-100 text-red-600' : 'bg-yellow-100 hover:bg-yellow-200 text-yellow-600'"
                :title="item.isFavorited ? '已收藏' : '添加收藏'"
                :disabled="item.isFavorited"
              >
                <!-- Filled Heart for Favorited -->
                <svg v-if="item.isFavorited" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                </svg>
                <!-- Outline Heart for Not Favorited -->
                <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
              </button>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4 p-4 bg-gradient-to-r from-green-50 to-yellow-50 rounded-lg">
              <div class="flex items-center space-x-2 text-green-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <div>
                  <div class="text-xs text-gray-600">做种</div>
                  <div class="font-bold text-lg">{{ item.Seeders || 0 }}</div>
                </div>
              </div>
              <div class="flex items-center space-x-2 text-yellow-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                <div>
                  <div class="text-xs text-gray-600">下载</div>
                  <div class="font-bold text-lg">{{ item.Leechers || 0 }}</div>
                </div>
              </div>
            </div>

            <button
              v-if="item.Magnet"
              @click="copyMagnet(item.Magnet)"
              class="w-full py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-lg transition-all duration-200 flex items-center justify-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
              <span>复制磁力链接</span>
            </button>
          </div>
        </div>
      </transition-group>
    </div>

    <!-- Empty State -->
    <div v-else-if="searched && !loading" class="text-center py-16">
      <svg class="w-24 h-24 mx-auto text-white/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-xl text-white/80 mb-6">未找到相关资源，请尝试其他关键词</p>
      <button
        @click="searched = false"
        class="px-6 py-3 bg-white text-purple-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors duration-200"
      >
        返回
      </button>
    </div>

    <!-- Toast Notification -->
    <transition name="slide-up">
      <div
        v-if="toast.show"
        class="fixed bottom-8 right-8 px-6 py-4 rounded-lg shadow-2xl text-white font-medium z-50"
        :class="toast.type === 'success' ? 'bg-green-500' : toast.type === 'error' ? 'bg-red-500' : 'bg-blue-500'"
      >
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../api'

const searchQuery = ref('')
const loading = ref(false)
const results = ref([])
const searched = ref(false)
const toast = ref({ show: false, message: '', type: 'success' })

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const handleSearch = async () => {
  if (!searchQuery.value.trim()) {
    showToast('请输入搜索关键词', 'error')
    return
  }

  loading.value = true
  searched.value = true

  try {
    const response = await api.search('1337x', searchQuery.value, 1)
    results.value = response.data || []
    
    if (results.value.length === 0) {
      showToast('未找到相关资源', 'info')
    } else {
      showToast(`找到 ${results.value.length} 个相关资源`, 'success')
    }
  } catch (error) {
    showToast('搜索失败: ' + (error.response?.data?.message || error.message), 'error')
    results.value = []
  } finally {
    loading.value = false
  }
}

const addToFavorites = async (item) => {
  if (item.isFavorited) return

  try {
    await api.addFavorite({
      name: item.Name || item.title,
      magnet: item.Magnet,
      size: item.Size,
      seeders: item.Seeders,
      leechers: item.Leechers,
      category: item.Category,
      source: '1337x'
    })
    item.isFavorited = true
    showToast('收藏成功', 'success')
  } catch (error) {
    if (error.response?.data?.message?.includes('已在收藏列表中')) {
      item.isFavorited = true
      showToast('该资源已在收藏列表中', 'info')
    } else {
      showToast(error.response?.data?.message || '收藏失败', 'error')
    }
  }
}

const copyMagnet = (magnet) => {
  navigator.clipboard.writeText(magnet)
  showToast('磁力链接已复制到剪贴板', 'success')
}
</script>

<style scoped>
.list-enter-active {
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.list-enter-from {
  opacity: 0;
  transform: translateY(30px) scale(0.95);
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
