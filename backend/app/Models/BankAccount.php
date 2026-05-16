<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends TenantModel
{
    protected $table = 'bank_accounts';

    protected $fillable = ['name', 'bank', 'type', 'balance'];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'bank_account_id');
    }
}
