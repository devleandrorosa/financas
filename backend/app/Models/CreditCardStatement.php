<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCardStatement extends TenantModel
{
    protected $table = 'credit_card_statements';

    protected $fillable = ['credit_card_id', 'year', 'month', 'total_amount', 'status', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime'];

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'credit_card_statement_id');
    }
}
