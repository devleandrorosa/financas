import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login',           name: 'login',    component: () => import('@/views/LoginView.vue'),    meta: { guest: true } },
  { path: '/register',        name: 'register', component: () => import('@/views/RegisterView.vue'), meta: { guest: true } },
  { path: '/invite/:token',   name: 'invite',   component: () => import('@/views/InviteView.vue'),   meta: { guest: true } },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '',               name: 'dashboard',       component: () => import('@/views/DashboardView.vue') },
      { path: 'transactions',   name: 'transactions',    component: () => import('@/views/TransactionsView.vue') },
      { path: 'categories',     name: 'categories',      component: () => import('@/views/CategoriesView.vue') },
      { path: 'bank-accounts',  name: 'bank-accounts',   component: () => import('@/views/BankAccountsView.vue') },
      { path: 'credit-cards',   name: 'credit-cards',    component: () => import('@/views/CreditCardsView.vue') },
      { path: 'budgets',        name: 'budgets',         component: () => import('@/views/BudgetsView.vue') },
      { path: 'goals',          name: 'goals',           component: () => import('@/views/GoalsView.vue') },
      { path: 'recurring',      name: 'recurring',       component: () => import('@/views/RecurringRulesView.vue') },
      { path: 'investments',    name: 'investments',     component: () => import('@/views/InvestmentsView.vue') },
      { path: 'family',         name: 'family',          component: () => import('@/views/FamilyView.vue') },
      { path: 'ai-import',      name: 'ai-import',       component: () => import('@/views/AIImportView.vue') },
      { path: 'projection',     name: 'projection',      component: () => import('@/views/ProjectionView.vue') },
      { path: 'settings',       name: 'settings',        component: () => import('@/views/SettingsView.vue') },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) return { name: 'login' }
  if (to.meta.guest && auth.isAuthenticated) return { name: 'dashboard' }
})

export default router
