<template>
  <div class="history-page">
    <el-card class="page-header" shadow="never">
      <div class="header-content">
        <h2>
          <el-icon><Clock /></el-icon>
          搜索历史
        </h2>
        <el-button
          v-if="history.length > 0"
          type="danger"
          size="small"
          @click="clearAllHistory"
        >
          清空历史
        </el-button>
      </div>
    </el-card>

    <div v-if="loading" class="history-loading">
      <el-skeleton animated>
        <template #template>
          <el-skeleton-item variant="text" v-for="i in 10" :key="i" />
        </template>
      </el-skeleton>
    </div>

    <div v-else-if="history.length > 0" class="history-list">
      <el-card
        v-for="item in history"
        :key="item.id"
        class="history-item"
        shadow="hover"
        @click="searchAgain(item)"
      >
        <div class="history-content">
          <div class="history-main">
            <el-icon class="history-icon"><Search /></el-icon>
            <div class="history-text">
              <div class="history-query">{{ item.query }}</div>
              <el-text type="info" size="small">来源: {{ item.keyword }}</el-text>
            </div>
          </div>
          <div class="history-time">
            <el-text type="info" size="small">
              {{ formatDate(item.createdAt) }}
            </el-text>
          </div>
        </div>
      </el-card>
    </div>

    <el-empty
      v-else
      description="暂无搜索历史"
      :image-size="200"
    >
      <el-button type="primary" @click="$router.push('/')">
        去搜索
      </el-button>
    </el-empty>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Clock, Search } from '@element-plus/icons-vue'
import api from '../api'

const router = useRouter()
const history = ref([])
const loading = ref(false)

const loadHistory = async () => {
  loading.value = true
  try {
    const response = await api.getHistory(50)
    history.value = response.data || []
  } catch (error) {
    ElMessage.error('加载搜索历史失败')
  } finally {
    loading.value = false
  }
}

const clearAllHistory = async () => {
  try {
    await ElMessageBox.confirm(
      '确定要清空所有搜索历史吗？',
      '提示',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    )
    
    await api.clearHistory()
    history.value = []
    ElMessage.success('已清空搜索历史')
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('清空失败')
    }
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
  const date = new Date(dateStr)
  const now = new Date()
  const diff = now - date
  
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  
  if (minutes < 1) return '刚刚'
  if (minutes < 60) return `${minutes}分钟前`
  if (hours < 24) return `${hours}小时前`
  if (days < 7) return `${days}天前`
  
  return date.toLocaleDateString('zh-CN')
}

onMounted(() => {
  loadHistory()
})
</script>

<style scoped>
.history-page {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 30px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.95);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h2 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #333;
}

.history-loading {
  background: white;
  padding: 20px;
  border-radius: 12px;
}

.history-list {
  display: grid;
  gap: 12px;
}

.history-item {
  cursor: pointer;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
}

.history-item:hover {
  transform: translateX(8px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.history-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.history-main {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
}

.history-icon {
  font-size: 24px;
  color: #909399;
}

.history-text {
  flex: 1;
}

.history-query {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
}

.history-time {
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .history-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .history-time {
    width: 100%;
    text-align: left;
  }
}
</style>
