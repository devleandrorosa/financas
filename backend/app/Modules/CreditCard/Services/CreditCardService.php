<?php

namespace App\Modules\CreditCard\Services;

use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use Illuminate\Database\Eloquent\Collection;

class CreditCardService
{
    public function list(): Collection
    {
        return CreditCard::orderBy('name')->get();
    }

    public function create(array $data): CreditCard
    {
        return CreditCard::create($data);
    }

    public function update(CreditCard $card, array $data): CreditCard
    {
        $card->update($data);
        return $card->fresh();
    }

    public function delete(CreditCard $card): void
    {
        $card->delete();
    }

    public function statements(CreditCard $card): Collection
    {
        return $card->statements()->orderByDesc('year')->orderByDesc('month')->get();
    }

    public function findOrCreateStatement(CreditCard $card, int $year, int $month): CreditCardStatement
    {
        return CreditCardStatement::firstOrCreate(
            ['credit_card_id' => $card->id, 'year' => $year, 'month' => $month],
            ['total_amount' => 0, 'status' => 'open']
        );
    }

    public function payStatement(CreditCardStatement $statement): CreditCardStatement
    {
        $statement->update(['status' => 'paid', 'paid_at' => now()]);
        return $statement->fresh();
    }
}
