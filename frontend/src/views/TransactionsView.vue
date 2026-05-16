<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { transactionsApi } from '@/api/transactions'
import { categoriesApi } from '@/api/categories'
import { bankAccountsApi } from '@/api/bankAccounts'
import { creditCardsApi } from '@/api/creditCards'
import { formatBRL, parseCents } from '@/utils/currency'
import { formatDate, currentYearMonth } from '@/utils/date'

const { year: curYear, month: curMonth } = currentYearMonth()

const transactions = ref([])
const categories = ref([])
const accounts = ref([])
const cards = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingId = ref(null)

const filters = reactive({ year: curYear, month: curMonth, type: '' })
// 0 = sem filtro

const form = reactive({
  description: '', amountStr: '', type: 'expense', date: new Date().toISOString().slice(0, 10),
  status: 'confirmed', notes: '', category_id: '', bank_account_id: '', credit_card_id: '', installments: 1,
})

async function load() {
  loading.value = true
  const params = {}
  if (filters.year)  params.year  = filters.year
  if (filters.month) params.month = filters.month
  if (filters.type)  params.type  = filters.type
  const res = await transactionsApi.list(params)
  transactions.value = res.data.data.data || []
  loading.value = false
}

onMounted(async () => {
  const [catRes, accRes, ccRes] = await Promise.all([
    categoriesApi.flat(), bankAccountsApi.list(), creditCardsApi.list()
  ])
  categories.value = catRes.data.data || []
  accounts.value = accRes.data.data || []
  cards.value = ccRes.data.data || []
  await load()
})

function openCreate() {
  editingId.value = null
  Object.assign(form, {
    description: '', amountStr: '', type: 'expense',
    date: new Date().toISOString().slice(0, 10),
    status: 'confirmed', notes: '', category_id: '', bank_account_id: '', credit_card_id: '', installments: 1,
  })
  showForm.value = true
}

function openEdit(t) {
  editingId.value = t.id
  Object.assign(form, {
    description: t.description,
    amountStr: (t.amount / 100).toFixed(2).replace('.', ','),
    type: t.type, date: t.date?.slice(0, 10),
    status: t.status, notes: t.notes || '',
    category_id: t.category_id || '', bank_account_id: t.bank_account_id || '',
    credit_card_id: t.credit_card_id || '', installments: 1,
  })
  showForm.value = true
}

const formError = ref('')

async function save() {
  formError.value = ''
  const amount = parseCents(form.amountStr)
  if (!amount) return (formError.value = 'Informe um valor válido.')

  const payload = {
    description: form.description, amount, type: form.type, date: form.date,
    status: form.status, notes: form.notes || null,
    category_id: form.category_id || null,
    bank_account_id: form.bank_account_id || null,
    credit_card_id: form.credit_card_id || null,
    installments: form.credit_card_id ? form.installments : undefined,
  }

  try {
    if (editingId.value) await transactionsApi.update(editingId.value, payload)
    else await transactionsApi.create(payload)
    showForm.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat()[0] || 'Erro ao salvar.'
  }
}

async function remove(id) {
  if (!confirm('Remover esta transação?')) return
  await transactionsApi.remove(id)
  await load()
}

function selectBankAccount() { if (form.bank_account_id) form.credit_card_id = '' }
function selectCreditCard() { if (form.credit_card_id) form.bank_account_id = '' }

const months = Array.from({ length: 12 }, (_, i) => ({
  value: i + 1,
  label: new Date(2000, i, 1).toLocaleDateString('pt-BR', { month: 'long' }),
}))
const years = [curYear - 2, curYear - 1, curYear, curYear + 1]

const totalIncome = computed(() =>
  transactions.value.filter(t => t.type === 'income' && t.status !== 'cancelled').reduce((s, t) => s + t.amount, 0)
)
const totalExpense = computed(() =>
  transactions.value.filter(t => t.type === 'expense' && t.status !== 'cancelled').reduce((s, t) => s + t.amount, 0)
)
</script>

<template>
  <div class="p-6 max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Transações</h1>
      <button @click="openCreate" class="btn-primary">+ Nova transação</button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
      <div class="flex flex-wrap gap-3 items-end">
        <div>
          <label class="label">Ano</label>
          <select v-model="filters.year" class="input w-28" @change="load">
            <option :value="0">Todos</option>
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div>
          <label class="label">Mês</label>
          <select v-model="filters.month" class="input w-36" @change="load">
            <option :value="0">Todos</option>
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>
        <div>
          <label class="label">Tipo</label>
          <select v-model="filters.type" class="input w-36" @change="load">
            <option value="">Todos</option>
            <option value="income">Receita</option>
            <option value="expense">Despesa</option>
          </select>
        </div>
        <div class="flex gap-4 ml-auto text-sm">
          <span class="text-emerald-600 font-medium">Receitas: {{ formatBRL(totalIncome) }}</span>
          <span class="text-red-600 font-medium">Despesas: {{ formatBRL(totalExpense) }}</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <div v-else class="card overflow-hidden p-0">
      <div v-if="!transactions.length" class="text-center py-12 text-gray-400 text-sm">
        Nenhuma transação encontrada.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Descrição</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Categoria</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Data</th>
            <th class="text-right px-4 py-3 font-medium text-gray-600">Valor</th>
            <th class="text-center px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="t in transactions" :key="t.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <p class="font-medium text-gray-900">{{ t.description }}</p>
              <p v-if="t.credit_card" class="text-xs text-gray-400">{{ t.credit_card.name }}</p>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ t.category?.name || '—' }}</td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(t.date) }}</td>
            <td class="px-4 py-3 text-right font-semibold"
              :class="t.type === 'income' ? 'text-emerald-600' : 'text-red-600'">
              {{ t.type === 'income' ? '+' : '-' }}{{ formatBRL(t.amount) }}
            </td>
            <td class="px-4 py-3 text-center">
              <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                :class="{
                  'bg-emerald-50 text-emerald-700': t.status === 'confirmed',
                  'bg-yellow-50 text-yellow-700': t.status === 'pending',
                  'bg-gray-100 text-gray-500': t.status === 'cancelled',
                }">
                {{ { confirmed: 'Confirmado', pending: 'Pendente', cancelled: 'Cancelado' }[t.status] }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2 justify-end">
                <button @click="openEdit(t)" class="btn-secondary btn-sm">Editar</button>
                <button @click="remove(t.id)" class="btn-danger btn-sm">Remover</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">{{ editingId ? 'Editar' : 'Nova' }} transação</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>

          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="label">Descrição</label>
              <input v-model="form.description" class="input" required />
            </div>
            <div>
              <label class="label">Tipo</label>
              <select v-model="form.type" class="input">
                <option value="expense">Despesa</option>
                <option value="income">Receita</option>
              </select>
            </div>
            <div>
              <label class="label">Valor (R$)</label>
              <input v-model="form.amountStr" class="input" placeholder="0,00" />
            </div>
            <div>
              <label class="label">Data</label>
              <input v-model="form.date" type="date" class="input" />
            </div>
            <div>
              <label class="label">Status</label>
              <select v-model="form.status" class="input">
                <option value="confirmed">Confirmado</option>
                <option value="pending">Pendente</option>
                <option value="cancelled">Cancelado</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="label">Categoria</label>
              <select v-model="form.category_id" class="input">
                <option value="">— Nenhuma —</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">
                  {{ c.parent_id ? '  └ ' : '' }}{{ c.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="label">Conta bancária</label>
              <select v-model="form.bank_account_id" class="input" @change="selectBankAccount">
                <option value="">— Nenhuma —</option>
                <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Cartão de crédito</label>
              <select v-model="form.credit_card_id" class="input" @change="selectCreditCard">
                <option value="">— Nenhum —</option>
                <option v-for="c in cards" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div v-if="form.credit_card_id && !editingId" class="col-span-2">
              <label class="label">Parcelas</label>
              <select v-model="form.installments" class="input">
                <option v-for="n in 12" :key="n" :value="n">{{ n }}x</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="label">Observações</label>
              <textarea v-model="form.notes" class="input" rows="2" />
            </div>
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
