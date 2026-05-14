# Financas — Sistema de Gestão Financeira Pessoal e Familiar

Sistema completo de gestão financeira com integração de LLM (Claude) para importação inteligente de documentos financeiros (PDFs de faturas, planilhas), multi-tenancy por família via schemas PostgreSQL, e dashboard analítico em tempo real.

---

## Visão Geral

O sistema permite que famílias gerenciem suas finanças de forma colaborativa. Cada família possui seu próprio ambiente isolado (schema PostgreSQL), com dados de receitas, despesas, investimentos, cartões de crédito, orçamentos e metas. A diferencial principal é o agente Claude que lê PDFs de faturas e planilhas, extrai os lançamentos e apresenta para o usuário confirmar antes de salvar.

---

## Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│                      Docker Compose                         │
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │  nginx   │  │   app    │  │  worker  │  │   node   │  │
│  │ :8081    │→ │ PHP 8.3  │  │ (filas)  │  │ Vite:5174│  │
│  └──────────┘  └────┬─────┘  └────┬─────┘  └──────────┘  │
│                     │              │                        │
│  ┌──────────┐  ┌────▼──────────────▼─────┐  ┌──────────┐ │
│  │ mailpit  │  │      PostgreSQL 16       │  │  redis   │ │
│  │ :8025    │  │  schema: public (global) │  │  :6380   │ │
│  └──────────┘  │  schema: family_{slug}  │  └──────────┘ │
│                └─────────────────────────┘                 │
└─────────────────────────────────────────────────────────────┘
```

### Camadas do Backend (Laravel 11)

```
Request → Middleware (Auth + SetTenantSchema) → Controller
       → Service (regras de negócio)
       → Repository (acesso a dados)
       → Model (Eloquent)
       → PostgreSQL schema do tenant
```

---

## Stack Tecnológica

### Backend
| Tecnologia | Versão | Uso |
|---|---|---|
| PHP | 8.3 | Runtime |
| Laravel | 11 | Framework |
| PostgreSQL | 16 | Banco de dados (multi-tenant) |
| Redis | 7 | Cache + filas |
| Laravel Sanctum | — | Autenticação stateless (tokens) |
| Laravel Queues | — | Processamento assíncrono do LLM |
| Anthropic PHP SDK | — | Integração Claude |
| Spatie Laravel-Permission | — | Roles (admin/member) |

### Frontend
| Tecnologia | Versão | Uso |
|---|---|---|
| Vue.js | 3 | Framework SPA |
| Vite | — | Build tool + dev server |
| PrimeVue | 4 | Componentes UI |
| Pinia | — | State management |
| Vue Router | 4 | Roteamento SPA |
| Chart.js | — | Gráficos (via PrimeVue Charts) |
| Axios | — | HTTP client |
| Tailwind CSS | — | Utilitários CSS |

### Infraestrutura
| Serviço | Porta | Descrição |
|---|---|---|
| Nginx | 8081 | Proxy reverso para PHP-FPM |
| Vite Dev Server | 5174 | Frontend em desenvolvimento |
| PostgreSQL | 5433 | Banco de dados |
| Redis | 6380 | Cache e filas |
| Mailpit | 8025 | Visualizador de e-mails (dev) |
| Xdebug | 9003 | Debug PHP (VSCode) |

---

## Multi-Tenancy por Schema PostgreSQL

Cada família é um **tenant isolado** representado por um schema PostgreSQL dedicado.

### Schema `public` (global — todos os tenants)
```sql
users           -- usuários do sistema
families        -- famílias (tenants)
invitations     -- convites por e-mail
```

### Schema `family_{slug}` (por família)
```sql
categories            -- categorias de gastos/receitas
bank_accounts         -- contas bancárias
credit_cards          -- cartões de crédito
credit_card_statements-- faturas mensais
transactions          -- lançamentos financeiros
installments          -- parcelas de cartão
recurring_rules       -- regras de recorrência
investments           -- investimentos
budgets               -- orçamento por categoria/mês
goals                 -- metas financeiras
ai_import_sessions    -- sessões de importação via LLM
ai_import_items       -- itens extraídos aguardando confirmação
```

### Como o tenant é resolvido

1. Usuário faz login → token Sanctum emitido
2. Cada request autenticada passa pelo middleware `SetTenantSchema`
3. Middleware executa `SET search_path = family_{slug}, public`
4. Todos os models Eloquent operam automaticamente no schema correto

---

## Módulos do Sistema

### Auth
Registro, login e aceite de convites. Ao registrar, o usuário cria uma nova família e o schema correspondente é provisionado automaticamente. O login retorna o token Sanctum e o slug da família.

**Rotas:**
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/invite/accept`

---

### Family
Gerenciamento da família (tenant). O admin pode convidar membros por e-mail, promover a admin ou remover membros. O provisionamento do schema PostgreSQL ocorre automaticamente ao criar a família.

**Rotas:**
- `GET /api/v1/family`
- `POST /api/v1/family/invite`
- `DELETE /api/v1/family/members/{id}`
- `PATCH /api/v1/family/members/{id}/role`

---

### Transaction
Lançamentos financeiros (receitas e despesas). Suporta lançamentos avulsos, parcelas de cartão de crédito e lançamentos gerados por regras de recorrência.

**Campos:** data, descrição, valor, tipo (receita/despesa), categoria, conta bancária, cartão de crédito, status (confirmado/pendente), notas.

**Rotas:**
- `GET /api/v1/transactions` (filtros: mês, ano, categoria, banco, tipo)
- `POST /api/v1/transactions`
- `PUT /api/v1/transactions/{id}`
- `DELETE /api/v1/transactions/{id}`

---

### Category
Categorias customizadas por família com suporte a subcategorias (hierarquia). Um seed de categorias padrão é aplicado ao provisionar cada novo schema.

**Categorias padrão:** Moradia, Alimentação (Bandeco, Lanchonete, Mercado, Restaurante), Transporte (Aplicativo, Ônibus, Viagem), Saúde (Convênio, Drogaria), Compras (Entretenimento, Pessoal, Presente), Serviços (Assinatura, Prestado), Eventos (Delivery, Rolê), Outros, Investimento.

**Rotas:**
- `GET /api/v1/categories`
- `POST /api/v1/categories`
- `PUT /api/v1/categories/{id}`
- `DELETE /api/v1/categories/{id}`

---

### BankAccount
Contas bancárias da família. O saldo é calculado dinamicamente a partir dos lançamentos. Suporta múltiplos bancos e tipos (corrente, poupança, carteira digital).

**Bancos pré-configurados:** Nubank, C6 Bank, Mercado Pago, Santander, 99Pay.

**Rotas:**
- `GET /api/v1/bank-accounts`
- `POST /api/v1/bank-accounts`
- `PUT /api/v1/bank-accounts/{id}`
- `DELETE /api/v1/bank-accounts/{id}`

---

### CreditCard
Cartões de crédito com ciclo de fatura. Cada cartão tem dia de fechamento e vencimento. Compras parceladas geram uma parcela por mês automaticamente na data correta.

**Rotas:**
- `GET /api/v1/credit-cards`
- `GET /api/v1/credit-cards/{id}/statement/{year}/{month}`
- `POST /api/v1/credit-cards`
- `PUT /api/v1/credit-cards/{id}`
- `DELETE /api/v1/credit-cards/{id}`

---

### Recurring
Regras de recorrência para lançamentos que se repetem (aluguel, salário, assinaturas). Um job agendado gera os lançamentos automaticamente no início de cada mês. O usuário pode pausar ou cancelar regras.

**Frequências:** diária, semanal, quinzenal, mensal, bimestral, trimestral, semestral, anual.

**Rotas:**
- `GET /api/v1/recurring`
- `POST /api/v1/recurring`
- `PUT /api/v1/recurring/{id}`
- `DELETE /api/v1/recurring/{id}`
- `PATCH /api/v1/recurring/{id}/toggle`

---

### Investment
Controle de investimentos com histórico de aportes. Suporta múltiplos tipos e instituições.

**Tipos:** CDB, LCI/LCA, Tesouro Direto, Ações, FIIs, Criptomoedas, Poupança, Outros.

**Rotas:**
- `GET /api/v1/investments`
- `POST /api/v1/investments`
- `PUT /api/v1/investments/{id}`
- `DELETE /api/v1/investments/{id}`

---

### Budget
Orçamento mensal por categoria. Permite definir um limite de gasto e acompanhar o progresso em tempo real.

**Rotas:**
- `GET /api/v1/budgets/{year}/{month}`
- `POST /api/v1/budgets`
- `PUT /api/v1/budgets/{id}`
- `DELETE /api/v1/budgets/{id}`

---

### Goal
Metas financeiras com valor alvo e data limite. O progresso é calculado a partir de lançamentos vinculados à meta.

**Tipos:** Poupança, Viagem, Compra, Fundo de emergência, Aposentadoria, Outros.

**Rotas:**
- `GET /api/v1/goals`
- `POST /api/v1/goals`
- `PUT /api/v1/goals/{id}`
- `DELETE /api/v1/goals/{id}`

---

### Projection
Projeção financeira para os próximos N meses, baseada em recorrentes ativos e despesas fixas declaradas. Retorna receita projetada, despesa projetada e saldo projetado por mês.

**Rotas:**
- `GET /api/v1/projection?months=6`

---

### AI (Importação via Claude)

Fluxo completo de processamento de documentos financeiros:

```
1. Upload de arquivo (PDF, XLSX, CSV)
         ↓
2. Backend armazena + cria ai_import_session (status: processing)
         ↓
3. Job enfileirado no Redis
         ↓
4. Worker chama Claude API com arquivo + prompt estruturado
         ↓
5. Claude retorna JSON com lançamentos sugeridos
         ↓
6. Backend cria ai_import_items (status: pending)
         ↓
7. Frontend exibe tabela de confirmação
   (usuário edita / aceita / rejeita cada item)
         ↓
8. Endpoint de confirmação salva items aceitos como transactions
```

**Prompt Claude:** extrai data, descrição, valor (positivo=receita, negativo=despesa), categoria sugerida, banco/cartão identificado, tipo de lançamento.

**Rotas:**
- `POST /api/v1/ai/import` (upload do arquivo)
- `GET /api/v1/ai/import/{sessionId}` (polling do status)
- `POST /api/v1/ai/import/{sessionId}/confirm` (confirmar/rejeitar items)

---

### Report
Relatórios consolidados para análise financeira.

**Relatórios disponíveis:**
- **DRE Mensal:** receitas vs despesas vs resultado
- **Breakdown por Categoria:** participação percentual de cada categoria
- **Balanço por Banco:** saldo por conta bancária
- **Evolução do Patrimônio:** histórico do saldo total ao longo do tempo

**Rotas:**
- `GET /api/v1/reports/dre/{year}/{month}`
- `GET /api/v1/reports/categories/{year}/{month}`
- `GET /api/v1/reports/banks/{year}/{month}`
- `GET /api/v1/reports/patrimony?from={date}&to={date}`

---

## Telas do Frontend

| Módulo | Rota | Descrição |
|---|---|---|
| Login | `/login` | Autenticação |
| Registro | `/register` | Criar conta + família |
| Aceitar Convite | `/invite/{token}` | Entrar em família existente |
| Dashboard | `/` | Visão geral: saldo, gráficos, alertas |
| Transações | `/transactions` | Lista + filtros + lançamento manual |
| Categorias | `/categories` | CRUD de categorias |
| Contas | `/bank-accounts` | Contas bancárias e saldos |
| Cartões | `/credit-cards` | Cartões + faturas + parcelas |
| Recorrentes | `/recurring` | Regras de recorrência |
| Investimentos | `/investments` | Portfólio de investimentos |
| Orçamento | `/budget` | Orçamento vs realizado por categoria |
| Metas | `/goals` | Progresso das metas financeiras |
| Projeção | `/projection` | Gráfico de projeção futura |
| Importar (AI) | `/ai-import` | Upload + confirmação de documentos |
| Família | `/family` | Membros + convites |
| Configurações | `/settings` | Perfil e preferências |

---

## Configuração do Ambiente

### Pré-requisitos
- Docker e Docker Compose instalados
- Portas livres: 8081, 5174, 5433, 6380, 8025, 1025

### 1. Subir containers

```bash
cd ~/code/financas
docker compose up -d --build
```

### 2. Instalar e configurar Laravel

```bash
# Criar projeto Laravel
docker compose exec app composer create-project laravel/laravel .

# Copiar e configurar .env
cp backend/.env.example backend/.env
# (editar backend/.env — ver seção Variáveis de Ambiente)

# Gerar chave da aplicação
docker compose exec app php artisan key:generate

# Rodar migrations (schema public)
docker compose exec app php artisan migrate

# Rodar seeders
docker compose exec app php artisan db:seed
```

### 3. Instalar e configurar Vue.js

```bash
# Criar projeto Vue com Vite
docker compose run --rm node sh -c "npm create vite@latest . -- --template vue"

# Instalar dependências
docker compose run --rm node npm install
docker compose run --rm node npm install primevue primeicons pinia vue-router axios tailwindcss @tailwindcss/vite chart.js

# Iniciar dev server
docker compose up -d node
```

### 4. Acessar serviços

| Serviço | URL |
|---|---|
| API Laravel | http://localhost:8081 |
| Frontend Vue | http://localhost:5174 |
| Mailpit (e-mails) | http://localhost:8025 |

---

## Estrutura de Diretórios

```
financas/
├── docker-compose.yml
├── .gitignore
├── README.md
│
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── conf.d/
│   │       └── xdebug.ini
│   └── nginx/
│       └── default.conf
│
├── backend/                          ← Laravel 11 (criado via docker exec)
│   ├── app/
│   │   ├── Core/
│   │   │   ├── Contracts/
│   │   │   │   ├── RepositoryInterface.php
│   │   │   │   └── ServiceInterface.php
│   │   │   ├── Middleware/
│   │   │   │   └── SetTenantSchema.php
│   │   │   └── Traits/
│   │   │       └── UsesTenantSchema.php
│   │   └── Modules/
│   │       ├── Auth/
│   │       │   ├── Controllers/
│   │       │   ├── Services/
│   │       │   ├── Repositories/
│   │       │   ├── Requests/
│   │       │   ├── Resources/
│   │       │   └── routes.php
│   │       ├── Family/
│   │       ├── Transaction/
│   │       ├── Category/
│   │       ├── BankAccount/
│   │       ├── CreditCard/
│   │       ├── Recurring/
│   │       ├── Investment/
│   │       ├── Budget/
│   │       ├── Goal/
│   │       ├── Projection/
│   │       ├── AI/
│   │       └── Report/
│   └── ...
│
└── frontend/                         ← Vue.js 3 (criado via docker run)
    ├── src/
    │   ├── modules/
    │   │   ├── auth/
    │   │   ├── dashboard/
    │   │   ├── transactions/
    │   │   ├── categories/
    │   │   ├── bank-accounts/
    │   │   ├── credit-cards/
    │   │   ├── recurring/
    │   │   ├── investments/
    │   │   ├── budget/
    │   │   ├── goals/
    │   │   ├── projection/
    │   │   ├── ai-import/
    │   │   ├── family/
    │   │   └── settings/
    │   ├── stores/
    │   ├── router/
    │   ├── services/
    │   └── layouts/
    └── ...
```

---

## Variáveis de Ambiente (backend/.env)

```env
APP_NAME="Financas"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8081

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=financas
DB_USERNAME=financas
DB_PASSWORD=financas

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="no-reply@financas.local"
MAIL_FROM_NAME="Financas"

SANCTUM_STATELESS_DOMAINS=localhost:5174

ANTHROPIC_API_KEY=sk-ant-...

FILESYSTEM_DISK=local
```

---

## Convenções de API

- Base URL: `/api/v1/`
- Autenticação: `Authorization: Bearer {token}` (Sanctum)
- Formato de resposta: JSON
- Paginação: `?page=1&per_page=20`
- Filtros: query parameters (`?month=5&year=2026&category_id=1`)
- Datas: formato ISO 8601 (`YYYY-MM-DD`)
- Valores monetários: inteiros em centavos (ex: R$ 15,50 → `1550`)

### Estrutura de resposta padrão

```json
{
  "data": { ... },
  "message": "OK",
  "status": 200
}
```

### Estrutura de erro

```json
{
  "message": "Descrição do erro",
  "errors": {
    "campo": ["Mensagem de validação"]
  },
  "status": 422
}
```
