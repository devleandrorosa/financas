# Financas — Contexto do Projeto

Sistema de gestão financeira pessoal/familiar. Stack: **Laravel 11 + Vue 3 + PostgreSQL 16 multi-tenant por schema**.

## Infraestrutura Docker

| Container | Porta host | Uso |
|-----------|-----------|-----|
| nginx     | 8081      | Proxy para PHP-FPM |
| app       | —         | PHP 8.3 (Laravel) |
| worker    | —         | `php artisan queue:work` (Redis) |
| node      | 5174      | Vite dev server |
| postgres  | 5433      | PostgreSQL 16 |
| redis     | 6380      | Cache + filas |
| mailpit   | 8025      | SMTP dev |

Comandos frequentes:
```bash
docker compose exec app php artisan <cmd>
docker compose exec node npm run <cmd>
docker compose logs -f worker
```

## Multi-tenancy por Schema PostgreSQL

- Schema `public`: tabelas globais — `users`, `families`, `invitations`
- Schema `family_{slug}` (underscores, nunca hífens): tabelas por família
- `TenantProvisioningService::schemaName($slug)` — converte slug para nome de schema
- Middleware `SetTenantSchema` (`tenant`) executa `SET search_path = "family_{slug}", public` em cada request

**Armadilha crítica:** Laravel resolve route model bindings (SubstituteBindings) **antes** do middleware `tenant` rodar. Por isso todo model de tabela tenant herda `TenantModel`, que sobrescreve `resolveRouteBinding` para setar o `search_path` antecipadamente. **Nunca usar `extends Model` direto em models de tabelas tenant.**

## Status de Implementação

**Concluído:**
- Fase 1: Docker Compose
- Fase 2: Backend fundação (Auth, Family, TenantProvisioningService, migrações public)
- Fase 3: Módulos backend — Category, BankAccount, CreditCard, Transaction, RecurringRule, Investment, Budget, Goal
- Fase 4: Frontend SPA — todas as views acima + Dashboard, Login, Register
- Fase 5: AI Import (`POST /api/v1/ai/import` + Job Claude Haiku + Frontend `/ai-import`)
- Fase 6: Módulo Projection (`GET /api/v1/projection` + Frontend `/projection`)

**Pendente:**
- Módulo Report (DRE mensal, breakdown por categoria, balanço por banco, patrimônio)
- Frontend: `/invite/{token}`, `/settings`, gráficos no Dashboard (Chart.js)
- AI Import: sugestão automática de categoria (passar categorias ao Gemini), suporte a XLSX

## Convenções Gerais

- Valores monetários: **inteiros em centavos (BIGINT)**. R$ 15,50 = `1550`. Nunca float.
- Datas: ISO 8601 `YYYY-MM-DD`
- Auth: `Authorization: Bearer {token}` (Sanctum stateless)
- Base URL da API: `/api/v1/`
- Resposta sucesso: `{"data": ..., "message": "...", "status": 200}`
- Resposta erro: `{"message": "...", "errors": {"campo": ["msg"]}, "status": 422}`
