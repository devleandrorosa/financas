# Backend — Convenções Laravel 11

## Estrutura de Módulos

Cada módulo em `app/Modules/{Nome}/`:
```
Controllers/   — thin: recebe request, chama service, retorna JSON
Services/      — regras de negócio
Requests/      — Form Requests com validação
routes.php     — carregado automaticamente via glob em bootstrap/app.php
```
Não há camada Repository — Services usam Eloquent diretamente.

Rotas sempre com prefixo `api/v1/` (configurado em bootstrap/app.php) e middleware `['auth:sanctum', 'tenant']`:
```php
Route::prefix('nome')->middleware(['auth:sanctum', 'tenant'])->group(function () { ... });
```

## Models

**Regra absoluta:** todo model de tabela tenant (schema `family_*`) deve `extends TenantModel`, nunca `extends Model`.

`TenantModel` (`app/Models/TenantModel.php`) sobrescreve `resolveRouteBinding` para setar `search_path` antes do Laravel resolver o binding — necessário porque `SubstituteBindings` roda antes do middleware `tenant`.

Models públicos (users, families, invitations) estendem `Model` normalmente.

## Controllers

Padrão de resposta:
```php
// Sucesso
return response()->json(['data' => $data, 'status' => 200]);
return response()->json(['data' => $item, 'message' => 'Criado.', 'status' => 201], 201);
// Deleção
return response()->json(['message' => 'Removido.', 'status' => 200]);
```
Injetar Service via constructor. Não colocar lógica de negócio no controller.

## Serviços Críticos

**`TenantProvisioningService`**
- `schemaName(string $slug): string` — converte slug → nome do schema (troca `-` por `_`)
- `provision(string $slug)` — cria schema + 12 tabelas + seed de categorias
- Chamado apenas no Register

**`TransactionService`**
- Lida com statement de cartão: dia após `closing_day` → statement do mês seguinte
- Criação de parcelas: `createInstallments()` gera N registros em `installments`
- `recalcStatementTotal()` após qualquer alteração de transação de cartão

## Fila / Worker

- Driver: Redis
- Job despachado com `dispatch(new MinhaJob(...))`
- Worker: `php artisan queue:work` (container `worker`)
- **Jobs não passam pelo middleware `tenant`** — todo Job que acessa tabela tenant deve setar `search_path` manualmente:
  ```php
  DB::statement("SET search_path = \"{$schema}\", public");
  ```
  O slug da família é passado no construtor do Job e convertido com `TenantProvisioningService::schemaName($slug)`.

## AI Import (`app/Modules/AI/`)

Módulo completo: upload → processamento assíncrono → revisão → confirmação.

Endpoints: `POST /ai/import`, `GET /ai/import/{session}`, `POST /ai/import/{session}/confirm`

Job `ProcessAIImportJob`:
- Chama **Claude Haiku** via SDK `anthropic-ai/sdk` (`Anthropic\Client`)
- Chave em `.env`: `ANTHROPIC_API_KEY=...`
- PDF → `DocumentBlockParam::with(source: Base64PDFSource::with(...))` + text block; CSV/TXT → texto direto
- Strip de markdown fences antes de `json_decode()` (Claude pode envolver em ```json ... ```)
- Erros → `session->update(['status' => 'failed', 'error_message' => $e->getMessage()])`

**Armadilha:** Models `AIImportSession` e `AIImportItem` exigem `protected $table` explícito.
Laravel converte `AIImportSession` para `a_i_import_sessions` (divide em cada letra maiúscula).
Sempre declarar: `protected $table = 'ai_import_sessions'`.

## Módulo Projection (`app/Modules/Projection/`)

`ProjectionService::project(int $months)` itera meses a partir do mês atual usando `RecurringRule::where('active', true)`.

Lógica por frequência:
- `monthly` → 1 ocorrência por mês
- `yearly` → só no mês de `start_date`
- `weekly` → conta quantas vezes o dia-da-semana de `start_date` ocorre no mês
- `daily` → `amount × dias_no_mês`

Retorna array com `year`, `month`, `label`, `income`, `expense`, `balance`, `cumulative` (saldo acumulado crescente).

## Autenticação

Sanctum stateless. Token retornado no login, enviado via `Authorization: Bearer`. Sem sessão/cookie. `auth()->user()` disponível após middleware `auth:sanctum`.

## Banco de Dados

- Migrations convencionais do Laravel ficam em `database/migrations/` (schema public)
- Tabelas tenant NÃO usam migrations — são criadas via SQL raw em `TenantProvisioningService::createTables()`
- Ao adicionar coluna a tabela tenant: adicionar o SQL em `createTables()` + `ALTER TABLE` manual para schemas existentes
- Valores monetários: BIGINT em centavos, nunca DECIMAL/FLOAT
