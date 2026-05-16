<script setup>
import { ref, onUnmounted } from 'vue'
import { aiImportApi } from '@/api/aiImport'
import { categoriesApi } from '@/api/categories'
import { bankAccountsApi } from '@/api/bankAccounts'
import { parseCents } from '@/utils/currency'

// exposed to template
const POLL_INTERVAL_MS = 3000
const TIMEOUT_SECONDS = 300

// stage: 'upload' | 'processing' | 'review' | 'done'
const stage = ref('upload')
const sessionId = ref(null)
const error = ref('')
const uploadError = ref('')
const fileInput = ref(null)
const dragging = ref(false)

// processing state
const elapsed = ref(0)
const pollError = ref('')
const consecutiveErrors = ref(0)

const categories = ref([])
const accounts = ref([])
const rows = ref([])
const submitting = ref(false)
const result = ref(null)

let pollTimer = null
let elapsedTimer = null

async function loadOptions() {
  const [catRes, accRes] = await Promise.all([categoriesApi.flat(), bankAccountsApi.list()])
  categories.value = catRes.data.data || []
  accounts.value = accRes.data.data || []
}
loadOptions()

function onDragOver(e) { e.preventDefault(); dragging.value = true }
function onDragLeave() { dragging.value = false }
function onDrop(e) {
  e.preventDefault()
  dragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) startUpload(file)
}
function onFileChange(e) {
  const file = e.target.files[0]
  if (file) startUpload(file)
}

async function startUpload(file) {
  uploadError.value = ''
  error.value = ''
  if (!file.name.match(/\.(pdf|csv|txt|xlsx|xls)$/i)) {
    uploadError.value = 'Formato não suportado. Use PDF, CSV, TXT ou XLSX.'
    return
  }
  try {
    const res = await aiImportApi.upload(file)
    sessionId.value = res.data.data.session_id
    stage.value = 'processing'
    elapsed.value = 0
    pollError.value = ''
    consecutiveErrors.value = 0
    startPolling()
  } catch (e) {
    uploadError.value = e.response?.data?.errors?.file?.[0]
      || e.response?.data?.message
      || 'Erro ao enviar arquivo.'
  }
}

function startPolling() {
  elapsedTimer = setInterval(() => { elapsed.value++ }, 1000)

  pollTimer = setInterval(async () => {
    if (elapsed.value >= TIMEOUT_SECONDS) {
      stopPolling()
      error.value = `O processamento excedeu ${TIMEOUT_SECONDS}s. Tente novamente ou verifique os logs do servidor.`
      stage.value = 'upload'
      return
    }

    try {
      const res = await aiImportApi.status(sessionId.value)
      const session = res.data.data
      consecutiveErrors.value = 0
      pollError.value = ''

      if (session.status === 'completed') {
        stopPolling()
        initReview(session.items || [])
        stage.value = 'review'
      } else if (session.status === 'failed') {
        stopPolling()
        error.value = friendlyError(session.error_message)
        stage.value = 'upload'
      }
      // status === 'processing': keep polling
    } catch (e) {
      consecutiveErrors.value++
      pollError.value = e.response?.data?.message || e.message || 'Erro ao verificar status.'
      if (consecutiveErrors.value >= 5) {
        stopPolling()
        error.value = `Não foi possível verificar o status após 5 tentativas: ${pollError.value}`
        stage.value = 'upload'
      }
    }
  }, POLL_INTERVAL_MS)
}

function stopPolling() {
  clearInterval(pollTimer)
  clearInterval(elapsedTimer)
}

function friendlyError(msg) {
  if (!msg) return 'Erro desconhecido no processamento.'
  if (msg.includes('429') || msg.includes('overloaded')) return 'Quota da API de IA esgotada após múltiplas tentativas. Tente novamente mais tarde.'
  if (msg.includes('invalid x-api-key') || msg.includes('invalid_api_key') || msg.includes('API_KEY_INVALID')) return 'Chave da API de IA inválida. Verifique o AI_PROVIDER e a chave correspondente no .env.'
  if (msg.includes('models/') && msg.includes('not found')) return 'Modelo de IA não disponível para esta chave. Verifique o AI_PROVIDER no .env.'
  if (msg.includes('Arquivo não encontrado')) return 'Arquivo não encontrado no servidor. Tente enviar novamente.'
  if (msg.includes('inválida') || msg.includes('inválido')) return 'O documento não contém transações reconhecíveis.'
  return msg.length > 200 ? msg.slice(0, 200) + '...' : msg
}

function cancelProcessing() {
  stopPolling()
  stage.value = 'upload'
  sessionId.value = null
}

function initReview(items) {
  rows.value = items.map(item => ({
    id: item.id,
    status: 'accepted',
    description: item.description,
    amountStr: (item.amount / 100).toFixed(2).replace('.', ','),
    type: item.type,
    date: item.date?.slice(0, 10) || '',
    category_id: item.category_id || '',
    bank_account_id: '',
  }))
}

function toggleAll(status) {
  rows.value.forEach(r => r.status = status)
}

async function submit() {
  submitting.value = true
  error.value = ''
  try {
    const items = rows.value.map(r => ({
      id: r.id,
      status: r.status,
      description: r.description,
      amount: parseCents(r.amountStr),
      type: r.type,
      date: r.date,
      category_id: r.category_id || null,
      bank_account_id: r.bank_account_id || null,
      credit_card_id: null,
    }))
    const res = await aiImportApi.confirm(sessionId.value, items)
    result.value = res.data.data
    stage.value = 'done'
  } catch (e) {
    error.value = e.response?.data?.message || 'Erro ao confirmar importação.'
  } finally {
    submitting.value = false
  }
}

function reset() {
  stopPolling()
  stage.value = 'upload'
  sessionId.value = null
  error.value = ''
  uploadError.value = ''
  rows.value = []
  result.value = null
  elapsed.value = 0
  pollError.value = ''
  consecutiveErrors.value = 0
  if (fileInput.value) fileInput.value.value = ''
}

onUnmounted(stopPolling)
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Importar via IA</h1>
      <p class="text-gray-500 text-sm">Envie uma fatura em PDF ou planilha CSV/XLSX. O Claude extrai os lançamentos para você revisar antes de salvar.</p>
    </div>

    <!-- Error banner -->
    <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm flex justify-between">
      <span>{{ error }}</span>
      <button @click="error = ''" class="ml-4 font-semibold">✕</button>
    </div>

    <!-- STAGE: upload -->
    <div v-if="stage === 'upload'" class="card">
      <div
        class="border-2 border-dashed rounded-xl p-12 text-center transition-colors cursor-pointer"
        :class="dragging ? 'border-primary-400 bg-primary-50' : 'border-gray-300 hover:border-gray-400'"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
        @click="fileInput.click()"
      >
        <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        <p class="text-gray-600 font-medium mb-1">Arraste o arquivo aqui ou clique para selecionar</p>
        <p class="text-gray-400 text-sm">PDF, CSV, TXT ou XLSX — máximo 10 MB</p>
      </div>
      <input ref="fileInput" type="file" accept=".pdf,.csv,.txt,.xlsx,.xls" class="hidden" @change="onFileChange" />
      <p v-if="uploadError" class="mt-3 text-sm text-red-600">{{ uploadError }}</p>
    </div>

    <!-- STAGE: processing -->
    <div v-else-if="stage === 'processing'" class="card text-center py-16">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 mb-6 animate-pulse">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
        </svg>
      </div>
      <h2 class="text-lg font-semibold text-gray-900 mb-2">Gemini está analisando o documento...</h2>

      <!-- Progress bar -->
      <div class="w-64 mx-auto mb-3">
        <div class="flex justify-between text-xs text-gray-400 mb-1">
          <span>{{ elapsed }}s</span>
          <span>limite {{ TIMEOUT_SECONDS }}s</span>
        </div>
        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
          <div class="h-full bg-primary-500 rounded-full transition-all duration-1000"
            :style="{ width: Math.min((elapsed / TIMEOUT_SECONDS) * 100, 100) + '%' }" />
        </div>
      </div>

      <p class="text-gray-400 text-sm mb-1">Verificando status a cada {{ POLL_INTERVAL_MS / 1000 }}s... Em caso de limite de quota, o sistema retentará automaticamente.</p>
      <p v-if="pollError" class="text-xs text-yellow-600 mb-3">
        Falha temporária na verificação ({{ consecutiveErrors }}/5): {{ pollError }}
      </p>

      <button @click="cancelProcessing" class="btn-secondary btn-sm mt-4">Cancelar</button>
    </div>

    <!-- STAGE: review -->
    <div v-else-if="stage === 'review'">
      <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-600">
          <span class="font-semibold text-gray-900">{{ rows.filter(r => r.status === 'accepted').length }}</span> de
          <span class="font-semibold">{{ rows.length }}</span> itens selecionados para importar
        </p>
        <div class="flex gap-2">
          <button @click="toggleAll('accepted')" class="btn-secondary btn-sm">Aceitar todos</button>
          <button @click="toggleAll('rejected')" class="btn-secondary btn-sm">Rejeitar todos</button>
        </div>
      </div>

      <div class="card overflow-hidden p-0 mb-4">
        <div v-if="!rows.length" class="text-center py-12 text-gray-400 text-sm">
          Nenhuma transação detectada no documento.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-3 py-3 w-10"></th>
                <th class="text-left px-3 py-3 font-medium text-gray-600">Descrição</th>
                <th class="text-left px-3 py-3 font-medium text-gray-600 w-28">Valor (R$)</th>
                <th class="text-left px-3 py-3 font-medium text-gray-600 w-24">Tipo</th>
                <th class="text-left px-3 py-3 font-medium text-gray-600 w-32">Data</th>
                <th class="text-left px-3 py-3 font-medium text-gray-600 w-40">Categoria</th>
                <th class="text-left px-3 py-3 font-medium text-gray-600 w-36">Conta</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in rows" :key="row.id"
                class="transition-colors"
                :class="row.status === 'rejected' ? 'bg-gray-50 opacity-50' : 'hover:bg-blue-50/30'"
              >
                <td class="px-3 py-2 text-center">
                  <input type="checkbox" :checked="row.status === 'accepted'"
                    @change="row.status = $event.target.checked ? 'accepted' : 'rejected'"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                </td>
                <td class="px-3 py-2">
                  <input v-model="row.description" class="input py-1 text-sm w-full" :disabled="row.status === 'rejected'" />
                </td>
                <td class="px-3 py-2">
                  <input v-model="row.amountStr" class="input py-1 text-sm w-full" :disabled="row.status === 'rejected'" />
                </td>
                <td class="px-3 py-2">
                  <select v-model="row.type" class="input py-1 text-sm" :disabled="row.status === 'rejected'">
                    <option value="expense">Despesa</option>
                    <option value="income">Receita</option>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <input v-model="row.date" type="date" class="input py-1 text-sm" :disabled="row.status === 'rejected'" />
                </td>
                <td class="px-3 py-2">
                  <select v-model="row.category_id" class="input py-1 text-sm" :disabled="row.status === 'rejected'">
                    <option value="">—</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <select v-model="row.bank_account_id" class="input py-1 text-sm" :disabled="row.status === 'rejected'">
                    <option value="">—</option>
                    <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex gap-3 justify-end">
        <button @click="reset" class="btn-secondary">Cancelar</button>
        <button @click="submit" :disabled="submitting || !rows.some(r => r.status === 'accepted')" class="btn-primary">
          {{ submitting ? 'Salvando...' : `Importar ${rows.filter(r => r.status === 'accepted').length} transação(ões)` }}
        </button>
      </div>
    </div>

    <!-- STAGE: done -->
    <div v-else-if="stage === 'done'" class="card text-center py-16">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-6">
        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <h2 class="text-lg font-semibold text-gray-900 mb-2">Importação concluída!</h2>
      <p class="text-gray-500 text-sm mb-6">
        <span class="font-semibold text-emerald-600">{{ result?.accepted }}</span> transação(ões) importada(s) ·
        <span class="font-semibold text-gray-400">{{ result?.rejected }}</span> rejeitada(s)
      </p>
      <div class="flex gap-3 justify-center">
        <button @click="reset" class="btn-secondary">Nova importação</button>
        <router-link to="/transactions" class="btn-primary">Ver transações</router-link>
      </div>
    </div>
  </div>
</template>
