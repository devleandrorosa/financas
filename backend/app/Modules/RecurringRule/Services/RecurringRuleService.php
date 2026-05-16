<?php

namespace App\Modules\RecurringRule\Services;

use App\Models\RecurringRule;
use Illuminate\Database\Eloquent\Collection;

class RecurringRuleService
{
    public function list(): Collection
    {
        return RecurringRule::with(['category', 'bankAccount'])->orderBy('description')->get();
    }

    public function create(array $data): RecurringRule
    {
        return RecurringRule::create($data);
    }

    public function update(RecurringRule $rule, array $data): RecurringRule
    {
        $rule->update($data);
        return $rule->fresh(['category', 'bankAccount']);
    }

    public function toggle(RecurringRule $rule): RecurringRule
    {
        $rule->update(['active' => !$rule->active]);
        return $rule->fresh();
    }

    public function delete(RecurringRule $rule): void
    {
        $rule->delete();
    }
}
