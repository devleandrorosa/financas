<?php

namespace Tests\Feature\Projection;

use Tests\TenantTestCase;

class ProjectionTest extends TenantTestCase
{
    private int $categoryId;
    private int $bankAccountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryId = $this->tenantInsert('categories', [
            'name' => 'Salário', 'type' => 'income', 'parent_id' => null,
        ]);

        $this->bankAccountId = $this->tenantInsert('bank_accounts', [
            'name' => 'Conta', 'bank' => 'Nubank', 'type' => 'checking', 'balance' => 0,
        ]);
    }

    private function createRule(array $override = []): void
    {
        $this->withHeaders($this->authHeaders())
             ->postJson('/api/v1/recurring-rules', array_merge([
                 'description'     => 'Regra padrão',
                 'amount'          => 300000,
                 'type'            => 'income',
                 'frequency'       => 'monthly',
                 'start_date'      => '2026-01-01',
                 'category_id'     => $this->categoryId,
                 'bank_account_id' => $this->bankAccountId,
             ], $override));
    }

    public function test_projection_returns_200(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/projection');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_projection_default_is_6_months(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/projection');

        $response->assertStatus(200);
        $this->assertCount(6, $response->json('data'));
    }

    public function test_projection_respects_months_param(): void
    {
        foreach ([3, 6, 12] as $months) {
            $response = $this->withHeaders($this->authHeaders())
                             ->getJson("/api/v1/projection?months={$months}");

            $response->assertStatus(200);
            $this->assertCount($months, $response->json('data'), "Falhou para months={$months}");
        }
    }

    public function test_projection_item_has_required_structure(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/projection?months=1');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['year', 'month', 'label', 'income', 'expense', 'balance', 'cumulative']]]);
    }

    public function test_projection_reflects_active_rules(): void
    {
        $this->createRule(['amount' => 500000, 'type' => 'income']);
        $this->createRule(['amount' => 200000, 'type' => 'expense', 'description' => 'Aluguel']);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/projection?months=1');

        $month = $response->json('data.0');
        $this->assertSame(500000, $month['income']);
        $this->assertSame(200000, $month['expense']);
        $this->assertSame(300000, $month['balance']);
    }

    public function test_projection_inactive_rule_is_ignored(): void
    {
        $create = $this->withHeaders($this->authHeaders())
                       ->postJson('/api/v1/recurring-rules', [
                           'description'     => 'Inativa',
                           'amount'          => 999999,
                           'type'            => 'income',
                           'frequency'       => 'monthly',
                           'start_date'      => '2026-01-01',
                           'category_id'     => $this->categoryId,
                           'bank_account_id' => $this->bankAccountId,
                       ]);

        // Toggle para inativar
        $id = $create->json('data.id');
        if ($create->json('data.active')) {
            $this->withHeaders($this->authHeaders())
                 ->patchJson("/api/v1/recurring-rules/{$id}/toggle");
        }

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/projection?months=1');

        $this->assertSame(0, $response->json('data.0.income'));
    }

    public function test_projection_cumulative_accumulates_correctly(): void
    {
        $this->createRule(['amount' => 100000, 'type' => 'income']);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/projection?months=3');

        $data = $response->json('data');
        $this->assertSame(100000, $data[0]['cumulative']);
        $this->assertSame(200000, $data[1]['cumulative']);
        $this->assertSame(300000, $data[2]['cumulative']);
    }

    public function test_projection_without_rules_returns_zeros(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/projection?months=3');

        foreach ($response->json('data') as $month) {
            $this->assertSame(0, $month['income']);
            $this->assertSame(0, $month['expense']);
            $this->assertSame(0, $month['balance']);
        }
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/projection')->assertStatus(401);
    }
}
