<script setup>
import { ref, reactive, onMounted } from 'vue'
import { recurringRulesApi } from '@/api/recurringRules'
import { categoriesApi } from '@/api/categories'
import { bankAccountsApi } from '@/api/bankAccounts'
import { formatBRL, parseCents } from '@/utils/currency'
import { formatDate } from '@/utils/date'

const rules = ref([])
const categories = ref([])
const accounts = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({
  description: '', amountStr: '', type: 'expense', frequency: 'monthly',
  start_date: '', end_date: '', category_id: '', bank_account_id: '',
})
const formError = ref('')

const frequencies = { daily: 'Diário', weekly: 'Semanal', monthly: 'Mensal', yearly: 'Anual' }

async function load() {
  loading.value = true
  const res = await recurringRulesApi.list()
  rules.value = res.data.data || []
  loading.value = false
}

onMounted(async () => {
  const [catRes, accRes] = await Promise.all([categoriesApi.flat(), bankAccountsApi.list()])
  categories.value = catRes.data.data || []
  accounts.value = accRes.data.data || []
  await load()
})

function openCreate() {
  editingId.value = null
  Object.assign(form, { description: '', amountStr: '', type: 'expense', frequency: 'monthly', start_date: '', end_date: '', category_id: '', bank_account_id: '' })
  showForm.value = true
}

function openEdit(r) {
  editingId.value = r.id
  Object.assign(form, {
    description: r.description, amountStr: (r.amount / 100).toFixed(2).replace('.', ','),
    type: r.type, frequency: r.frequency,
    start_date: r.start_date?.slice(0, 10) || '', end_date: r.end_date?.slice(0, 10) || '',
    category_id: r.category_id || '', bank_account_id: r.bank_account_id || '',
  })
  showForm.value = true
}

async function save() {
  formError.value = ''
  const payload = {
    description: form.description, amount: parseCents(form.amountStr),
    type: form.type, frequency: form.frequency,
    start_date: form.start_date, end_date: form.end_date || null,
    category_id: form.category_id || null, bank_account_id: form.bank_account_id || null,
  }
  try {
    if (editingId.value) await recurringRulesApi.update(editingId.value, payload)
    else await recurringRulesApi.create(payload)
    showForm.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erro ao salvar.'
  }
}

async function toggle(id) {
  await recurringRulesApi.toggle(id)
  await load()
}

async function remove(id) {
  if (!confirm('Remover esta regra?')) return
  await recurringRulesApi.remove(id)
  await load()
}
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Transações recorrentes</h1>
      <button @click="openCreate" class="btn-primary">+ Nova regra</button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <div v-else class="card overflow-hidden p-0">
      <div v-if="!rules.length" class="text-center py-12 text-gray-400 text-sm p-6">
        Nenhuma regra cadastrada.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Descrição</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Frequência</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Início</th>
            <th class="text-right px-4 py-3 font-medium text-gray-600">Valor</th>
            <th class="text-center px-4 py-3 font-medium text-gray-600">Ativo</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="r in rules" :key="r.id" class="hover:bg-gray-50" :class="!r.active && 'opacity-50'">
            <td class="px-4 py-3">
              <p class="font-medium text-gray-900">{{ r.description }}</p>
              <p class="text-xs text-gray-400">{{ r.category?.name }}</p>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ frequencies[r.frequency] }}</td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(r.start_date) }}</td>
            <td class="px-4 py-3 text-right font-semibold" :class="r.type === 'income' ? 'text-emerald-600' : 'text-red-600'">
              {{ formatBRL(r.amount) }}
            </td>
            <td class="px-4 py-3 text-center">
              <button @click="toggle(r.id)" class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                :class="r.active ? 'bg-primary-600' : 'bg-gray-300'">
                <span class="inline-block h-4 w-4 mt-0.5 rounded-full bg-white shadow transition-transform"
                  :class="r.active ? 'translate-x-4' : 'translate-x-0.5'" />
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2 justify-end">
                <button @click="openEdit(r)" class="btn-secondary btn-sm">Editar</button>
                <button @click="remove(r.id)" class="btn-danger btn-sm">Remover</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">{{ editingId ? 'Editar' : 'Nova' }} regra</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>
          <div><label class="label">Descrição</label><input v-model="form.description" class="input" /></div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Tipo</label>
              <select v-model="form.type" class="input">
                <option value="expense">Despesa</option>
                <option value="income">Receita</option>
              </select>
            </div>
            <div><label class="label">Valor (R$)</label><input v-model="form.amountStr" class="input" placeholder="0,00" /></div>
            <div>
              <label class="label">Frequência</label>
              <select v-model="form.frequency" class="input">
                <option v-for="(label, val) in frequencies" :key="val" :value="val">{{ label }}</option>
              </select>
            </div>
            <div><label class="label">Data início</label><input v-model="form.start_date" type="date" class="input" /></div>
            <div class="col-span-2"><label class="label">Data fim (opcional)</label><input v-model="form.end_date" type="date" class="input" /></div>
          </div>
          <div>
            <label class="label">Categoria</label>
            <select v-model="form.category_id" class="input">
              <option value="">— Nenhuma —</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Conta bancária</label>
            <select v-model="form.bank_account_id" class="input">
              <option value="">— Nenhuma —</option>
              <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
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
