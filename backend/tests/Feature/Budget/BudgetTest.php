<?php

namespace Tests\Feature\Budget;

use Tests\TenantTestCase;

class BudgetTest extends TenantTestCase
{
    private int $categoryId;
    private int $bankAccountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryId = $this->tenantInsert('categories', [
            'name' => 'Alimentação', 'type' => 'expense', 'parent_id' => null,
        ]);

        $this->bankAccountId = $this->tenantInsert('bank_accounts', [
            'name' => 'Conta', 'bank' => 'Nubank', 'type' => 'checking', 'balance' => 0,
        ]);
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function test_list_budgets_returns_200(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/budgets?year=2026&month=5');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_list_returns_budget_with_spent_and_remaining(): void
    {
        // Cria orçamento
        $this->withHeaders($this->authHeaders())
             ->postJson('/api/v1/budgets', [
                 'category_id' => $this->categoryId,
                 'year'        => 2026,
                 'month'       => 5,
                 'amount'      => 50000,
             ]);

        // Cria transação de despesa nessa categoria
        $this->withHeaders($this->authHeaders())
             ->postJson('/api/v1/transactions', [
                 'description'     => 'Supermercado',
                 'amount'          => 20000,
                 'type'            => 'expense',
                 'date'            => '2026-05-10',
                 'status'          => 'completed',
                 'category_id'     => $this->categoryId,
                 'bank_account_id' => $this->bankAccountId,
             ]);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/budgets?year=2026&month=5');

        $response->assertStatus(200);
        $budget = collect($response->json('data'))->firstWhere('category_id', $this->categoryId);

        $this->assertSame(50000, $budget['amount']);
        $this->assertSame(20000, (int) $budget['spent']);
        $this->assertSame(30000, (int) $budget['remaining']);
    }

    // ── Store / Upsert ────────────────────────────────────────────────────

    public function test_create_budget_returns_201(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/budgets', [
                             'category_id' => $this->categoryId,
                             'year'        => 2026,
                             'month'       => 5,
                             'amount'      => 80000,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.amount', 80000)
                 ->assertJsonPath('data.category_id', $this->categoryId);
    }

    public function test_upsert_updates_existing_budget(): void
    {
        $this->withHeaders($this->authHeaders())
             ->postJson('/api/v1/budgets', [
                 'category_id' => $this->categoryId,
                 'year'        => 2026,
                 'month'       => 5,
                 'amount'      => 50000,
             ]);

        // Cria novamente com valor diferente — deve atualizar, não duplicar
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/budgets', [
                             'category_id' => $this->categoryId,
                             'year'        => 2026,
                             'month'       => 5,
                             'amount'      => 90000,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.amount', 90000);

        $listResponse = $this->withHeaders($this->authHeaders())
                             ->getJson('/api/v1/budgets?year=2026&month=5');

        $this->assertCount(1, $listResponse->json('data'));
    }

    public function test_create_budget_fails_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/budgets', []);

        $response->assertStatus(422)->assertJsonStructure(['errors']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function test_delete_budget_returns_200(): void
    {
        $create = $this->withHeaders($this->authHeaders())
                       ->postJson('/api/v1/budgets', [
                           'category_id' => $this->categoryId,
                           'year'        => 2026,
                           'month'       => 5,
                           'amount'      => 50000,
                       ]);

        $id = $create->json('data.id');

        $this->withHeaders($this->authHeaders())
             ->deleteJson("/api/v1/budgets/{$id}")
             ->assertStatus(200);
    }
}
