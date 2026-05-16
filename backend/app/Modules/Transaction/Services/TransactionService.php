<?php

namespace App\Modules\Transaction\Services;

use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\Installment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Transaction::with(['category', 'bankAccount', 'creditCard', 'statement'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (!empty($filters['year'])) {
            $query->whereYear('date', $filters['year']);
        }

        if (!empty($filters['month'])) {
            $query->whereMonth('date', $filters['month']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['bank_account_id'])) {
            $query->where('bank_account_id', $filters['bank_account_id']);
        }

        if (!empty($filters['credit_card_id'])) {
            $query->where('credit_card_id', $filters['credit_card_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(50);
    }

    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $installmentCount = (int) ($data['installments'] ?? 1);
            unset($data['installments']);

            if (!empty($data['credit_card_id'])) {
                $card = CreditCard::findOrFail($data['credit_card_id']);
                $statement = $this->resolveStatement($card, Carbon::parse($data['date']));
                $data['credit_card_statement_id'] = $statement->id;
                unset($data['bank_account_id']);
            }

            $transaction = Transaction::create($data);

            if (!empty($data['credit_card_id']) && $installmentCount > 1) {
                $this->createInstallments($transaction, $installmentCount);
            }

            if (!empty($data['credit_card_statement_id'])) {
                $this->recalcStatementTotal($data['credit_card_statement_id']);
            }

            return $transaction->load(['category', 'bankAccount', 'creditCard', 'statement', 'installments']);
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $oldStatementId = $transaction->credit_card_statement_id;

            unset($data['installments']);

            if (!empty($data['credit_card_id'])) {
                $card = CreditCard::findOrFail($data['credit_card_id']);
                $statement = $this->resolveStatement($card, Carbon::parse($data['date'] ?? $transaction->date));
                $data['credit_card_statement_id'] = $statement->id;
                unset($data['bank_account_id']);
            }

            $transaction->update($data);

            if ($oldStatementId) {
                $this->recalcStatementTotal($oldStatementId);
            }

            if (!empty($data['credit_card_statement_id']) && $data['credit_card_statement_id'] !== $oldStatementId) {
                $this->recalcStatementTotal($data['credit_card_statement_id']);
            }

            return $transaction->fresh(['category', 'bankAccount', 'creditCard', 'statement']);
        });
    }

    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $statementId = $transaction->credit_card_statement_id;
            $transaction->delete();

            if ($statementId) {
                $this->recalcStatementTotal($statementId);
            }
        });
    }

    private function resolveStatement(CreditCard $card, Carbon $date): CreditCardStatement
    {
        // Purchases after closing_day go to next month's statement
        if ($date->day > $card->closing_day) {
            $statementDate = $date->copy()->addMonth()->startOfMonth();
        } else {
            $statementDate = $date->copy()->startOfMonth();
        }

        return CreditCardStatement::firstOrCreate(
            [
                'credit_card_id' => $card->id,
                'year'           => $statementDate->year,
                'month'          => $statementDate->month,
            ],
            ['total_amount' => 0, 'status' => 'open']
        );
    }

    private function createInstallments(Transaction $transaction, int $total): void
    {
        $card = $transaction->creditCard;
        $installmentAmount = (int) floor($transaction->amount / $total);
        $remainder = $transaction->amount - ($installmentAmount * $total);

        $baseDate = Carbon::parse($transaction->date);

        for ($i = 1; $i <= $total; $i++) {
            $installmentDate = $baseDate->copy()->addMonths($i - 1);
            $statement = $this->resolveStatement($card, $installmentDate);

            $amount = $installmentAmount + ($i === 1 ? $remainder : 0);

            Installment::create([
                'transaction_id'          => $transaction->id,
                'credit_card_statement_id' => $statement->id,
                'number'                  => $i,
                'total'                   => $total,
                'amount'                  => $amount,
                'date'                    => $installmentDate->toDateString(),
            ]);

            $this->recalcStatementTotal($statement->id);
        }
    }

    private function recalcStatementTotal(int $statementId): void
    {
        $fromTransactions = Transaction::where('credit_card_statement_id', $statementId)->sum('amount');
        $fromInstallments = Installment::where('credit_card_statement_id', $statementId)->sum('amount');

        $total = $fromTransactions + $fromInstallments;

        CreditCardStatement::where('id', $statementId)->update(['total_amount' => $total]);
    }
}
