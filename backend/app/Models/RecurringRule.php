<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringRule extends TenantModel
{
    protected $table = 'recurring_rules';

    protected $fillable = [
        'description',
        'amount',
        'type',
        'frequency',
        'start_date',
        'end_date',
        'category_id',
        'bank_account_id',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'active'     => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
