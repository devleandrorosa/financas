<?php

namespace Tests\Unit\Services;

use App\Models\RecurringRule;
use App\Modules\Projection\Services\ProjectionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class ProjectionServiceTest extends TestCase
{
    private ProjectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectionService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRule(array $attrs): RecurringRule
    {
        $rule = Mockery::mock(RecurringRule::class)->makePartial();
        foreach ($attrs as $key => $value) {
            $rule->{$key} = $value;
        }
        return $rule;
    }

    public function test_project_returns_correct_number_of_months(): void
    {
        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection());

        $result = $this->service->project(6);

        $this->assertCount(6, $result);
    }

    public function test_project_result_has_required_keys(): void
    {
        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection());

        $result = $this->service->project(1);

        $this->assertArrayHasKey('year', $result[0]);
        $this->assertArrayHasKey('month', $result[0]);
        $this->assertArrayHasKey('label', $result[0]);
        $this->assertArrayHasKey('income', $result[0]);
        $this->assertArrayHasKey('expense', $result[0]);
        $this->assertArrayHasKey('balance', $result[0]);
        $this->assertArrayHasKey('cumulative', $result[0]);
    }

    public function test_monthly_rule_appears_every_month(): void
    {
        $rule = $this->makeRule([
            'type'       => 'income',
            'frequency'  => 'monthly',
            'amount'     => 500000,
            'start_date' => Carbon::now()->subYear()->toDateString(),
            'end_date'   => null,
            'active'     => true,
        ]);

        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection([$rule]));

        $result = $this->service->project(3);

        foreach ($result as $month) {
            $this->assertSame(500000, $month['income']);
            $this->assertSame(0, $month['expense']);
        }
    }

    public function test_yearly_rule_appears_only_in_its_month(): void
    {
        $now = Carbon::now()->startOfMonth();
        // Regra anual cujo mês coincide com o mês atual
        $rule = $this->makeRule([
            'type'       => 'expense',
            'frequency'  => 'yearly',
            'amount'     => 120000,
            'start_date' => $now->copy()->subYear()->toDateString(),
            'end_date'   => null,
        ]);

        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection([$rule]));

        $result = $this->service->project(12);

        // Deve aparecer exatamente uma vez (mês atual, e talvez mês atual+12 se projeção fosse maior)
        $monthsWithExpense = collect($result)->where('expense', '>', 0);
        $this->assertCount(1, $monthsWithExpense);
        $this->assertSame(120000, $monthsWithExpense->first()['expense']);
    }

    public function test_income_and_expense_balance_is_calculated_correctly(): void
    {
        $income = $this->makeRule([
            'type'       => 'income',
            'frequency'  => 'monthly',
            'amount'     => 300000,
            'start_date' => Carbon::now()->subYear()->toDateString(),
            'end_date'   => null,
        ]);

        $expense = $this->makeRule([
            'type'       => 'expense',
            'frequency'  => 'monthly',
            'amount'     => 120000,
            'start_date' => Carbon::now()->subYear()->toDateString(),
            'end_date'   => null,
        ]);

        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection([$income, $expense]));

        $result = $this->service->project(1);

        $this->assertSame(300000, $result[0]['income']);
        $this->assertSame(120000, $result[0]['expense']);
        $this->assertSame(180000, $result[0]['balance']);
    }

    public function test_cumulative_accumulates_across_months(): void
    {
        $rule = $this->makeRule([
            'type'       => 'income',
            'frequency'  => 'monthly',
            'amount'     => 100000,
            'start_date' => Carbon::now()->subYear()->toDateString(),
            'end_date'   => null,
        ]);

        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection([$rule]));

        $result = $this->service->project(3);

        $this->assertSame(100000, $result[0]['cumulative']);
        $this->assertSame(200000, $result[1]['cumulative']);
        $this->assertSame(300000, $result[2]['cumulative']);
    }

    public function test_rule_not_started_yet_is_excluded(): void
    {
        $rule = $this->makeRule([
            'type'       => 'income',
            'frequency'  => 'monthly',
            'amount'     => 500000,
            'start_date' => Carbon::now()->addYear()->toDateString(), // começa no futuro
            'end_date'   => null,
        ]);

        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection([$rule]));

        $result = $this->service->project(3);

        foreach ($result as $month) {
            $this->assertSame(0, $month['income']);
        }
    }

    public function test_expired_rule_is_excluded(): void
    {
        $rule = $this->makeRule([
            'type'       => 'expense',
            'frequency'  => 'monthly',
            'amount'     => 200000,
            'start_date' => Carbon::now()->subYears(2)->toDateString(),
            'end_date'   => Carbon::now()->subMonth()->toDateString(), // expirou
        ]);

        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection([$rule]));

        $result = $this->service->project(3);

        foreach ($result as $month) {
            $this->assertSame(0, $month['expense']);
        }
    }

    public function test_daily_rule_multiplies_by_days_in_month(): void
    {
        $now = Carbon::now()->startOfMonth();
        $daysInMonth = $now->daysInMonth;

        $rule = $this->makeRule([
            'type'       => 'expense',
            'frequency'  => 'daily',
            'amount'     => 1000,
            'start_date' => Carbon::now()->subYear()->toDateString(),
            'end_date'   => null,
        ]);

        RecurringRule::shouldReceive('where->get')
            ->andReturn(new Collection([$rule]));

        $result = $this->service->project(1);

        $this->assertSame(1000 * $daysInMonth, $result[0]['expense']);
    }
}
