<script setup>
import { ref, reactive, onMounted } from 'vue'
import { familyApi } from '@/api/family'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const family = ref(null)
const loading = ref(true)
const showInvite = ref(false)
const form = reactive({ email: '', role: 'member' })
const formError = ref('')
const inviteSuccess = ref('')

async function load() {
  loading.value = true
  const res = await familyApi.get()
  family.value = res.data.data
  loading.value = false
}

onMounted(load)

async function invite() {
  formError.value = ''
  inviteSuccess.value = ''
  try {
    await familyApi.invite(form)
    inviteSuccess.value = `Convite enviado para ${form.email}.`
    form.email = ''
    showInvite.value = false
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erro ao enviar convite.'
  }
}

async function updateRole(memberId, role) {
  await familyApi.updateRole(memberId, role)
  await load()
}

async function removeMember(id) {
  if (!confirm('Remover este membro da família?')) return
  await familyApi.removeMember(id)
  await load()
}

const roleLabels = { admin: 'Admin', member: 'Membro' }
</script>

<template>
  <div class="p-6 max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Família</h1>
      <button @click="showInvite = true" class="btn-primary">+ Convidar membro</button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Carregando...</div>

    <template v-else-if="family">
      <div class="card mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ family.name }}</h2>
        <p class="text-sm text-gray-500">{{ family.members?.length || 0 }} membro(s)</p>
      </div>

      <div v-if="inviteSuccess" class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm">{{ inviteSuccess }}</div>

      <div class="card overflow-hidden p-0">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="font-medium text-gray-900">Membros</h3>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="member in family.members" :key="member.id" class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-semibold text-sm">
                {{ member.name.charAt(0) }}
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ member.name }}</p>
                <p class="text-sm text-gray-500">{{ member.email }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <select
                :value="member.roles?.[0]?.name || 'member'"
                @change="updateRole(member.id, $event.target.value)"
                class="input w-28 text-sm py-1"
                :disabled="member.id === auth.user?.id"
              >
                <option value="admin">Admin</option>
                <option value="member">Membro</option>
              </select>
              <button
                v-if="member.id !== auth.user?.id"
                @click="removeMember(member.id)"
                class="btn-danger btn-sm"
              >
                Remover
              </button>
              <span v-else class="text-xs text-gray-400">Você</span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Invite modal -->
    <div v-if="showInvite" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">Convidar membro</h3>
          <button @click="showInvite = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ formError }}</div>
          <div>
            <label class="label">E-mail</label>
            <input v-model="form.email" type="email" class="input" placeholder="joao@email.com" />
          </div>
          <div>
            <label class="label">Papel</label>
            <select v-model="form.role" class="input">
              <option value="member">Membro</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="flex gap-3 justify-end p-6 border-t">
          <button @click="showInvite = false" class="btn-secondary">Cancelar</button>
          <button @click="invite" class="btn-primary">Enviar convite</button>
        </div>
      </div>
    </div>
  </div>
</template>
