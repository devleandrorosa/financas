<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends TenantModel
{
    protected $table = 'transactions';

    protected $fillable = [
        'description',
        'amount',
        'type',
        'date',
        'category_id',
        'bank_account_id',
        'credit_card_id',
        'credit_card_statement_id',
        'status',
        'notes',
    ];

    protected $casts = ['date' => 'date'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CreditCardStatement::class, 'credit_card_statement_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'transaction_id');
    }
}
