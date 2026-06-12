<template>
  <div class="space-y-6 min-h-screen pb-16">
    <!-- Header -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-indigo-900 rounded-3xl p-8 text-white shadow-2xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
          </div>
          <div>
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight">镜像同步健康雷达</h1>
            <p class="text-slate-300 mt-1">实时监控 GitHub、Gitee、企业镜像及对象存储同步状态</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 bg-white/10 backdrop-blur px-4 py-2 rounded-xl">
            <svg class="w-5 h-5 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <input
              v-model="adminName"
              @change="saveAdminName"
              type="text"
              placeholder="管理员标识"
              class="bg-transparent border-none outline-none text-white placeholder-slate-400 w-32"
            />
          </div>
          <button
            @click="refreshAll"
            :disabled="refreshing"
            class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 rounded-xl font-medium transition-all flex items-center gap-2 shadow-lg disabled:opacity-50"
          >
            <svg v-if="!refreshing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ refreshing ? '刷新中' : '刷新数据' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Radar Summary Stats -->
    <div v-if="radarSummary.totals" class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div class="bg-white rounded-2xl p-5 shadow-lg border-l-4 border-slate-400">
        <div class="text-sm text-slate-500 font-medium">总镜像数</div>
        <div class="text-3xl font-bold text-slate-800 mt-1">{{ radarSummary.totals.mirrors }}</div>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-lg border-l-4 border-emerald-500">
        <div class="text-sm text-slate-500 font-medium">同步正常</div>
        <div class="text-3xl font-bold text-emerald-600 mt-1">{{ radarSummary.totals.healthy }}</div>
        <div class="text-xs text-emerald-500 mt-1">健康率 {{ radarSummary.totals.health_rate }}%</div>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-lg border-l-4 border-amber-500">
        <div class="text-sm text-slate-500 font-medium">同步落后</div>
        <div class="text-3xl font-bold text-amber-600 mt-1">{{ radarSummary.totals.outdated }}</div>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-lg border-l-4 border-rose-500">
        <div class="text-sm text-slate-500 font-medium">同步失败</div>
        <div class="text-3xl font-bold text-rose-600 mt-1">{{ radarSummary.totals.failed }}</div>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-lg border-l-4 border-slate-700 col-span-2 md:col-span-1">
        <div class="text-sm text-slate-500 font-medium">不可用下载</div>
        <div class="text-3xl font-bold text-slate-800 mt-1">{{ radarSummary.totals.unavailable }}</div>
      </div>
    </div>

    <!-- Health Rate Indicator -->
    <div v-if="radarSummary.totals" class="bg-white rounded-2xl p-6 shadow-lg">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-slate-800">整体健康度</h3>
        <span class="text-sm text-slate-500">{{ radarSummary.totals.healthy }}/{{ radarSummary.totals.mirrors }} 镜像正常</span>
      </div>
      <div class="w-full h-6 bg-slate-100 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-1000"
          :class="healthRateClass"
          :style="{ width: `${radarSummary.totals.health_rate}%` }"
        ></div>
      </div>
      <div class="flex justify-between mt-2 text-xs text-slate-500">
        <span>0%</span>
        <span>50%</span>
        <span>100%</span>
      </div>
    </div>

    <!-- Per Type Breakdown -->
    <div v-if="radarSummary.by_type && radarSummary.by_type.length > 0" class="bg-white rounded-2xl p-6 shadow-lg">
      <h3 class="text-lg font-bold text-slate-800 mb-5">各镜像源分布</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div
          v-for="item in radarSummary.by_type"
          :key="item.mirror_type"
          class="p-5 rounded-2xl border-2 transition-all hover:shadow-md"
          :class="getMirrorTypeCardClass(item.mirror_type)"
        >
          <div class="flex items-center gap-2 mb-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="getMirrorTypeIconClass(item.mirror_type)">
              <svg v-if="item.mirror_type === 'github'" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
              </svg>
              <svg v-else-if="item.mirror_type === 'gitee'" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
              <svg v-else-if="item.mirror_type === 'enterprise'" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
              </svg>
            </div>
            <span class="font-bold text-slate-800">{{ getMirrorTypeName(item.mirror_type) }}</span>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">总数</span>
              <span class="font-semibold text-slate-800">{{ item.total }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-emerald-600">正常</span>
              <span class="font-semibold text-emerald-600">{{ item.healthy }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-amber-600">落后</span>
              <span class="font-semibold text-amber-600">{{ item.outdated }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-rose-600">失败</span>
              <span class="font-semibold text-rose-600">{{ item.failed }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resources Search & Filter -->
    <div class="bg-white rounded-2xl p-6 shadow-lg">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
          <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="searchQuery"
            @input="handleResourceSearch"
            type="text"
            placeholder="搜索开源资源（Linux、Node.js、Python...）"
            class="w-full pl-12 pr-4 py-3 border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none transition-all"
          />
        </div>
        <select
          v-model="filterCategory"
          @change="loadResources"
          class="px-4 py-3 border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none transition-all bg-white"
        >
          <option value="">全部分类</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>
      </div>
    </div>

    <!-- Resources List with Mirror Radar -->
    <div class="space-y-5">
      <div v-if="loadingResources" class="grid grid-cols-1 gap-5">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-6 animate-pulse">
          <div class="h-7 bg-slate-200 rounded w-1/2 mb-4"></div>
          <div class="h-5 bg-slate-200 rounded w-3/4 mb-6"></div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div v-for="j in 4" :key="j" class="h-28 bg-slate-200 rounded-xl"></div>
          </div>
        </div>
      </div>

      <template v-else>
        <div
          v-for="resource in resources"
          :key="resource.id"
          class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300"
        >
          <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-5">
              <div class="flex-1">
                <div class="flex flex-wrap items-center gap-3 mb-2">
                  <h3 class="text-xl md:text-2xl font-bold text-slate-900">{{ resource.name }}</h3>
                  <span
                    v-if="resource.category"
                    class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold"
                  >
                    {{ resource.category }}
                  </span>
                  <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-semibold flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ resource.size || '未知' }}
                  </span>
                </div>
                <p v-if="resource.description" class="text-slate-600 mb-3">{{ resource.description }}</p>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                  <div class="flex items-center gap-2">
                    <span class="text-slate-500">原始校验值 ({{ resource.checksum_type }}):</span>
                    <code class="px-2 py-0.5 bg-slate-100 rounded font-mono text-xs text-slate-700 max-w-xs truncate" :title="resource.checksum">
                      {{ resource.checksum || '暂无' }}
                    </code>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <div class="px-4 py-2 bg-slate-50 rounded-xl border border-slate-200">
                  <div class="text-xs text-slate-500">镜像健康</div>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-lg font-bold text-emerald-600">{{ resource.healthy_mirror_count }}</span>
                    <span class="text-slate-400">/</span>
                    <span class="text-lg font-semibold text-slate-600">{{ resource.mirror_count }}</span>
                  </div>
                </div>
                <button
                  @click="checkAllMirrors(resource)"
                  :disabled="!adminName || checkingResourceId === resource.id"
                  class="px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 disabled:opacity-50 text-white rounded-xl font-medium transition-all flex items-center gap-2 shadow-md"
                  :title="adminName ? '批量复测所有镜像' : '请先设置管理员标识'"
                >
                  <svg v-if="checkingResourceId !== resource.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                  <span class="hidden sm:inline">复测全部</span>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
              <MirrorCard
                v-for="mirror in resource.mirrors || []"
                :key="mirror.id"
                :mirror="mirror"
                :resource="resource"
                :admin-name="adminName"
                :checking-mirror-id="checkingMirrorId"
                @check="handleCheckMirror"
                @toggle="handleToggleAvailable"
              />
              <div
                v-if="!resource.mirrors || resource.mirrors.length === 0"
                class="col-span-full p-8 bg-slate-50 rounded-xl border-2 border-dashed border-slate-300 text-center"
              >
                <svg class="w-12 h-12 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-slate-500">暂无镜像配置</p>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Admin Operation Logs -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
          </div>
          <h3 class="text-lg font-bold text-slate-800">管理员操作记录</h3>
        </div>
        <button
          @click="loadAdminLogs"
          class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
        >
          刷新日志
        </button>
      </div>
      <div class="max-h-80 overflow-y-auto">
        <div v-if="loadingLogs" class="p-8 text-center text-slate-500">
          <svg class="w-8 h-8 mx-auto animate-spin mb-3 text-slate-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          加载日志中...
        </div>
        <table v-else-if="adminLogs.length > 0" class="w-full">
          <thead class="bg-slate-50 sticky top-0">
            <tr>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase px-6 py-3">时间</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase px-6 py-3">管理员</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase px-6 py-3">操作类型</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase px-6 py-3">详情</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase px-6 py-3">IP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="log in adminLogs" :key="log.id" class="hover:bg-slate-50 transition-colors">
              <td class="px-6 py-3 text-sm text-slate-500 whitespace-nowrap">{{ formatTime(log.created_at) }}</td>
              <td class="px-6 py-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  {{ log.admin_name }}
                </span>
              </td>
              <td class="px-6 py-3">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold"
                  :class="getActionClass(log.action_type)"
                >
                  {{ getActionName(log.action_type) }}
                </span>
              </td>
              <td class="px-6 py-3 text-sm text-slate-700 max-w-md truncate" :title="log.details">{{ log.details }}</td>
              <td class="px-6 py-3 text-sm text-slate-500 font-mono">{{ log.ip_address }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="p-8 text-center text-slate-500">
          <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          暂无操作记录
        </div>
      </div>
    </div>

    <!-- Toast -->
    <transition name="toast">
      <div
        v-if="toast.show"
        class="fixed bottom-8 right-8 px-6 py-4 rounded-2xl shadow-2xl text-white font-medium z-50 flex items-center gap-3"
        :class="toastTypeClass"
      >
        <svg v-if="toast.type === 'success'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else-if="toast.type === 'error'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api'
import MirrorCard from '../components/MirrorCard.vue'

const adminName = ref(localStorage.getItem('admin_name') || '')
const radarSummary = ref({ by_type: [], totals: null })
const resources = ref([])
const categories = ref([])
const adminLogs = ref([])
const searchQuery = ref('')
const filterCategory = ref('')
const refreshing = ref(false)
const loadingResources = ref(false)
const loadingLogs = ref(false)
const checkingResourceId = ref(null)
const checkingMirrorId = ref(null)
const toast = ref({ show: false, message: '', type: 'success' })

let searchTimer = null

const toastTypeClass = computed(() => {
  const types = {
    success: 'bg-gradient-to-r from-emerald-500 to-green-600',
    error: 'bg-gradient-to-r from-rose-500 to-red-600',
    info: 'bg-gradient-to-r from-blue-500 to-indigo-600'
  }
  return types[toast.value.type] || types.info
})

const healthRateClass = computed(() => {
  const rate = radarSummary.value.totals?.health_rate || 0
  if (rate >= 80) return 'bg-gradient-to-r from-emerald-400 to-green-500'
  if (rate >= 50) return 'bg-gradient-to-r from-amber-400 to-orange-500'
  return 'bg-gradient-to-r from-rose-400 to-red-500'
})

const saveAdminName = () => {
  localStorage.setItem('admin_name', adminName.value)
  if (adminName.value) {
    showToast(`管理员标识已设置为: ${adminName.value}`, 'success')
  }
}

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3500)
}

const formatTime = (timeStr) => {
  if (!timeStr) return '未知'
  const date = new Date(timeStr.replace(' ', 'T'))
  if (isNaN(date.getTime())) return timeStr
  return date.toLocaleString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const getMirrorTypeName = (type) => {
  const names = { github: 'GitHub', gitee: 'Gitee', enterprise: '企业镜像', oss: '对象存储' }
  return names[type] || type
}

const getMirrorTypeCardClass = (type) => {
  const classes = {
    github: 'border-slate-200 bg-slate-50/50',
    gitee: 'border-rose-200 bg-rose-50/50',
    enterprise: 'border-indigo-200 bg-indigo-50/50',
    oss: 'border-cyan-200 bg-cyan-50/50'
  }
  return classes[type] || 'border-slate-200'
}

const getMirrorTypeIconClass = (type) => {
  const classes = {
    github: 'bg-slate-800 text-white',
    gitee: 'bg-rose-600 text-white',
    enterprise: 'bg-indigo-600 text-white',
    oss: 'bg-cyan-600 text-white'
  }
  return classes[type] || 'bg-slate-500 text-white'
}

const getActionName = (type) => {
  const names = { manual_check: '手动复测', toggle_available: '切换可用', update_mirror: '更新镜像' }
  return names[type] || type
}

const getActionClass = (type) => {
  const classes = {
    manual_check: 'bg-blue-100 text-blue-700',
    toggle_available: 'bg-amber-100 text-amber-700',
    update_mirror: 'bg-purple-100 text-purple-700'
  }
  return classes[type] || 'bg-slate-100 text-slate-700'
}

const loadRadarSummary = async () => {
  try {
    const res = await api.getRadarSummary()
    if (res.success) radarSummary.value = res.data
  } catch (e) {
    console.error('加载雷达概览失败:', e)
  }
}

const loadResources = async () => {
  loadingResources.value = true
  try {
    let res
    if (searchQuery.value.trim()) {
      res = await api.searchResources(searchQuery.value.trim())
    } else {
      res = await api.getResources(filterCategory.value || null)
    }
    if (res.success) {
      const list = res.data || []
      for (const r of list) {
        try {
          const detail = await api.getResource(r.id)
          if (detail.success) r.mirrors = detail.data.mirrors || []
        } catch {}
      }
      resources.value = list
    }
  } catch (e) {
    console.error('加载资源失败:', e)
    showToast('加载资源失败', 'error')
  } finally {
    loadingResources.value = false
  }
}

const loadCategories = async () => {
  try {
    const res = await api.getResourceCategories()
    if (res.success) categories.value = res.data || []
  } catch (e) {
    console.error('加载分类失败:', e)
  }
}

const loadAdminLogs = async () => {
  loadingLogs.value = true
  try {
    const res = await api.getAdminLogs(50)
    if (res.success) adminLogs.value = res.data || []
  } catch (e) {
    console.error('加载日志失败:', e)
  } finally {
    loadingLogs.value = false
  }
}

const handleResourceSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(loadResources, 400)
}

const refreshAll = async () => {
  refreshing.value = true
  await Promise.all([loadRadarSummary(), loadResources(), loadAdminLogs()])
  refreshing.value = false
  showToast('数据已刷新', 'success')
}

const checkAllMirrors = async (resource) => {
  if (!adminName.value) {
    showToast('请先设置管理员标识', 'error')
    return
  }
  checkingResourceId.value = resource.id
  try {
    const res = await api.checkResourceMirrors(resource.id, adminName.value)
    if (res.success) {
      const detail = await api.getResource(resource.id)
      if (detail.success) {
        const idx = resources.value.findIndex(r => r.id === resource.id)
        if (idx > -1) resources.value[idx].mirrors = detail.data.mirrors || []
      }
      await Promise.all([loadRadarSummary(), loadAdminLogs()])
      showToast(`${resource.name} 批量复测完成`, 'success')
    } else {
      showToast(res.message || '复测失败', 'error')
    }
  } catch (e) {
    showToast('复测失败: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    checkingResourceId.value = null
  }
}

const handleCheckMirror = async (mirrorId) => {
  if (!adminName.value) {
    showToast('请先设置管理员标识', 'error')
    return
  }
  checkingMirrorId.value = mirrorId
  try {
    const res = await api.checkMirror(mirrorId, adminName.value)
    if (res.success) {
      for (const r of resources.value) {
        const idx = r.mirrors?.findIndex(m => m.id === mirrorId)
        if (idx > -1) {
          const detail = await api.getResource(r.id)
          if (detail.success) r.mirrors = detail.data.mirrors || []
          break
        }
      }
      await Promise.all([loadRadarSummary(), loadAdminLogs()])
      showToast('镜像复测完成', 'success')
    } else {
      showToast(res.message || '复测失败', 'error')
    }
  } catch (e) {
    showToast('复测失败: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    checkingMirrorId.value = null
  }
}

const handleToggleAvailable = async (mirrorId) => {
  if (!adminName.value) {
    showToast('请先设置管理员标识', 'error')
    return
  }
  try {
    const res = await api.toggleMirrorAvailable(mirrorId, adminName.value)
    if (res.success) {
      for (const r of resources.value) {
        const idx = r.mirrors?.findIndex(m => m.id === mirrorId)
        if (idx > -1) {
          r.mirrors[idx].available = res.available ? 1 : 0
          break
        }
      }
      await Promise.all([loadRadarSummary(), loadAdminLogs()])
      showToast(res.message || '操作成功', 'success')
    } else {
      showToast(res.message || '操作失败', 'error')
    }
  } catch (e) {
    showToast('操作失败: ' + (e.response?.data?.message || e.message), 'error')
  }
}

onMounted(() => {
  loadRadarSummary()
  loadCategories()
  loadResources()
  loadAdminLogs()
})
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
</style>
