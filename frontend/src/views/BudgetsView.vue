<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { budgetsApi } from '@/api/budgets'
import { categoriesApi } from '@/api/categories'
import { formatBRL, parseCents } from '@/utils/currency'
import { currentYearMonth, monthLabel } from '@/utils/date'

const { year: curYear, month: curMonth } = currentYearMonth()
const year = ref(curYear)
const month = ref(curMonth)
const budgets = ref([])
const categories = ref([])
const loading = ref(true)
const showForm = ref(false)
const form = reactive({ category_id: '', amountStr: '' })
const formError = ref('')

async function load() {
  loading.value = true
  const res = await budgetsApi.list(year.value, month.value)
  budgets.value = res.data.data || []
  loading.value = false
}

onMounted(async () => {
  const res = await categoriesApi.flat()
  categories.value = (res.data.data || []).filter(c => c.type === 'expense')
  await load()
})

async function save() {
  formError.value = ''
  const payload = {
    category_id: Number(form.category_id),
    year: year.value, month: month.value,
    amount: parseCents(form.amountStr),
  }
  try {
    await budgetsApi.save(payload)
    showForm.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erro ao salvar.'
  }
}

async function remove(id) {
  if (!confirm('Remover este orçamento?')) return
  await budgetsApi.remove(id)
  await load()
}

const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: new Date(2000, i).toLocaleDateString('pt-BR', { month: 'long' }) }))
const years = [curYear - 1, curYear, curYear + 1]

const totalBudget = computed(() => budgets.value.reduce((s, b) => s + b.amount, 0))
const totalSpent = computed(() => budgets.value.reduce((s, b) => s + (b.spent || 0), 0))
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Orçamentos</h1>
        <p class="text-gray-500 text-sm capitalize">{{ monthLabel(year, month) }}</p>
      </div>
      <button @click="showForm = true" class="btn-primary">+ Novo orçamento</button>
    </div>

    <!-- Month picker -->
    <div class="card mb-6 flex gap-4 flex-wrap">
      <div>
        <label class="label">Mês</label>
        <select v-model="month" class="input w-36" @change="load">
          <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
      </div>
      <div>
        <label class="label">Ano</label>
        <select v-model="year" class="input w-28" @change="load">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
      <div class="ml-auto flex gap-6 items-end text-sm">
        <div>
          <p class="text-gray-500">Total orçado</p>
          <p class="font-bold text-gray-900 text-lg">{{ formatBRL(totalBudget) }}</p>
        </div>
        <div>
          <p class="text-gray-500">Total gasto</p>
          <p class="font-bold text-lg" :class="totalSpent > totalBudget ? 'text-red-600' : 'text-gray-900'">{{ formatBRL(totalSpent) }}</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <div v-else class="space-y-3">
      <div v-if="!budgets.length" class="card text-center py-12 text-gray-400 text-sm">
        Nenhum orçamento para este mês.
      </div>
      <div v-for="b in budgets" :key="b.id" class="card">
        <div class="flex items-center justify-between mb-2">
          <div>
            <p class="font-medium text-gray-900">{{ b.category?.name }}</p>
            <p class="text-xs text-gray-500">{{ formatBRL(b.spent || 0) }} de {{ formatBRL(b.amount) }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm font-semibold" :class="(b.remaining || 0) < 0 ? 'text-red-600' : 'text-emerald-600'">
              {{ (b.remaining || 0) < 0 ? '-' : '' }}{{ formatBRL(Math.abs(b.remaining || 0)) }}
            </span>
            <button @click="remove(b.id)" class="btn-danger btn-sm">✕</button>
          </div>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div class="h-2 rounded-full transition-all"
            :class="(b.spent || 0) > b.amount ? 'bg-red-500' : 'bg-primary-600'"
            :style="{ width: Math.min(100, ((b.spent || 0) / b.amount) * 100) + '%' }" />
        </div>
      </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">Novo orçamento</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>
          <div>
            <label class="label">Categoria</label>
            <select v-model="form.category_id" class="input">
              <option value="">— Selecione —</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Valor orçado (R$)</label>
            <input v-model="form.amountStr" class="input" placeholder="0,00" />
          </div>
        </div>
        <div class="flex gap-3 justify-end p-6 border-t">
          <button @click="showForm = false" class="btn-secondary">Cancelar</button>
          <button @click="save" class="btn-primary">Salvar</button>
        </div>
      </div>
    </div>
  </div>
</template>
