<template>
  <div
    class="rounded-2xl p-5 border-2 transition-all hover:shadow-lg"
    :class="mirrorCardClass"
  >
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center gap-2.5">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="mirrorIconClass">
          <svg v-if="mirror.mirror_type === 'github'" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
          </svg>
          <svg v-else-if="mirror.mirror_type === 'gitee'" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12.486 1.994c-.293-.047-2.428-.062-11.24 1.048C.466 3.09.057 3.469 0 3.912c-.353 2.746-.142 8.453 2.806 12.447C5.91 20.069 10.555 22.101 12.14 22.27c1.765.188 2.19-.013 2.957-.967 1.344-1.68 1.74-2.333 2.53-3.884 1.533-2.987 2.453-4.99 2.93-6.044 1.23-2.718 1.262-3.41.82-3.959l-.444-.458C20.576 6.818 17.978 5.83 12.486 1.994zM17.46 8.856c.501 1.155-1.123 2.71-2.69 2.435-1.674-.292-2.088-2.09-.657-2.79 1.493-.731 2.832-.73 3.347.355zm-4.86-4.558c1.915.158 3.323 1.81 3.084 3.64-.225 1.724-1.882 3.099-3.742 3.065-1.921-.038-3.472-1.737-3.256-3.709.212-1.867 1.966-3.122 3.914-2.996zM7.32 8.864c.706.155 1.404.806 1.404 1.81 0 1.117-.932 2.017-2.102 2.132-1.305.13-2.57-.692-2.633-1.87-.053-.988.832-2.125 2.202-2.244a3.922 3.922 0 011.13.172z"/>
          </svg>
          <svg v-else-if="mirror.mirror_type === 'enterprise'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
          </svg>
        </div>
        <div>
          <div class="font-bold text-slate-800">{{ mirror.mirror_name || getTypeName(mirror.mirror_type) }}</div>
          <div class="text-xs text-slate-500">{{ getTypeName(mirror.mirror_type) }}</div>
        </div>
      </div>
      <div class="flex items-center gap-1.5">
        <span
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold shadow-sm"
          :class="statusBadgeClass"
        >
          <span class="w-2 h-2 rounded-full" :class="statusDotClass"></span>
          {{ statusText }}
        </span>
      </div>
    </div>

    <div v-if="!isAvailable" class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-2">
      <svg class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <div>
        <div class="text-sm font-semibold text-rose-700">此镜像不可用</div>
        <div class="text-xs text-rose-600 mt-0.5">{{ unavailableReason }}</div>
      </div>
    </div>

    <div class="space-y-2.5 text-sm">
      <div class="flex items-center justify-between py-1 border-b border-slate-100/70">
        <span class="text-slate-500 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          最近同步
        </span>
        <span class="font-medium text-slate-700" :title="mirror.last_sync_time">
          {{ formatRelativeTime(mirror.last_sync_time) }}
        </span>
      </div>

      <div class="flex items-center justify-between py-1 border-b border-slate-100/70">
        <span class="text-slate-500 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          镜像大小
        </span>
        <span class="font-medium" :class="sizeDiffClass">
          {{ mirror.mirror_size || '未知' }}
        </span>
      </div>

      <div v-if="mirror.size_diff_percent != null && mirror.size_diff_percent != 0" class="flex items-center justify-between py-1 border-b border-slate-100/70">
        <span class="text-slate-500 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
          </svg>
          大小差异
        </span>
        <span class="font-semibold" :class="sizeDiffPercentClass">
          {{ mirror.size_diff_percent > 0 ? '+' : '' }}{{ mirror.size_diff_percent }}%
        </span>
      </div>

      <div class="flex items-start justify-between py-1 gap-2">
        <span class="text-slate-500 flex items-center gap-1.5 flex-shrink-0 pt-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
          校验值
        </span>
        <div class="text-right">
          <div
            class="font-mono text-xs px-2 py-0.5 rounded inline-block max-w-full truncate"
            :class="checksumClass"
            :title="mirror.mirror_checksum || '暂无'"
          >
            <span v-if="mirror.mirror_checksum">{{ (mirror.mirror_checksum || '').slice(0, 12) }}...</span>
            <span v-else class="text-slate-400">暂无</span>
          </div>
          <div v-if="mirror.checksum_match != null" class="text-xs mt-0.5" :class="mirror.checksum_match ? 'text-emerald-600 font-medium' : 'text-rose-600 font-medium'">
            {{ mirror.checksum_match ? '✓ 校验匹配' : '✗ 校验不匹配' }}
          </div>
        </div>
      </div>

      <div v-if="mirror.sync_lag_seconds != null && mirror.sync_lag_seconds > 0" class="flex items-center justify-between py-1 border-t border-slate-100/70 pt-2">
        <span class="text-slate-500 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
          </svg>
          同步延迟
        </span>
        <span class="font-semibold" :class="lagClass">
          {{ formatLag(mirror.sync_lag_seconds) }}
        </span>
      </div>

      <div v-if="mirror.error_message" class="mt-2 p-2 bg-rose-50 rounded-lg text-xs text-rose-600">
        {{ mirror.error_message }}
      </div>
    </div>

    <div v-if="mirror.last_check_time" class="mt-4 pt-3 border-t border-slate-200/60">
      <div class="flex items-center justify-between text-xs text-slate-400">
        <span>最近检测: {{ formatTime(mirror.last_check_time) }}</span>
        <span>操作人: {{ mirror.last_check_by || 'system' }}</span>
      </div>
    </div>

    <div class="mt-4 flex items-center gap-2">
      <button
        v-if="isAvailable"
        class="flex-1 py-2.5 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-all flex items-center justify-center gap-1.5 shadow-sm"
        :disabled="!adminName"
        :title="adminName ? '前往下载' : '管理员可管理'"
        @click="handleDownload"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        下载
      </button>
      <button
        v-else
        class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-xl text-sm font-semibold transition-all cursor-not-allowed flex items-center justify-center gap-1.5"
        disabled
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
        </svg>
        已禁用
      </button>
      <button
        @click="$emit('check', mirror.id)"
        :disabled="!isAuthenticated || checkingMirrorId === mirror.id"
        class="px-3 py-2.5 bg-indigo-50 hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed text-indigo-600 rounded-xl text-sm font-semibold transition-all flex items-center justify-center"
        :title="isAuthenticated ? '手动触发复测' : '请先登录管理员账户'"
      >
        <svg v-if="checkingMirrorId !== mirror.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
      </button>
      <button
        v-if="isAuthenticated"
        @click="$emit('toggle', mirror.id)"
        class="px-3 py-2.5 rounded-xl text-sm font-semibold transition-all flex items-center justify-center"
        :class="isAvailable ? 'bg-amber-50 hover:bg-amber-100 text-amber-600' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600'"
        :title="isAvailable ? '标记为不可用' : '标记为可用'"
      >
        <svg v-if="isAvailable" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  mirror: { type: Object, required: true },
  resource: { type: Object, default: null },
  isAuthenticated: { type: Boolean, default: false },
  checkingMirrorId: { type: [Number, String, null], default: null }
})

const emit = defineEmits(['check', 'toggle'])

const isAvailable = computed(() => props.mirror.available == 1 || props.mirror.available === true)

const getTypeName = (type) => {
  const names = { github: 'GitHub', gitee: 'Gitee', enterprise: '企业镜像', oss: '对象存储备份' }
  return names[type] || type
}

const mirrorCardClass = computed(() => {
  if (!isAvailable.value) return 'border-rose-200 bg-gradient-to-br from-rose-50/60 to-white'
  switch (props.mirror.sync_status) {
    case 'synced': return 'border-emerald-200 bg-gradient-to-br from-emerald-50/60 to-white'
    case 'outdated': return 'border-amber-200 bg-gradient-to-br from-amber-50/60 to-white'
    case 'failed': return 'border-rose-200 bg-gradient-to-br from-rose-50/60 to-white'
    case 'syncing': return 'border-blue-200 bg-gradient-to-br from-blue-50/60 to-white'
    default: return 'border-slate-200 bg-white'
  }
})

const mirrorIconClass = computed(() => {
  const classes = {
    github: 'bg-slate-800 text-white',
    gitee: 'bg-rose-600 text-white',
    enterprise: 'bg-indigo-600 text-white',
    oss: 'bg-cyan-600 text-white'
  }
  return classes[props.mirror.mirror_type] || 'bg-slate-500 text-white'
})

const statusText = computed(() => {
  if (!isAvailable.value && props.mirror.sync_status === 'synced') return '已禁用'
  const texts = { synced: '同步正常', outdated: '同步落后', failed: '同步失败', syncing: '同步中', unknown: '未知' }
  return texts[props.mirror.sync_status] || '未知'
})

const statusBadgeClass = computed(() => {
  if (!isAvailable.value) return 'bg-slate-100 text-slate-600'
  switch (props.mirror.sync_status) {
    case 'synced': return 'bg-emerald-100 text-emerald-700'
    case 'outdated': return 'bg-amber-100 text-amber-700'
    case 'failed': return 'bg-rose-100 text-rose-700'
    case 'syncing': return 'bg-blue-100 text-blue-700'
    default: return 'bg-slate-100 text-slate-600'
  }
})

const statusDotClass = computed(() => {
  if (!isAvailable.value) return 'bg-slate-400'
  switch (props.mirror.sync_status) {
    case 'synced': return 'bg-emerald-500 animate-pulse'
    case 'outdated': return 'bg-amber-500 animate-pulse'
    case 'failed': return 'bg-rose-500 animate-pulse'
    case 'syncing': return 'bg-blue-500 animate-ping'
    default: return 'bg-slate-400'
  }
})

const unavailableReason = computed(() => {
  if (props.mirror.sync_status === 'failed') return props.mirror.error_message || '镜像同步失败，已自动禁用'
  if (props.mirror.sync_status === 'outdated' && props.mirror.sync_lag_seconds > 86400) return '同步已超过24小时，为保护用户已自动禁用'
  return '管理员已手动禁用此镜像'
})

const sizeDiffClass = computed(() => {
  if (!props.mirror.mirror_size) return 'text-slate-400'
  if (props.mirror.size_diff_percent == null || props.mirror.size_diff_percent === 0) return 'text-slate-700'
  if (Math.abs(props.mirror.size_diff_percent) < 1) return 'text-slate-700'
  return props.mirror.size_diff_percent > 0 ? 'text-amber-600' : 'text-blue-600'
})

const sizeDiffPercentClass = computed(() => {
  if (!props.mirror.size_diff_percent) return 'text-slate-600'
  if (Math.abs(props.mirror.size_diff_percent) < 1) return 'text-slate-600'
  if (Math.abs(props.mirror.size_diff_percent) > 5) return 'text-rose-600'
  return 'text-amber-600'
})

const checksumClass = computed(() => {
  if (!props.mirror.mirror_checksum) return 'bg-slate-100 text-slate-400'
  if (props.mirror.checksum_match === 1 || props.mirror.checksum_match === true) return 'bg-emerald-50 text-emerald-700'
  if (props.mirror.checksum_match === 0 || props.mirror.checksum_match === false) return 'bg-rose-50 text-rose-700'
  return 'bg-slate-100 text-slate-600'
})

const lagClass = computed(() => {
  const sec = props.mirror.sync_lag_seconds || 0
  if (sec < 3600) return 'text-emerald-600'
  if (sec < 86400) return 'text-amber-600'
  return 'text-rose-600'
})

const formatTime = (timeStr) => {
  if (!timeStr) return '未知'
  const d = new Date(timeStr.replace(' ', 'T'))
  if (isNaN(d.getTime())) return timeStr
  return d.toLocaleString('zh-CN', { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}

const formatRelativeTime = (timeStr) => {
  if (!timeStr) return '从未同步'
  const d = new Date(timeStr.replace(' ', 'T'))
  if (isNaN(d.getTime())) return timeStr
  const diff = Date.now() - d.getTime()
  const sec = Math.floor(diff / 1000)
  if (sec < 60) return `${sec}秒前`
  if (sec < 3600) return `${Math.floor(sec / 60)}分钟前`
  if (sec < 86400) return `${Math.floor(sec / 3600)}小时前`
  return `${Math.floor(sec / 86400)}天前`
}

const formatLag = (seconds) => {
  if (!seconds) return '无延迟'
  if (seconds < 60) return `${seconds}秒`
  if (seconds < 3600) return `${Math.floor(seconds / 60)}分钟`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}小时`
  return `${Math.floor(seconds / 86400)}天`
}

const handleDownload = () => {
  if (props.mirror.mirror_url) {
    window.open(props.mirror.mirror_url, '_blank')
  } else if (props.resource?.original_url) {
    window.open(props.resource.original_url, '_blank')
  }
}
</script>
