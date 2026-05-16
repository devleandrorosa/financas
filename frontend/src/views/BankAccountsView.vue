<script setup>
import { ref, reactive, onMounted } from 'vue'
import { bankAccountsApi } from '@/api/bankAccounts'
import { formatBRL, parseCents } from '@/utils/currency'

const accounts = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', bank: '', type: 'checking', balanceStr: '0,00' })
const formError = ref('')

const types = { checking: 'Corrente', savings: 'Poupança', investment: 'Investimento', wallet: 'Carteira' }

async function load() {
  loading.value = true
  const res = await bankAccountsApi.list()
  accounts.value = res.data.data || []
  loading.value = false
}

onMounted(load)

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', bank: '', type: 'checking', balanceStr: '0,00' })
  showForm.value = true
}

function openEdit(a) {
  editingId.value = a.id
  Object.assign(form, { name: a.name, bank: a.bank, type: a.type, balanceStr: (a.balance / 100).toFixed(2).replace('.', ',') })
  showForm.value = true
}

async function save() {
  formError.value = ''
  const payload = { name: form.name, bank: form.bank, type: form.type, balance: parseCents(form.balanceStr) }
  try {
    if (editingId.value) await bankAccountsApi.update(editingId.value, payload)
    else await bankAccountsApi.create(payload)
    showForm.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erro ao salvar.'
  }
}

async function remove(id) {
  if (!confirm('Remover esta conta?')) return
  await bankAccountsApi.remove(id)
  await load()
}
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Contas bancárias</h1>
      <button @click="openCreate" class="btn-primary">+ Nova conta</button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-if="!accounts.length" class="col-span-full text-center py-12 text-gray-400 text-sm card">
        Nenhuma conta cadastrada.
      </div>
      <div v-for="acc in accounts" :key="acc.id" class="card">
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="font-semibold text-gray-900">{{ acc.name }}</p>
            <p class="text-sm text-gray-500">{{ acc.bank }} · {{ types[acc.type] }}</p>
          </div>
          <div class="flex gap-1">
            <button @click="openEdit(acc)" class="btn-secondary btn-sm">Editar</button>
            <button @click="remove(acc.id)" class="btn-danger btn-sm">✕</button>
          </div>
        </div>
        <p class="text-2xl font-bold" :class="acc.balance >= 0 ? 'text-gray-900' : 'text-red-600'">
          {{ formatBRL(acc.balance) }}
        </p>
      </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">{{ editingId ? 'Editar' : 'Nova' }} conta</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>
          <div>
            <label class="label">Nome</label>
            <input v-model="form.name" class="input" placeholder="Nubank" />
          </div>
          <div>
            <label class="label">Banco</label>
            <input v-model="form.bank" class="input" placeholder="Nubank" />
          </div>
          <div>
            <label class="label">Tipo</label>
            <select v-model="form.type" class="input">
              <option v-for="(label, val) in types" :key="val" :value="val">{{ label }}</option>
            </select>
          </div>
          <div>
            <label class="label">Saldo inicial (R$)</label>
            <input v-model="form.balanceStr" class="input" placeholder="0,00" />
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
