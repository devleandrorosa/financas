<script setup>
import { ref, reactive, onMounted } from 'vue'
import { categoriesApi } from '@/api/categories'

const categories = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', type: 'expense', parent_id: '', color: '' })
const formError = ref('')

async function load() {
  loading.value = true
  const res = await categoriesApi.list()
  categories.value = res.data.data || []
  loading.value = false
}

onMounted(load)

const flatList = ref([])
onMounted(async () => {
  const res = await categoriesApi.flat()
  flatList.value = (res.data.data || []).filter(c => !c.parent_id)
})

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', type: 'expense', parent_id: '', color: '' })
  showForm.value = true
}

function openEdit(c) {
  editingId.value = c.id
  Object.assign(form, { name: c.name, type: c.type, parent_id: c.parent_id || '', color: c.color || '' })
  showForm.value = true
}

async function save() {
  formError.value = ''
  const payload = { name: form.name, type: form.type, parent_id: form.parent_id || null, color: form.color || null }
  try {
    if (editingId.value) await categoriesApi.update(editingId.value, payload)
    else await categoriesApi.create(payload)
    showForm.value = false
    await load()
    const res = await categoriesApi.flat()
    flatList.value = (res.data.data || []).filter(c => !c.parent_id)
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erro ao salvar.'
  }
}

async function remove(id) {
  if (!confirm('Remover esta categoria?')) return
  await categoriesApi.remove(id)
  await load()
}
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Categorias</h1>
      <button @click="openCreate" class="btn-primary">+ Nova categoria</button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <div v-else class="space-y-3">
      <div v-for="cat in categories" :key="cat.id" class="card p-0 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <span v-if="cat.color" class="w-3 h-3 rounded-full" :style="{ backgroundColor: cat.color }"/>
            <span class="font-medium text-gray-900">{{ cat.name }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full" :class="cat.type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
              {{ cat.type === 'income' ? 'Receita' : 'Despesa' }}
            </span>
          </div>
          <div class="flex gap-2">
            <button @click="openEdit(cat)" class="btn-secondary btn-sm">Editar</button>
            <button @click="remove(cat.id)" class="btn-danger btn-sm">Remover</button>
          </div>
        </div>
        <div v-if="cat.children?.length" class="divide-y divide-gray-50">
          <div v-for="child in cat.children" :key="child.id" class="flex items-center justify-between px-4 py-2.5 pl-8">
            <div class="flex items-center gap-2">
              <span v-if="child.color" class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: child.color }"/>
              <span class="text-sm text-gray-700">{{ child.name }}</span>
            </div>
            <div class="flex gap-2">
              <button @click="openEdit(child)" class="btn-secondary btn-sm">Editar</button>
              <button @click="remove(child.id)" class="btn-danger btn-sm">Remover</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">{{ editingId ? 'Editar' : 'Nova' }} categoria</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>
          <div>
            <label class="label">Nome</label>
            <input v-model="form.name" class="input" />
          </div>
          <div>
            <label class="label">Tipo</label>
            <select v-model="form.type" class="input">
              <option value="expense">Despesa</option>
              <option value="income">Receita</option>
            </select>
          </div>
          <div>
            <label class="label">Categoria pai (opcional)</label>
            <select v-model="form.parent_id" class="input">
              <option value="">— Nenhuma —</option>
              <option v-for="c in flatList" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Cor (opcional)</label>
            <input v-model="form.color" type="color" class="h-10 w-full rounded-lg border border-gray-300 cursor-pointer" />
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
