<script setup>
import { ref, computed, onMounted } from 'vue'
import { projectionApi } from '@/api/projection'
import { formatBRL } from '@/utils/currency'

const months = ref(6)
const data = ref([])
const loading = ref(true)

async function load() {
  loading.value = true
  const res = await projectionApi.get(months.value)
  data.value = res.data.data || []
  loading.value = false
}

onMounted(load)

function changeMonths(m) {
  months.value = m
  load()
}

// Chart dimensions
const CHART_H = 220
const BAR_GAP = 8
const GROUP_GAP = 20

const chartData = computed(() => {
  if (!data.value.length) return { bars: [], width: 0, maxVal: 0, yTicks: [] }

  const maxVal = Math.max(...data.value.flatMap(d => [d.income, d.expense]), 1)

  const barW = Math.max(20, Math.min(40, Math.floor((800 - GROUP_GAP * data.value.length) / (data.value.length * 2 + 1))))
  const groupW = barW * 2 + BAR_GAP + GROUP_GAP
  const totalW = groupW * data.value.length + GROUP_GAP

  const bars = data.value.map((d, i) => {
    const x = GROUP_GAP + i * groupW
    const incomeH = Math.round((d.income / maxVal) * CHART_H)
    const expenseH = Math.round((d.expense / maxVal) * CHART_H)
    return {
      ...d,
      x,
      barW,
      incomeH,
      expenseH,
      incomeX: x,
      expenseX: x + barW + BAR_GAP,
      labelX: x + barW + BAR_GAP / 2,
    }
  })

  // 4 y-axis ticks
  const yTicks = [0, 0.25, 0.5, 0.75, 1].map(f => ({
    value: Math.round(maxVal * f),
    y: CHART_H - Math.round(CHART_H * f),
  }))

  return { bars, width: totalW, maxVal, yTicks }
})

const totals = computed(() => {
  const income  = data.value.reduce((s, d) => s + d.income, 0)
  const expense = data.value.reduce((s, d) => s + d.expense, 0)
  return { income, expense, balance: income - expense }
})
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Projeção futura</h1>
        <p class="text-gray-500 text-sm">Baseada nas regras de recorrência ativas</p>
      </div>
      <div class="flex gap-2">
        <button v-for="m in [3, 6, 12]" :key="m"
          @click="changeMonths(m)"
          class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="months === m ? 'bg-primary-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-gray-300'">
          {{ m }} meses
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-20 text-gray-400">Carregando...</div>

    <template v-else-if="data.length">
      <!-- Summary cards -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="card">
          <p class="text-xs text-gray-500 mb-1">Total projetado de receitas</p>
          <p class="text-xl font-bold text-emerald-600">{{ formatBRL(totals.income) }}</p>
        </div>
        <div class="card">
          <p class="text-xs text-gray-500 mb-1">Total projetado de despesas</p>
          <p class="text-xl font-bold text-red-600">{{ formatBRL(totals.expense) }}</p>
        </div>
        <div class="card">
          <p class="text-xs text-gray-500 mb-1">Saldo acumulado projetado</p>
          <p class="text-xl font-bold" :class="totals.balance >= 0 ? 'text-gray-900' : 'text-red-600'">
            {{ formatBRL(totals.balance) }}
          </p>
        </div>
      </div>

      <!-- Bar chart -->
      <div class="card mb-6 overflow-x-auto">
        <div class="flex gap-4 mb-4 text-xs text-gray-500">
          <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-sm bg-emerald-500"></span> Receitas
          </span>
          <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-sm bg-red-400"></span> Despesas
          </span>
        </div>
        <svg
          :viewBox="`0 0 ${chartData.width + 60} ${CHART_H + 50}`"
          :width="chartData.width + 60"
          :height="CHART_H + 50"
          class="min-w-full"
        >
          <!-- Y-axis ticks -->
          <g v-for="tick in chartData.yTicks" :key="tick.y">
            <line :x1="50" :y1="tick.y" :x2="chartData.width + 50" :y2="tick.y"
              stroke="#f0f0f0" stroke-width="1" />
            <text :x="46" :y="tick.y + 4" text-anchor="end" font-size="9" fill="#9ca3af">
              {{ tick.value >= 100000 ? (tick.value / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }) : '' }}
            </text>
          </g>

          <!-- Bars -->
          <g v-for="b in chartData.bars" :key="b.month" :transform="`translate(50, 0)`">
            <!-- Income bar -->
            <rect
              :x="b.incomeX" :y="CHART_H - b.incomeH"
              :width="b.barW" :height="b.incomeH"
              rx="3" fill="#10b981" opacity="0.85"
            />
            <!-- Expense bar -->
            <rect
              :x="b.expenseX" :y="CHART_H - b.expenseH"
              :width="b.barW" :height="b.expenseH"
              rx="3" fill="#f87171" opacity="0.85"
            />
            <!-- Month label -->
            <text :x="b.labelX" :y="CHART_H + 16" text-anchor="middle" font-size="10" fill="#6b7280">
              {{ b.label }}
            </text>
          </g>

          <!-- X axis line -->
          <line :x1="50" :y1="CHART_H" :x2="chartData.width + 50" :y2="CHART_H"
            stroke="#e5e7eb" stroke-width="1" />
        </svg>
      </div>

      <!-- Monthly table -->
      <div class="card overflow-hidden p-0">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Mês</th>
              <th class="text-right px-4 py-3 font-medium text-emerald-600">Receitas</th>
              <th class="text-right px-4 py-3 font-medium text-red-600">Despesas</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">Saldo mensal</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">Saldo acumulado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(d, i) in data" :key="i"
              class="hover:bg-gray-50"
              :class="i === 0 ? 'bg-blue-50/40' : ''">
              <td class="px-4 py-3 font-medium text-gray-900">
                {{ d.label }}
                <span v-if="i === 0" class="ml-1.5 text-xs text-primary-600 font-normal">mês atual</span>
              </td>
              <td class="px-4 py-3 text-right text-emerald-600 font-medium">{{ formatBRL(d.income) }}</td>
              <td class="px-4 py-3 text-right text-red-600 font-medium">{{ formatBRL(d.expense) }}</td>
              <td class="px-4 py-3 text-right font-semibold"
                :class="d.balance >= 0 ? 'text-gray-900' : 'text-red-600'">
                {{ d.balance >= 0 ? '+' : '' }}{{ formatBRL(d.balance) }}
              </td>
              <td class="px-4 py-3 text-right font-semibold"
                :class="d.cumulative >= 0 ? 'text-gray-700' : 'text-red-600'">
                {{ d.cumulative >= 0 ? '+' : '' }}{{ formatBRL(d.cumulative) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="mt-3 text-xs text-gray-400 text-center">
        Projeção calculada com base nas {{ months === 1 ? '1 regra' : months + ' regras' }} de recorrência ativas.
        Valores reais podem variar.
      </p>
    </template>

    <div v-else class="card text-center py-16 text-gray-400">
      <p class="font-medium mb-1">Nenhuma regra de recorrência ativa</p>
      <p class="text-sm">Cadastre receitas e despesas recorrentes para visualizar a projeção.</p>
    </div>
  </div>
</template>
