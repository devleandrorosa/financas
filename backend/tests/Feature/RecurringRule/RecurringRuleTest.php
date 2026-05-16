<?php

namespace Tests\Feature\RecurringRule;

use Tests\TenantTestCase;

class RecurringRuleTest extends TenantTestCase
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

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'description'     => 'Salário mensal',
            'amount'          => 500000,
            'type'            => 'income',
            'frequency'       => 'monthly',
            'start_date'      => '2026-01-01',
            'category_id'     => $this->categoryId,
            'bank_account_id' => $this->bankAccountId,
        ], $override);
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function test_list_returns_200(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/recurring-rules');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function test_create_recurring_rule_returns_201(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/recurring-rules', $this->validPayload());

        $response->assertStatus(201)
                 ->assertJsonPath('data.description', 'Salário mensal')
                 ->assertJsonPath('data.amount', 500000)
                 ->assertJsonPath('data.frequency', 'monthly');
    }

    public function test_create_with_all_frequencies(): void
    {
        foreach (['monthly', 'weekly', 'yearly', 'daily'] as $freq) {
            $response = $this->withHeaders($this->authHeaders())
                             ->postJson('/api/v1/recurring-rules', $this->validPayload([
                                 'frequency'   => $freq,
                                 'description' => "Regra {$freq}",
                             ]));

            $response->assertStatus(201, "Frequency {$freq} falhou");
        }
    }

    public function test_create_fails_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/recurring-rules', []);

        $response->assertStatus(422)->assertJsonStructure(['errors']);
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function test_update_recurring_rule_returns_200(): void
    {
        $create = $this->withHeaders($this->authHeaders())
                       ->postJson('/api/v1/recurring-rules', $this->validPayload());

        $id = $create->json('data.id');

        $response = $this->withHeaders($this->authHeaders())
                         ->putJson("/api/v1/recurring-rules/{$id}", $this->validPayload([
                             'description' => 'Atualizado',
                             'amount'      => 600000,
                         ]));

        $response->assertStatus(200)
                 ->assertJsonPath('data.description', 'Atualizado')
                 ->assertJsonPath('data.amount', 600000);
    }

    // ── Toggle ────────────────────────────────────────────────────────────

    public function test_toggle_deactivates_active_rule(): void
    {
        $create = $this->withHeaders($this->authHeaders())
                       ->postJson('/api/v1/recurring-rules', $this->validPayload());

        $id = $create->json('data.id');
        $wasActive = $create->json('data.active');

        $response = $this->withHeaders($this->authHeaders())
                         ->patchJson("/api/v1/recurring-rules/{$id}/toggle");

        $response->assertStatus(200);
        $this->assertNotSame($wasActive, $response->json('data.active'));
    }

    public function test_toggle_twice_restores_original_state(): void
    {
        $create = $this->withHeaders($this->authHeaders())
                       ->postJson('/api/v1/recurring-rules', $this->validPayload());

        $id = $create->json('data.id');
        $original = $create->json('data.active');

        $this->withHeaders($this->authHeaders())->patchJson("/api/v1/recurring-rules/{$id}/toggle");
        $second = $this->withHeaders($this->authHeaders())->patchJson("/api/v1/recurring-rules/{$id}/toggle");

        $this->assertSame($original, $second->json('data.active'));
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function test_delete_recurring_rule_returns_200(): void
    {
        $create = $this->withHeaders($this->authHeaders())
                       ->postJson('/api/v1/recurring-rules', $this->validPayload());

        $id = $create->json('data.id');

        $this->withHeaders($this->authHeaders())
             ->deleteJson("/api/v1/recurring-rules/{$id}")
             ->assertStatus(200);
    }
}
