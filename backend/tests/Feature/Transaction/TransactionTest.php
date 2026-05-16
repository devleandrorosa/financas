<?php

namespace Tests\Feature\Transaction;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;

class TransactionTest extends TenantTestCase
{
    private int $categoryId;
    private int $bankAccountId;
    private int $creditCardId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryId = $this->tenantInsert('categories', [
            'name' => 'Salário', 'type' => 'income', 'parent_id' => null,
        ]);

        $this->bankAccountId = $this->tenantInsert('bank_accounts', [
            'name' => 'Nubank', 'bank' => 'Nubank', 'type' => 'checking', 'balance' => 0,
        ]);

        $this->creditCardId = $this->tenantInsert('credit_cards', [
            'name'         => 'Visa',
            'bank'         => 'Nubank',
            'limit_amount' => 500000,
            'closing_day'  => 10,
            'due_day'      => 17,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function test_create_income_transaction_returns_201(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/transactions', [
                             'description'     => 'Salário maio',
                             'amount'          => 500000,
                             'type'            => 'income',
                             'date'            => '2026-05-05',
                             'status'          => 'completed',
                             'category_id'     => $this->categoryId,
                             'bank_account_id' => $this->bankAccountId,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.description', 'Salário maio')
                 ->assertJsonPath('data.amount', 500000)
                 ->assertJsonPath('data.type', 'income');
    }

    public function test_create_expense_transaction_returns_201(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/transactions', [
                             'description'     => 'Mercado',
                             'amount'          => 15000,
                             'type'            => 'expense',
                             'date'            => '2026-05-10',
                             'status'          => 'completed',
                             'category_id'     => $this->categoryId,
                             'bank_account_id' => $this->bankAccountId,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.amount', 15000);
    }

    public function test_create_fails_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/transactions', []);

        $response->assertStatus(422)->assertJsonStructure(['errors']);
    }

    // ── Parcelamento ──────────────────────────────────────────────────────

    public function test_create_installments_generates_correct_count(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/transactions', [
                             'description'    => 'Notebook 3x',
                             'amount'         => 300000,
                             'type'           => 'expense',
                             'date'           => '2026-05-05',
                             'status'         => 'completed',
                             'category_id'    => $this->categoryId,
                             'credit_card_id' => $this->creditCardId,
                             'installments'   => 3,
                         ]);

        $response->assertStatus(201);

        $transactionId = $response->json('data.id');
        $count = DB::table('installments')->where('transaction_id', $transactionId)->count();
        $this->assertSame(3, $count);
    }

    public function test_installments_total_equals_transaction_amount(): void
    {
        $amount = 100000;

        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/transactions', [
                             'description'    => 'Parcela teste',
                             'amount'         => $amount,
                             'type'           => 'expense',
                             'date'           => '2026-05-05',
                             'status'         => 'completed',
                             'category_id'    => $this->categoryId,
                             'credit_card_id' => $this->creditCardId,
                             'installments'   => 3,
                         ]);

        $transactionId = $response->json('data.id');
        $total = DB::table('installments')->where('transaction_id', $transactionId)->sum('amount');
        $this->assertSame($amount, (int) $total);
    }

    public function test_installment_remainder_goes_to_first_installment(): void
    {
        // 100001 / 3 = 33333,67 → parcela 1: 33335, parcelas 2 e 3: 33333
        $amount = 100001;

        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/transactions', [
                             'description'    => 'Resto na primeira',
                             'amount'         => $amount,
                             'type'           => 'expense',
                             'date'           => '2026-05-05',
                             'status'         => 'completed',
                             'category_id'    => $this->categoryId,
                             'credit_card_id' => $this->creditCardId,
                             'installments'   => 3,
                         ]);

        $transactionId = $response->json('data.id');
        $first = DB::table('installments')
                   ->where('transaction_id', $transactionId)
                   ->where('number', 1)
                   ->value('amount');

        $others = DB::table('installments')
                    ->where('transaction_id', $transactionId)
                    ->where('number', '>', 1)
                    ->pluck('amount');

        $base = (int) floor($amount / 3);
        $this->assertSame($base + ($amount - $base * 3), (int) $first);
        foreach ($others as $o) {
            $this->assertSame($base, (int) $o);
        }
    }

    // ── Resolução de statement (closing_day) ──────────────────────────────

    public function test_purchase_after_closing_day_goes_to_next_month_statement(): void
    {
        // closing_day = 10; compra dia 15 → statement de junho
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/transactions', [
                             'description'    => 'Compra após fechamento',
                             'amount'         => 5000,
                             'type'           => 'expense',
                             'date'           => '2026-05-15',
                             'status'         => 'completed',
                             'category_id'    => $this->categoryId,
                             'credit_card_id' => $this->creditCardId,
                         ]);

        $response->assertStatus(201);
        $statementId = $response->json('data.credit_card_statement_id');
        $statement = DB::table('credit_card_statements')->find($statementId);

        $this->assertSame(2026, (int) $statement->year);
        $this->assertSame(6, (int) $statement->month);
    }

    public function test_purchase_before_closing_day_goes_to_current_month_statement(): void
    {
        // closing_day = 10; compra dia 5 → statement de maio
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/transactions', [
                             'description'    => 'Compra antes do fechamento',
                             'amount'         => 5000,
                             'type'           => 'expense',
                             'date'           => '2026-05-05',
                             'status'         => 'completed',
                             'category_id'    => $this->categoryId,
                             'credit_card_id' => $this->creditCardId,
                         ]);

        $response->assertStatus(201);
        $statementId = $response->json('data.credit_card_statement_id');
        $statement = DB::table('credit_card_statements')->find($statementId);

        $this->assertSame(2026, (int) $statement->year);
        $this->assertSame(5, (int) $statement->month);
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function test_list_transactions_returns_200(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/transactions');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_list_filters_by_type(): void
    {
        // Cria uma income e uma expense
        foreach (['income', 'expense'] as $type) {
            $this->withHeaders($this->authHeaders())
                 ->postJson('/api/v1/transactions', [
                     'description'     => "Trans {$type}",
                     'amount'          => 10000,
                     'type'            => $type,
                     'date'            => '2026-05-10',
                     'status'          => 'completed',
                     'category_id'     => $this->categoryId,
                     'bank_account_id' => $this->bankAccountId,
                 ]);
        }

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/transactions?type=income');

        $response->assertStatus(200);
        foreach ($response->json('data.data') as $item) {
            $this->assertSame('income', $item['type']);
        }
    }

    public function test_list_filters_by_year_and_month(): void
    {
        $this->withHeaders($this->authHeaders())
             ->postJson('/api/v1/transactions', [
                 'description'     => 'Jan 2026',
                 'amount'          => 10000,
                 'type'            => 'income',
                 'date'            => '2026-01-15',
                 'status'          => 'completed',
                 'category_id'     => $this->categoryId,
                 'bank_account_id' => $this->bankAccountId,
             ]);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/transactions?year=2026&month=1');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function test_delete_transaction_returns_200(): void
    {
        $create = $this->withHeaders($this->authHeaders())
                       ->postJson('/api/v1/transactions', [
                           'description'     => 'Para deletar',
                           'amount'          => 5000,
                           'type'            => 'expense',
                           'date'            => '2026-05-10',
                           'status'          => 'completed',
                           'category_id'     => $this->categoryId,
                           'bank_account_id' => $this->bankAccountId,
                       ]);

        $id = $create->json('data.id');

        $this->withHeaders($this->authHeaders())
             ->deleteJson("/api/v1/transactions/{$id}")
             ->assertStatus(200);
    }
}
