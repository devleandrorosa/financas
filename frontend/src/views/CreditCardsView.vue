<script setup>
import { ref, reactive, onMounted } from 'vue'
import { creditCardsApi } from '@/api/creditCards'
import { formatBRL, parseCents } from '@/utils/currency'

const cards = ref([])
const statements = ref([])
const selectedCard = ref(null)
const loading = ref(true)
const showForm = ref(false)
const showStatements = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', bank: '', limitStr: '0,00', closing_day: 20, due_day: 27 })
const formError = ref('')

async function load() {
  loading.value = true
  const res = await creditCardsApi.list()
  cards.value = res.data.data || []
  loading.value = false
}

onMounted(load)

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', bank: '', limitStr: '0,00', closing_day: 20, due_day: 27 })
  showForm.value = true
}

function openEdit(c) {
  editingId.value = c.id
  Object.assign(form, {
    name: c.name, bank: c.bank,
    limitStr: (c.limit_amount / 100).toFixed(2).replace('.', ','),
    closing_day: c.closing_day, due_day: c.due_day,
  })
  showForm.value = true
}

async function save() {
  formError.value = ''
  const payload = {
    name: form.name, bank: form.bank,
    limit_amount: parseCents(form.limitStr),
    closing_day: Number(form.closing_day), due_day: Number(form.due_day),
  }
  try {
    if (editingId.value) await creditCardsApi.update(editingId.value, payload)
    else await creditCardsApi.create(payload)
    showForm.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erro ao salvar.'
  }
}

async function remove(id) {
  if (!confirm('Remover este cartão?')) return
  await creditCardsApi.remove(id)
  await load()
}

async function viewStatements(card) {
  selectedCard.value = card
  const res = await creditCardsApi.statements(card.id)
  statements.value = res.data.data || []
  showStatements.value = true
}

async function pay(statementId) {
  await creditCardsApi.payStatement(statementId)
  await viewStatements(selectedCard.value)
}

const monthNames = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Cartões de crédito</h1>
      <button @click="openCreate" class="btn-primary">+ Novo cartão</button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-if="!cards.length" class="col-span-full text-center py-12 text-gray-400 text-sm card">
        Nenhum cartão cadastrado.
      </div>
      <div v-for="card in cards" :key="card.id" class="card">
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="font-semibold text-gray-900">{{ card.name }}</p>
            <p class="text-sm text-gray-500">{{ card.bank }}</p>
          </div>
          <div class="flex gap-1">
            <button @click="openEdit(card)" class="btn-secondary btn-sm">Editar</button>
            <button @click="remove(card.id)" class="btn-danger btn-sm">✕</button>
          </div>
        </div>
        <div class="text-sm text-gray-500 space-y-1 mb-3">
          <p>Limite: <span class="font-medium text-gray-900">{{ formatBRL(card.limit_amount) }}</span></p>
          <p>Fechamento: dia {{ card.closing_day }} · Vencimento: dia {{ card.due_day }}</p>
        </div>
        <button @click="viewStatements(card)" class="btn-secondary btn-sm w-full">Ver faturas</button>
      </div>
    </div>

    <!-- Card form modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">{{ editingId ? 'Editar' : 'Novo' }} cartão</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>
          <div>
            <label class="label">Nome</label>
            <input v-model="form.name" class="input" placeholder="Nubank Roxinho" />
          </div>
          <div>
            <label class="label">Banco</label>
            <input v-model="form.bank" class="input" placeholder="Nubank" />
          </div>
          <div>
            <label class="label">Limite (R$)</label>
            <input v-model="form.limitStr" class="input" placeholder="0,00" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Dia de fechamento</label>
              <input v-model="form.closing_day" type="number" min="1" max="31" class="input" />
            </div>
            <div>
              <label class="label">Dia de vencimento</label>
              <input v-model="form.due_day" type="number" min="1" max="31" class="input" />
            </div>
          </div>
        </div>
        <div class="flex gap-3 justify-end p-6 border-t">
          <button @click="showForm = false" class="btn-secondary">Cancelar</button>
          <button @click="save" class="btn-primary">Salvar</button>
        </div>
      </div>
    </div>

    <!-- Statements modal -->
    <div v-if="showStatements" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">Faturas · {{ selectedCard?.name }}</h3>
          <button @click="showStatements = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6">
          <div v-if="!statements.length" class="text-center py-8 text-gray-400 text-sm">Nenhuma fatura.</div>
          <div v-else class="space-y-3">
            <div v-for="s in statements" :key="s.id"
              class="flex items-center justify-between p-4 rounded-lg border"
              :class="s.status === 'paid' ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200'">
              <div>
                <p class="font-medium text-gray-900">{{ monthNames[s.month - 1] }}/{{ s.year }}</p>
                <p class="text-lg font-bold mt-0.5" :class="s.status === 'paid' ? 'text-emerald-700' : 'text-gray-900'">
                  {{ formatBRL(s.total_amount) }}
                </p>
              </div>
              <div class="text-right">
                <span class="text-xs font-medium px-2 py-1 rounded-full"
                  :class="s.status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-yellow-100 text-yellow-700'">
                  {{ s.status === 'paid' ? 'Paga' : 'Em aberto' }}
                </span>
                <div v-if="s.status !== 'paid'" class="mt-2">
                  <button @click="pay(s.id)" class="btn-primary btn-sm">Marcar como paga</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
