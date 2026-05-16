<?php

namespace App\Modules\Budget\Services;

use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function listWithSpending(int $year, int $month): Collection
    {
        $budgets = Budget::with('category')
            ->where('year', $year)
            ->where('month', $month)
            ->get();

        $spending = Transaction::select('category_id', DB::raw('SUM(amount) as spent'))
            ->where('type', 'expense')
            ->where('status', '!=', 'cancelled')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->pluck('spent', 'category_id');

        return $budgets->map(function (Budget $budget) use ($spending) {
            $budget->spent = $spending->get($budget->category_id, 0);
            $budget->remaining = $budget->amount - $budget->spent;
            return $budget;
        });
    }

    public function upsert(array $data): Budget
    {
        return Budget::updateOrCreate(
            [
                'category_id' => $data['category_id'],
                'year'        => $data['year'],
                'month'       => $data['month'],
            ],
            ['amount' => $data['amount']]
        );
    }

    public function delete(Budget $budget): void
    {
        $budget->delete();
    }
}
