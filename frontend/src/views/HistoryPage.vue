<template>
  <div class="space-y-8">
    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 text-center">
      <div class="flex justify-center mb-4">
        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h2 class="text-4xl font-bold text-white mb-3">搜索历史</h2>
      <p class="text-xl text-white/90">{{ history.length }} 条历史记录</p>
    </div>

    <div v-if="history.length > 0" class="flex justify-end">
      <button
        @click="showConfirmClear = true"
        class="px-5 py-2.5 bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 text-white rounded-xl font-medium transition-all flex items-center gap-2 shadow-lg"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        清空历史
      </button>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="bg-white rounded-xl p-6 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/2 mb-3"></div>
        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
      </div>
    </div>

    <div v-else-if="history.length > 0" class="space-y-4">
      <transition-group name="list" tag="div">
        <div
          v-for="item in history"
          :key="item.id"
          @click="searchAgain(item)"
          class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 cursor-pointer overflow-hidden"
        >
          <div class="p-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4 flex-1 min-w-0">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-lg font-semibold text-gray-900 truncate">{{ item.query }}</div>
                <div class="text-sm text-gray-500 mt-1">
                  来源: <span class="font-medium text-purple-600">{{ item.keyword }}</span>
                </div>
              </div>
            </div>
            <div class="text-sm text-gray-400 whitespace-nowrap flex-shrink-0">
              {{ formatDate(item.created_at) }}
            </div>
          </div>
        </div>
      </transition-group>
    </div>

    <div v-else class="text-center py-16">
      <svg class="w-24 h-24 mx-auto text-white/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-xl text-white/80 mb-6">暂无搜索历史</p>
      <button
        @click="$router.push('/')"
        class="px-8 py-3 bg-white text-purple-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors duration-200 inline-flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        去搜索
      </button>
    </div>

    <transition name="slide-up">
      <div
        v-if="toast.show"
        class="fixed bottom-8 right-8 px-6 py-4 rounded-lg shadow-2xl text-white font-medium z-50"
        :class="toast.type === 'success' ? 'bg-green-500' : toast.type === 'error' ? 'bg-red-500' : 'bg-blue-500'"
      >
        {{ toast.message }}
      </div>
    </transition>

    <transition name="fade">
      <div v-if="showConfirmClear" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl">
          <div class="flex items-start gap-4 mb-5">
            <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-1">确认清空</h3>
              <p class="text-gray-600">确定要清空所有搜索历史吗？此操作不可恢复。</p>
            </div>
          </div>
          <div class="flex gap-3">
            <button
              @click="showConfirmClear = false"
              class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors duration-200"
            >
              取消
            </button>
            <button
              @click="clearAllHistory"
              class="flex-1 px-4 py-2.5 bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 text-white font-medium rounded-xl transition-colors duration-200"
            >
              确认清空
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const history = ref([])
const loading = ref(false)
const toast = ref({ show: false, message: '', type: 'success' })
const showConfirmClear = ref(false)

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const loadHistory = async () => {
  loading.value = true
  try {
    const response = await api.getHistory(50)
    history.value = response.data || []
  } catch (error) {
    showToast('加载搜索历史失败', 'error')
  } finally {
    loading.value = false
  }
}

const clearAllHistory = async () => {
  try {
    await api.clearHistory()
    history.value = []
    showConfirmClear.value = false
    showToast('已清空搜索历史', 'success')
  } catch (error) {
    showToast('清空失败', 'error')
  }
}

const searchAgain = (item) => {
  router.push({
    path: '/',
    query: {
      keyword: item.keyword,
      query: item.query
    }
  })
}

const formatDate = (dateStr) => {
  if (!dateStr) return '未知'
  const date = new Date(dateStr.replace(' ', 'T'))
  if (isNaN(date.getTime())) return dateStr
  const now = new Date()
  const diff = now - date

  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return '刚刚'
  if (minutes < 60) return `${minutes}分钟前`
  if (hours < 24) return `${hours}小时前`
  if (days < 7) return `${days}天前`

  return date.toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit' })
}

onMounted(() => {
  loadHistory()
})
</script>

<style scoped>
.list-enter-active {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.list-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 1, 1);
}
.list-enter-from {
  opacity: 0;
  transform: translateX(-30px) scale(0.95);
}
.list-leave-to {
  opacity: 0;
  transform: translateX(30px) scale(0.95);
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
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
