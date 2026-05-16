<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends TenantModel
{
    protected $table = 'installments';

    protected $fillable = [
        'transaction_id',
        'credit_card_statement_id',
        'number',
        'total',
        'amount',
        'date',
    ];

    protected $casts = ['date' => 'date'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CreditCardStatement::class, 'credit_card_statement_id');
    }
}
