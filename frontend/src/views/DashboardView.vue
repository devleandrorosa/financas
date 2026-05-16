<script setup>
import { ref, computed, watch } from 'vue'
import { transactionsApi } from '@/api/transactions'
import { bankAccountsApi } from '@/api/bankAccounts'
import { formatBRL } from '@/utils/currency'
import { formatDate, currentYearMonth, monthLabel } from '@/utils/date'

const { year: curYear, month: curMonth } = currentYearMonth()

// 0 = sem filtro (todos)
const selYear  = ref(curYear)
const selMonth = ref(curMonth)

const transactions = ref([])
const accounts     = ref([])
const barMonths    = ref([])
const loading      = ref(true)

const monthsList = Array.from({ length: 12 }, (_, i) => ({
  value: i + 1,
  label: new Date(2000, i, 1).toLocaleDateString('pt-BR', { month: 'long' }),
}))
const yearsList = [curYear - 2, curYear - 1, curYear, curYear + 1]

const periodLabel = computed(() => {
  if (!selYear.value && !selMonth.value) return 'Todos os períodos'
  if (!selMonth.value) return String(selYear.value)
  if (!selYear.value) {
    const name = new Date(2000, selMonth.value - 1).toLocaleDateString('pt-BR', { month: 'long' })
    return name.charAt(0).toUpperCase() + name.slice(1)
  }
  return monthLabel(selYear.value, selMonth.value)
})

// Gera array de meses para o gráfico de barras (últimos 6 ou 12 meses do ano selecionado)
function buildBarMonths() {
  const result = []
  if (selMonth.value === 0 && selYear.value) {
    // ano inteiro: 12 meses
    for (let m = 1; m <= 12; m++) {
      result.push({ year: selYear.value, month: m })
    }
  } else {
    // últimos 6 meses a partir do período selecionado
    const baseYear  = selYear.value  || curYear
    const baseMonth = selMonth.value || curMonth
    for (let i = 5; i >= 0; i--) {
      const d = new Date(baseYear, baseMonth - 1 - i, 1)
      result.push({ year: d.getFullYear(), month: d.getMonth() + 1 })
    }
  }
  return result
}

async function load() {
  loading.value = true

  const months = buildBarMonths()

  const params = {}
  if (selYear.value)  params.year     = selYear.value
  if (selMonth.value) params.month    = selMonth.value
  params.per_page = 500

  const [txRes, accRes, ...histRes] = await Promise.all([
    transactionsApi.list(params),
    bankAccountsApi.list(),
    ...months.map(m => transactionsApi.list({ year: m.year, month: m.month, per_page: 500 })),
  ])

  transactions.value = txRes.data.data.data || []
  accounts.value     = accRes.data.data     || []

  barMonths.value = months.map((m, i) => {
    const txs     = histRes[i]?.data?.data?.data || []
    const income  = txs.filter(t => t.type === 'income'  && t.status !== 'cancelled').reduce((s, t) => s + t.amount, 0)
    const expense = txs.filter(t => t.type === 'expense' && t.status !== 'cancelled').reduce((s, t) => s + t.amount, 0)
    return {
      label: new Date(m.year, m.month - 1, 1).toLocaleDateString('pt-BR', { month: 'short' }),
      income,
      expense,
    }
  })

  loading.value = false
}

watch([selYear, selMonth], load, { immediate: true })

const income  = computed(() => transactions.value.filter(t => t.type === 'income'  && t.status !== 'cancelled').reduce((s, t) => s + t.amount, 0))
const expense = computed(() => transactions.value.filter(t => t.type === 'expense' && t.status !== 'cancelled').reduce((s, t) => s + t.amount, 0))
const balance = computed(() => income.value - expense.value)
const recent  = computed(() => transactions.value.slice(0, 8))

// Bar chart
const CHART_H  = 180
const BAR_GAP  = 6
const GROUP_GAP = 16

const barChart = computed(() => {
  if (!barMonths.value.length) return { bars: [], width: 0, yTicks: [] }
  const maxVal = Math.max(...barMonths.value.flatMap(d => [d.income, d.expense]), 1)
  const barW   = barMonths.value.length > 6 ? 20 : 28
  const groupW = barW * 2 + BAR_GAP + GROUP_GAP
  const totalW = groupW * barMonths.value.length + GROUP_GAP

  const bars = barMonths.value.map((d, i) => {
    const x = GROUP_GAP + i * groupW
    return {
      ...d,
      incomeX:  x,
      expenseX: x + barW + BAR_GAP,
      labelX:   x + barW + BAR_GAP / 2,
      incomeH:  Math.round((d.income  / maxVal) * CHART_H) || 0,
      expenseH: Math.round((d.expense / maxVal) * CHART_H) || 0,
      barW,
    }
  })

  const yTicks = [0, 0.25, 0.5, 0.75, 1].map(f => ({
    value: Math.round(maxVal * f),
    y: CHART_H - Math.round(CHART_H * f),
  }))

  return { bars, width: totalW, yTicks }
})

// Category breakdown
const categoryChart = computed(() => {
  const expenses = transactions.value.filter(t => t.type === 'expense' && t.status !== 'cancelled')
  const map = {}
  for (const t of expenses) {
    const name = t.category?.name || 'Sem categoria'
    map[name] = (map[name] || 0) + t.amount
  }
  const total = Object.values(map).reduce((s, v) => s + v, 0) || 1
  return Object.entries(map)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 6)
    .map(([name, amount]) => ({ name, amount, pct: Math.round((amount / total) * 100) }))
})

const COLORS = ['bg-blue-500', 'bg-violet-500', 'bg-amber-500', 'bg-rose-500', 'bg-teal-500', 'bg-gray-400']
</script>

<template>
  <div class="p-6 max-w-6xl mx-auto">

    <!-- Header + period selector -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500 text-sm capitalize">{{ periodLabel }}</p>
      </div>
      <div class="flex items-center gap-2">
        <select v-model.number="selYear" class="input w-28 text-sm">
          <option :value="0">Todos os anos</option>
          <option v-for="y in yearsList" :key="y" :value="y">{{ y }}</option>
        </select>
        <select v-model.number="selMonth" class="input w-36 text-sm">
          <option :value="0">Todos os meses</option>
          <option v-for="m in monthsList" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <template v-else>
      <!-- Summary cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card">
          <p class="text-sm text-gray-500 mb-1">Receitas</p>
          <p class="text-2xl font-bold text-emerald-600">{{ formatBRL(income) }}</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500 mb-1">Despesas</p>
          <p class="text-2xl font-bold text-red-600">{{ formatBRL(expense) }}</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500 mb-1">Saldo do período</p>
          <p class="text-2xl font-bold" :class="balance >= 0 ? 'text-emerald-600' : 'text-red-600'">
            {{ formatBRL(balance) }}
          </p>
        </div>
      </div>

      <!-- Charts row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Bar chart -->
        <div class="card">
          <h2 class="text-base font-semibold text-gray-900 mb-3">
            Receitas vs Despesas —
            {{ selMonth === 0 && selYear ? 'ano ' + selYear : 'últimos ' + barChart.bars.length + ' meses' }}
          </h2>
          <div class="flex gap-4 mb-3 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm bg-emerald-500"></span> Receitas</span>
            <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm bg-red-400"></span> Despesas</span>
          </div>
          <div v-if="!barChart.bars.length" class="text-center py-8 text-gray-400 text-sm">Sem dados</div>
          <div v-else class="overflow-x-auto">
            <svg
              :viewBox="`0 0 ${barChart.width + 60} ${CHART_H + 40}`"
              :width="barChart.width + 60"
              :height="CHART_H + 40"
              class="w-full"
            >
              <g v-for="tick in barChart.yTicks" :key="tick.y">
                <line :x1="50" :y1="tick.y" :x2="barChart.width + 50" :y2="tick.y" stroke="#f0f0f0" stroke-width="1"/>
                <text :x="46" :y="tick.y + 4" text-anchor="end" font-size="8" fill="#9ca3af">
                  {{ tick.value > 0 ? formatBRL(tick.value).replace('R$ ', '') : '' }}
                </text>
              </g>
              <g v-for="b in barChart.bars" :key="b.label" :transform="`translate(50,0)`">
                <rect :x="b.incomeX"  :y="CHART_H - b.incomeH"  :width="b.barW" :height="b.incomeH"  rx="2" fill="#10b981" opacity="0.85"/>
                <rect :x="b.expenseX" :y="CHART_H - b.expenseH" :width="b.barW" :height="b.expenseH" rx="2" fill="#f87171" opacity="0.85"/>
                <text :x="b.labelX" :y="CHART_H + 14" text-anchor="middle" font-size="9" fill="#6b7280">{{ b.label }}</text>
              </g>
              <line :x1="50" :y1="CHART_H" :x2="barChart.width + 50" :y2="CHART_H" stroke="#e5e7eb" stroke-width="1"/>
            </svg>
          </div>
        </div>

        <!-- Category breakdown -->
        <div class="card">
          <h2 class="text-base font-semibold text-gray-900 mb-4">Despesas por categoria — {{ periodLabel }}</h2>
          <div v-if="!categoryChart.length" class="text-center py-8 text-gray-400 text-sm">Sem despesas neste período.</div>
          <div v-else class="space-y-3">
            <div v-for="(item, i) in categoryChart" :key="item.name">
              <div class="flex items-center justify-between text-sm mb-1">
                <div class="flex items-center gap-2">
                  <span class="inline-block w-2.5 h-2.5 rounded-sm" :class="COLORS[i]"></span>
                  <span class="text-gray-700 truncate max-w-[140px]">{{ item.name }}</span>
                </div>
                <div class="flex items-center gap-2 text-right">
                  <span class="text-gray-500 text-xs">{{ item.pct }}%</span>
                  <span class="font-medium text-gray-900">{{ formatBRL(item.amount) }}</span>
                </div>
              </div>
              <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" :class="COLORS[i]" :style="{ width: item.pct + '%' }"/>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bank accounts -->
      <div v-if="accounts.length" class="card mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Contas bancárias</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="acc in accounts" :key="acc.id" class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500">{{ acc.name }}</p>
            <p class="font-semibold text-gray-900">{{ formatBRL(acc.balance) }}</p>
          </div>
        </div>
      </div>

      <!-- Recent transactions -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-gray-900">Transações recentes</h2>
          <RouterLink to="/transactions" class="text-sm text-primary-600 hover:underline">Ver todas</RouterLink>
        </div>
        <div v-if="!recent.length" class="text-center py-8 text-gray-400 text-sm">Nenhuma transação neste período.</div>
        <div v-else class="divide-y divide-gray-100">
          <div v-for="t in recent" :key="t.id" class="flex items-center justify-between py-3">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full flex items-center justify-center"
                :class="t.type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    :d="t.type === 'income' ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3'"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ t.description }}</p>
                <p class="text-xs text-gray-400">{{ t.category?.name }} · {{ formatDate(t.date) }}</p>
              </div>
            </div>
            <span class="text-sm font-semibold" :class="t.type === 'income' ? 'text-emerald-600' : 'text-red-600'">
              {{ t.type === 'income' ? '+' : '-' }}{{ formatBRL(t.amount) }}
            </span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
