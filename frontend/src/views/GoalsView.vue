<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { goalsApi } from '@/api/goals'
import { formatBRL, parseCents } from '@/utils/currency'
import { formatDate } from '@/utils/date'

const goals = ref([])
const loading = ref(true)
const showForm = ref(false)
const showProgress = ref(false)
const editingId = ref(null)
const progressGoal = ref(null)
const progressStr = ref('0,00')
const form = reactive({ name: '', type: 'savings', targetStr: '', currentStr: '0,00', deadline: '', notes: '' })
const formError = ref('')

const types = { savings: 'Poupança', debt: 'Dívida', purchase: 'Compra', emergency: 'Emergência' }

async function load() {
  loading.value = true
  const res = await goalsApi.list()
  goals.value = res.data.data || []
  loading.value = false
}

onMounted(load)

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', type: 'savings', targetStr: '', currentStr: '0,00', deadline: '', notes: '' })
  showForm.value = true
}

function openEdit(g) {
  editingId.value = g.id
  Object.assign(form, {
    name: g.name, type: g.type,
    targetStr: (g.target_amount / 100).toFixed(2).replace('.', ','),
    currentStr: (g.current_amount / 100).toFixed(2).replace('.', ','),
    deadline: g.deadline?.slice(0, 10) || '', notes: g.notes || '',
  })
  showForm.value = true
}

function openProgress(g) {
  progressGoal.value = g
  progressStr.value = '0,00'
  showProgress.value = true
}

async function save() {
  formError.value = ''
  const payload = {
    name: form.name, type: form.type,
    target_amount: parseCents(form.targetStr),
    current_amount: parseCents(form.currentStr),
    deadline: form.deadline || null, notes: form.notes || null,
  }
  try {
    if (editingId.value) await goalsApi.update(editingId.value, payload)
    else await goalsApi.create(payload)
    showForm.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erro ao salvar.'
  }
}

async function addProgress() {
  const amount = parseCents(progressStr.value)
  if (!amount) return
  await goalsApi.progress(progressGoal.value.id, amount)
  showProgress.value = false
  await load()
}

async function remove(id) {
  if (!confirm('Remover esta meta?')) return
  await goalsApi.remove(id)
  await load()
}

function pct(g) {
  if (!g.target_amount) return 0
  return Math.min(100, Math.round((g.current_amount / g.target_amount) * 100))
}
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Metas financeiras</h1>
      <button @click="openCreate" class="btn-primary">+ Nova meta</button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-if="!goals.length" class="col-span-full text-center py-12 text-gray-400 text-sm card">
        Nenhuma meta cadastrada.
      </div>
      <div v-for="g in goals" :key="g.id" class="card">
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="font-semibold text-gray-900">{{ g.name }}</p>
            <p class="text-xs text-gray-500">{{ types[g.type] }}{{ g.deadline ? ` · até ${formatDate(g.deadline)}` : '' }}</p>
          </div>
          <div class="flex gap-1">
            <button @click="openEdit(g)" class="btn-secondary btn-sm">Editar</button>
            <button @click="remove(g.id)" class="btn-danger btn-sm">✕</button>
          </div>
        </div>
        <div class="mb-3">
          <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-500">{{ formatBRL(g.current_amount) }}</span>
            <span class="font-medium text-gray-900">{{ formatBRL(g.target_amount) }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="h-3 rounded-full bg-primary-600 transition-all" :style="{ width: pct(g) + '%' }" />
          </div>
          <p class="text-right text-xs text-gray-500 mt-1">{{ pct(g) }}%</p>
        </div>
        <button @click="openProgress(g)" class="btn-secondary btn-sm w-full">+ Adicionar progresso</button>
      </div>
    </div>

    <!-- Goal form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">{{ editingId ? 'Editar' : 'Nova' }} meta</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>
          <div><label class="label">Nome</label><input v-model="form.name" class="input" /></div>
          <div>
            <label class="label">Tipo</label>
            <select v-model="form.type" class="input">
              <option v-for="(label, val) in types" :key="val" :value="val">{{ label }}</option>
            </select>
          </div>
          <div><label class="label">Valor alvo (R$)</label><input v-model="form.targetStr" class="input" placeholder="0,00" /></div>
          <div><label class="label">Valor atual (R$)</label><input v-model="form.currentStr" class="input" placeholder="0,00" /></div>
          <div><label class="label">Prazo (opcional)</label><input v-model="form.deadline" type="date" class="input" /></div>
          <div><label class="label">Observações</label><textarea v-model="form.notes" class="input" rows="2" /></div>
        </div>
        <div class="flex gap-3 justify-end p-6 border-t">
          <button @click="showForm = false" class="btn-secondary">Cancelar</button>
          <button @click="save" class="btn-primary">Salvar</button>
        </div>
      </div>
    </div>

    <!-- Progress modal -->
    <div v-if="showProgress" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">Adicionar progresso</h3>
          <button @click="showProgress = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-500 mb-4">Meta: {{ progressGoal?.name }}</p>
          <label class="label">Valor (R$)</label>
          <input v-model="progressStr" class="input" placeholder="0,00" />
        </div>
        <div class="flex gap-3 justify-end p-6 border-t">
          <button @click="showProgress = false" class="btn-secondary">Cancelar</button>
          <button @click="addProgress" class="btn-primary">Confirmar</button>
        </div>
      </div>
    </div>
  </div>
</template>
