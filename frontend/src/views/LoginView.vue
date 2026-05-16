<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const res = await authApi.login(form.value)
    auth.setAuth(res.data.data)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Credenciais inválidas.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Finanças</h1>
        <p class="text-gray-500 mt-2">Gestão financeira familiar</p>
      </div>

      <div class="card">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Entrar</h2>

        <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="label">E-mail</label>
            <input v-model="form.email" type="email" required class="input" placeholder="seu@email.com" />
          </div>
          <div>
            <label class="label">Senha</label>
            <input v-model="form.password" type="password" required class="input" placeholder="••••••••" />
          </div>
          <button type="submit" :disabled="loading" class="btn-primary w-full mt-2">
            {{ loading ? 'Entrando...' : 'Entrar' }}
          </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-500">
          Não tem conta?
          <RouterLink to="/register" class="text-primary-600 hover:underline font-medium">Criar conta</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>
