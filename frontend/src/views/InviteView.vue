<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const token = route.params.token
const form = ref({ name: '', password: '', password_confirmation: '' })
const error = ref('')
const fieldErrors = ref({})
const loading = ref(false)

async function submit() {
  error.value = ''
  fieldErrors.value = {}
  loading.value = true
  try {
    const res = await authApi.acceptInvite({ ...form.value, token })
    auth.setAuth(res.data.data)
    router.push('/')
  } catch (e) {
    fieldErrors.value = e.response?.data?.errors || {}
    error.value = e.response?.data?.message || 'Erro ao aceitar convite.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Você foi convidado!</h1>
        <p class="text-gray-500 text-sm mt-1">Crie sua conta para acessar o sistema de finanças da família.</p>
      </div>

      <div class="card">
        <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="label">Seu nome</label>
            <input v-model="form.name" type="text" class="input" placeholder="Como quer ser chamado?" required autofocus />
            <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-600">{{ fieldErrors.name[0] }}</p>
          </div>

          <div>
            <label class="label">Senha</label>
            <input v-model="form.password" type="password" class="input" placeholder="Mínimo 8 caracteres" required />
            <p v-if="fieldErrors.password" class="mt-1 text-xs text-red-600">{{ fieldErrors.password[0] }}</p>
          </div>

          <div>
            <label class="label">Confirmar senha</label>
            <input v-model="form.password_confirmation" type="password" class="input" placeholder="Repita a senha" required />
          </div>

          <button type="submit" :disabled="loading" class="btn-primary w-full">
            {{ loading ? 'Criando conta...' : 'Criar conta e entrar' }}
          </button>
        </form>
      </div>

      <p class="text-center text-sm text-gray-400 mt-4">
        Já tem uma conta?
        <RouterLink to="/login" class="text-primary-600 hover:underline">Entrar</RouterLink>
      </p>
    </div>
  </div>
</template>
