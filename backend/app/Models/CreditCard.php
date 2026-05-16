<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends TenantModel
{
    protected $table = 'credit_cards';

    protected $fillable = ['name', 'bank', 'limit_amount', 'closing_day', 'due_day'];

    public function statements(): HasMany
    {
        return $this->hasMany(CreditCardStatement::class, 'credit_card_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'credit_card_id');
    }
}
