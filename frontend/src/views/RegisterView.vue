<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = ref({ name: '', email: '', password: '', password_confirmation: '', family_name: '' })
const loading = ref(false)
const errors = ref({})

async function submit() {
  errors.value = {}
  loading.value = true
  try {
    const res = await authApi.register(form.value)
    auth.setAuth(res.data.data)
    router.push('/')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else errors.value = { general: e.response?.data?.message || 'Erro ao criar conta.' }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-8">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Finanças</h1>
        <p class="text-gray-500 mt-2">Crie sua conta familiar</p>
      </div>

      <div class="card">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Criar conta</h2>

        <div v-if="errors.general" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ errors.general }}</div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="label">Nome da família</label>
            <input v-model="form.family_name" type="text" required class="input" placeholder="Família Silva" />
            <p v-if="errors.family_name" class="text-red-600 text-xs mt-1">{{ errors.family_name[0] }}</p>
          </div>
          <div>
            <label class="label">Seu nome</label>
            <input v-model="form.name" type="text" required class="input" placeholder="João Silva" />
            <p v-if="errors.name" class="text-red-600 text-xs mt-1">{{ errors.name[0] }}</p>
          </div>
          <div>
            <label class="label">E-mail</label>
            <input v-model="form.email" type="email" required class="input" placeholder="joao@email.com" />
            <p v-if="errors.email" class="text-red-600 text-xs mt-1">{{ errors.email[0] }}</p>
          </div>
          <div>
            <label class="label">Senha</label>
            <input v-model="form.password" type="password" required class="input" placeholder="Mínimo 8 caracteres" />
            <p v-if="errors.password" class="text-red-600 text-xs mt-1">{{ errors.password[0] }}</p>
          </div>
          <div>
            <label class="label">Confirmar senha</label>
            <input v-model="form.password_confirmation" type="password" required class="input" />
          </div>
          <button type="submit" :disabled="loading" class="btn-primary w-full mt-2">
            {{ loading ? 'Criando conta...' : 'Criar conta' }}
          </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-500">
          Já tem conta?
          <RouterLink to="/login" class="text-primary-600 hover:underline font-medium">Entrar</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>
