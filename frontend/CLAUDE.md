# Frontend — Convenções Vue 3

## Stack

Vue 3 + `<script setup>` (Composition API), Pinia, Vue Router 4, Axios, Tailwind CSS 3. Sem PrimeVue — UI feita com Tailwind + classes customizadas.

Dev server: `http://localhost:5174` (container node). Proxy Vite: `/api` → `http://nginx:80`.

## Estrutura

```
src/
  api/         — módulos axios por domínio (auth, transactions, categories...)
  stores/      — Pinia (auth.js)
  router/      — index.js com guards requiresAuth / guest
  utils/       — currency.js, date.js
  views/       — uma view por rota
  components/  — layout/AppLayout.vue (sidebar)
```

## HTTP Client

`src/api/http.js` — Axios com `baseURL: '/api/v1'`, interceptor injeta `Bearer token` do localStorage, 401 → redirect `/login`.

Cada módulo de API exporta funções nomeadas:
```js
export const transactionsApi = {
  list: (params) => http.get('/transactions', { params }),
  create: (data) => http.post('/transactions', data),
  update: (id, data) => http.put(`/transactions/${id}`, data),
  remove: (id) => http.delete(`/transactions/${id}`),
}
```

## Auth Store (Pinia)

`src/stores/auth.js` — `token` e `user` em localStorage. Métodos: `setAuth({token, user})`, `clearAuth()`, `logout()`. Guard do router lê `auth.isAuthenticated`.

## Utilitários de Moeda e Data

```js
// src/utils/currency.js
formatBRL(cents)     // 1550 → "R$ 15,50"
parseCents(string)   // "15,50" → 1550

// src/utils/date.js
formatDate(value)    // "2026-05-14" → "14/05/2026"
currentYearMonth()   // { year: 2026, month: 5 }
monthLabel(y, m)     // "maio de 2026"
```

Toda manipulação de valor monetário usa centavos (inteiros). Nunca enviar float para API.

## CSS / Tailwind

Classes customizadas definidas em `src/style.css`:
- `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-sm`
- `.input` — campo de formulário padrão
- `.label` — label de formulário padrão
- `.card` — container com borda e sombra suave

## Padrão de Views

Cada view segue o mesmo padrão:
1. `ref` + `reactive` para estado local
2. `onMounted` carrega dados da API
3. Formulário em modal (`v-if="showForm"`) com `fixed inset-0 bg-black/50`
4. Erros de API em `formError` exibido no topo do modal
5. Confirmação de deleção via `confirm()` nativo

## Rotas Registradas

| Path | View |
|------|------|
| `/login` | LoginView |
| `/register` | RegisterView |
| `/` | DashboardView |
| `/transactions` | TransactionsView |
| `/categories` | CategoriesView |
| `/bank-accounts` | BankAccountsView |
| `/credit-cards` | CreditCardsView |
| `/budgets` | BudgetsView |
| `/goals` | GoalsView |
| `/recurring` | RecurringRulesView |
| `/investments` | InvestmentsView |
| `/family` | FamilyView |
| `/ai-import` | AIImportView |
| `/projection` | ProjectionView |

**Ainda não implementadas:** `/invite/:token`, `/settings`

## AI Import View (`AIImportView.vue`)

4 estágios: `upload` → `processing` → `review` → `done`.

Polling a cada 3 s (`POLL_INTERVAL_MS = 3000`), timeout de 120 s. Após 5 erros consecutivos de poll → para e exibe erro. Botão "Cancelar" disponível durante processing.

Stage `review`: tabela editável com checkbox por linha (aceitar/rejeitar), campos de descrição, valor, tipo, data, categoria, conta. Bulk action `toggleAll`.

## Projection View (`ProjectionView.vue`)

Seletor 3/6/12 meses. 3 cards de resumo. Gráfico de barras SVG nativo (sem Chart.js): barras verdes (receita) e vermelhas (despesa) por mês, eixo Y com ticks, largura responsiva calculada em `computed`. Tabela com saldo mensal e saldo acumulado.

## Notas

- Sidebar definida em `AppLayout.vue` — ao adicionar rota, adicionar item de nav lá também
- Lazy loading em todas as rotas (import dinâmico)
- Sem SSR, SPA puro
- Gráficos: usar SVG nativo em vez de Chart.js (sem dependência extra)
