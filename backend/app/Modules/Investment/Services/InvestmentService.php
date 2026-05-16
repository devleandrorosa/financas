<?php

namespace App\Modules\Investment\Services;

use App\Models\Investment;
use Illuminate\Database\Eloquent\Collection;

class InvestmentService
{
    public function list(): Collection
    {
        return Investment::orderBy('name')->get();
    }

    public function create(array $data): Investment
    {
        return Investment::create($data);
    }

    public function update(Investment $investment, array $data): Investment
    {
        $investment->update($data);
        return $investment->fresh();
    }

    public function delete(Investment $investment): void
    {
        $investment->delete();
    }
}
