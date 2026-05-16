<?php

namespace App\Modules\Goal\Services;

use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;

class GoalService
{
    public function list(): Collection
    {
        return Goal::orderBy('name')->get();
    }

    public function create(array $data): Goal
    {
        return Goal::create($data);
    }

    public function update(Goal $goal, array $data): Goal
    {
        $goal->update($data);
        return $goal->fresh();
    }

    public function addProgress(Goal $goal, int $amount): Goal
    {
        $goal->increment('current_amount', $amount);
        return $goal->fresh();
    }

    public function delete(Goal $goal): void
    {
        $goal->delete();
    }
}
