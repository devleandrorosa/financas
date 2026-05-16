<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { investmentsApi } from '@/api/investments'
import { formatBRL, parseCents } from '@/utils/currency'
import { formatDate } from '@/utils/date'

const investments = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', type: '', institution: '', amountStr: '', purchased_at: '', maturity_at: '', notes: '' })
const formError = ref('')

const total = computed(() => investments.value.reduce((s, i) => s + i.amount, 0))

async function load() {
  loading.value = true
  const res = await investmentsApi.list()
  investments.value = res.data.data || []
  loading.value = false
}

onMounted(load)

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', type: '', institution: '', amountStr: '', purchased_at: '', maturity_at: '', notes: '' })
  showForm.value = true
}

function openEdit(inv) {
  editingId.value = inv.id
  Object.assign(form, {
    name: inv.name, type: inv.type, institution: inv.institution,
    amountStr: (inv.amount / 100).toFixed(2).replace('.', ','),
    purchased_at: inv.purchased_at?.slice(0, 10) || '',
    maturity_at: inv.maturity_at?.slice(0, 10) || '',
    notes: inv.notes || '',
  })
  showForm.value = true
}

async function save() {
  formError.value = ''
  const payload = {
    name: form.name, type: form.type, institution: form.institution,
    amount: parseCents(form.amountStr),
    purchased_at: form.purchased_at, maturity_at: form.maturity_at || null,
    notes: form.notes || null,
  }
  try {
    if (editingId.value) await investmentsApi.update(editingId.value, payload)
    else await investmentsApi.create(payload)
    showForm.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erro ao salvar.'
  }
}

async function remove(id) {
  if (!confirm('Remover este investimento?')) return
  await investmentsApi.remove(id)
  await load()
}
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Investimentos</h1>
        <p class="text-gray-500 text-sm">Total: <span class="font-semibold text-gray-900">{{ formatBRL(total) }}</span></p>
      </div>
      <button @click="openCreate" class="btn-primary">+ Novo investimento</button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <div v-else class="card overflow-hidden p-0">
      <div v-if="!investments.length" class="text-center py-12 text-gray-400 text-sm p-6">
        Nenhum investimento cadastrado.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Nome</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Tipo</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Instituição</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Compra</th>
            <th class="text-right px-4 py-3 font-medium text-gray-600">Valor</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="inv in investments" :key="inv.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ inv.name }}</td>
            <td class="px-4 py-3 text-gray-500">{{ inv.type }}</td>
            <td class="px-4 py-3 text-gray-500">{{ inv.institution }}</td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(inv.purchased_at) }}</td>
            <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ formatBRL(inv.amount) }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2 justify-end">
                <button @click="openEdit(inv)" class="btn-secondary btn-sm">Editar</button>
                <button @click="remove(inv.id)" class="btn-danger btn-sm">Remover</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">{{ editingId ? 'Editar' : 'Novo' }} investimento</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>
          <div><label class="label">Nome</label><input v-model="form.name" class="input" /></div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Tipo</label><input v-model="form.type" class="input" placeholder="Tesouro Direto" /></div>
            <div><label class="label">Instituição</label><input v-model="form.institution" class="input" placeholder="XP" /></div>
            <div><label class="label">Valor (R$)</label><input v-model="form.amountStr" class="input" placeholder="0,00" /></div>
            <div><label class="label">Data de compra</label><input v-model="form.purchased_at" type="date" class="input" /></div>
            <div class="col-span-2"><label class="label">Vencimento (opcional)</label><input v-model="form.maturity_at" type="date" class="input" /></div>
          </div>
          <div><label class="label">Observações</label><textarea v-model="form.notes" class="input" rows="2" /></div>
        </div>
        <div class="flex gap-3 justify-end p-6 border-t">
          <button @click="showForm = false" class="btn-secondary">Cancelar</button>
          <button @click="save" class="btn-primary">Salvar</button>
        </div>
      </div>
    </div>
  </div>
</template>
