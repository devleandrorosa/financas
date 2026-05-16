<?php

namespace Tests\Feature\BankAccount;

use Tests\TenantTestCase;

class BankAccountTest extends TenantTestCase
{
    private function createAccount(array $override = []): int
    {
        return $this->tenantInsert('bank_accounts', array_merge([
            'name'    => 'Conta Corrente',
            'bank'    => 'Nubank',
            'type'    => 'checking',
            'balance' => 100000,
        ], $override));
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function test_list_bank_accounts_returns_200(): void
    {
        $this->createAccount();

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/bank-accounts');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data'])
                 ->assertJsonCount(1, 'data');
    }

    public function test_list_returns_empty_initially(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/bank-accounts');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function test_create_bank_account_returns_201(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/bank-accounts', [
                             'name'    => 'Poupança',
                             'bank'    => 'Itaú',
                             'type'    => 'savings',
                             'balance' => 250000,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Poupança')
                 ->assertJsonPath('data.bank', 'Itaú')
                 ->assertJsonPath('data.balance', 250000);
    }

    public function test_create_fails_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/bank-accounts', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors']);
    }

    public function test_balance_is_stored_in_cents(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/bank-accounts', [
                             'name'    => 'Conta',
                             'bank'    => 'BB',
                             'type'    => 'checking',
                             'balance' => 1550, // R$ 15,50
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.balance', 1550);
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function test_update_bank_account_returns_200(): void
    {
        $id = $this->createAccount();

        $response = $this->withHeaders($this->authHeaders())
                         ->putJson("/api/v1/bank-accounts/{$id}", [
                             'name'    => 'Atualizado',
                             'bank'    => 'Inter',
                             'type'    => 'checking',
                             'balance' => 50000,
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Atualizado')
                 ->assertJsonPath('data.bank', 'Inter');
    }

    public function test_update_nonexistent_account_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->putJson('/api/v1/bank-accounts/99999', [
                             'name' => 'X', 'bank' => 'Y', 'type' => 'checking', 'balance' => 0,
                         ]);

        $response->assertStatus(404);
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function test_delete_bank_account_returns_200(): void
    {
        $id = $this->createAccount();

        $response = $this->withHeaders($this->authHeaders())
                         ->deleteJson("/api/v1/bank-accounts/{$id}");

        $response->assertStatus(200);
    }

    public function test_deleted_account_disappears_from_list(): void
    {
        $id = $this->createAccount();

        $this->withHeaders($this->authHeaders())
             ->deleteJson("/api/v1/bank-accounts/{$id}");

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/bank-accounts');

        $response->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/bank-accounts')->assertStatus(401);
    }
}
