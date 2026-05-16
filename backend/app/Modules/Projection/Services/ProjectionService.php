<?php

namespace App\Modules\Projection\Services;

use App\Models\RecurringRule;
use Carbon\Carbon;

class ProjectionService
{
    public function project(int $months): array
    {
        $rules = RecurringRule::where('active', true)->get();

        $result = [];
        $cumulative = 0;
        $now = Carbon::now()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $target = $now->copy()->addMonths($i);
            [$income, $expense] = $this->calcMonth($rules, $target);

            $balance    = $income - $expense;
            $cumulative += $balance;

            $result[] = [
                'year'       => $target->year,
                'month'      => $target->month,
                'label'      => ucfirst($target->translatedFormat('M/Y')),
                'income'     => $income,
                'expense'    => $expense,
                'balance'    => $balance,
                'cumulative' => $cumulative,
            ];
        }

        return $result;
    }

    private function calcMonth($rules, Carbon $target): array
    {
        $income  = 0;
        $expense = 0;

        $monthStart = $target->copy()->startOfMonth();
        $monthEnd   = $target->copy()->endOfMonth();

        foreach ($rules as $rule) {
            $startDate = $rule->start_date instanceof Carbon
                ? $rule->start_date
                : Carbon::parse($rule->start_date);

            // Rule hasn't started yet this month
            if ($startDate->gt($monthEnd)) {
                continue;
            }

            // Rule already ended before this month
            if ($rule->end_date) {
                $endDate = $rule->end_date instanceof Carbon
                    ? $rule->end_date
                    : Carbon::parse($rule->end_date);

                if ($endDate->lt($monthStart)) {
                    continue;
                }
            }

            $amount = $this->amountForMonth($rule, $target, $monthStart, $monthEnd, $startDate);

            if ($amount <= 0) {
                continue;
            }

            if ($rule->type === 'income') {
                $income += $amount;
            } else {
                $expense += $amount;
            }
        }

        return [$income, $expense];
    }

    private function amountForMonth(RecurringRule $rule, Carbon $target, Carbon $monthStart, Carbon $monthEnd, Carbon $startDate): int
    {
        return match ($rule->frequency) {
            'monthly'  => $rule->amount,

            'yearly'   => $startDate->month === $target->month ? $rule->amount : 0,

            'weekly'   => $rule->amount * $this->countWeekdays($monthStart, $monthEnd, $startDate),

            'daily'    => $rule->amount * $monthEnd->day,

            default    => $rule->amount,
        };
    }

    // Count how many times a weekly recurrence fires in the month
    // anchored to the day-of-week of the start_date
    private function countWeekdays(Carbon $monthStart, Carbon $monthEnd, Carbon $startDate): int
    {
        $dayOfWeek = $startDate->dayOfWeek;
        $count = 0;
        $cursor = $monthStart->copy()->next($dayOfWeek);

        if ($monthStart->dayOfWeek === $dayOfWeek) {
            $cursor = $monthStart->copy();
        }

        while ($cursor->lte($monthEnd)) {
            $count++;
            $cursor->addWeek();
        }

        return $count;
    }
}
