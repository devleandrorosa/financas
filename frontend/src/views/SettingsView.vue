<script setup>
import { ref, reactive } from 'vue'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const profile = reactive({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
})
const profileError = ref('')
const profileFieldErrors = ref({})
const profileSuccess = ref('')
const savingProfile = ref(false)

const password = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordError = ref('')
const passwordFieldErrors = ref({})
const passwordSuccess = ref('')
const savingPassword = ref(false)

async function saveProfile() {
  profileError.value = ''
  profileFieldErrors.value = {}
  profileSuccess.value = ''
  savingProfile.value = true
  try {
    const res = await authApi.updateProfile({ name: profile.name, email: profile.email })
    const updatedUser = res.data.data
    auth.user.name = updatedUser.name
    auth.user.email = updatedUser.email
    localStorage.setItem('user', JSON.stringify(auth.user))
    profileSuccess.value = 'Perfil atualizado com sucesso.'
  } catch (e) {
    profileFieldErrors.value = e.response?.data?.errors || {}
    profileError.value = e.response?.data?.message || 'Erro ao atualizar perfil.'
  } finally {
    savingProfile.value = false
  }
}

async function savePassword() {
  passwordError.value = ''
  passwordFieldErrors.value = {}
  passwordSuccess.value = ''
  savingPassword.value = true
  try {
    await authApi.updatePassword({
      current_password: password.current_password,
      password: password.password,
      password_confirmation: password.password_confirmation,
    })
    passwordSuccess.value = 'Senha alterada com sucesso.'
    password.current_password = ''
    password.password = ''
    password.password_confirmation = ''
  } catch (e) {
    passwordFieldErrors.value = e.response?.data?.errors || {}
    passwordError.value = e.response?.data?.message || 'Erro ao alterar senha.'
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <div class="p-6 max-w-2xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Configurações</h1>
      <p class="text-gray-500 text-sm">Gerencie seu perfil e segurança da conta.</p>
    </div>

    <!-- Profile -->
    <div class="card mb-6">
      <h2 class="text-base font-semibold text-gray-900 mb-4">Perfil</h2>

      <div v-if="profileSuccess" class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm">{{ profileSuccess }}</div>
      <div v-if="profileError" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ profileError }}</div>

      <form @submit.prevent="saveProfile" class="space-y-4">
        <div>
          <label class="label">Nome</label>
          <input v-model="profile.name" type="text" class="input" required />
          <p v-if="profileFieldErrors.name" class="mt-1 text-xs text-red-600">{{ profileFieldErrors.name[0] }}</p>
        </div>
        <div>
          <label class="label">E-mail</label>
          <input v-model="profile.email" type="email" class="input" required />
          <p v-if="profileFieldErrors.email" class="mt-1 text-xs text-red-600">{{ profileFieldErrors.email[0] }}</p>
        </div>
        <div class="flex justify-end">
          <button type="submit" :disabled="savingProfile" class="btn-primary">
            {{ savingProfile ? 'Salvando...' : 'Salvar perfil' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Password -->
    <div class="card">
      <h2 class="text-base font-semibold text-gray-900 mb-4">Alterar senha</h2>

      <div v-if="passwordSuccess" class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm">{{ passwordSuccess }}</div>
      <div v-if="passwordError" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ passwordError }}</div>

      <form @submit.prevent="savePassword" class="space-y-4">
        <div>
          <label class="label">Senha atual</label>
          <input v-model="password.current_password" type="password" class="input" required />
          <p v-if="passwordFieldErrors.current_password" class="mt-1 text-xs text-red-600">{{ passwordFieldErrors.current_password[0] }}</p>
        </div>
        <div>
          <label class="label">Nova senha</label>
          <input v-model="password.password" type="password" class="input" placeholder="Mínimo 8 caracteres" required />
          <p v-if="passwordFieldErrors.password" class="mt-1 text-xs text-red-600">{{ passwordFieldErrors.password[0] }}</p>
        </div>
        <div>
          <label class="label">Confirmar nova senha</label>
          <input v-model="password.password_confirmation" type="password" class="input" required />
        </div>
        <div class="flex justify-end">
          <button type="submit" :disabled="savingPassword" class="btn-primary">
            {{ savingPassword ? 'Alterando...' : 'Alterar senha' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
