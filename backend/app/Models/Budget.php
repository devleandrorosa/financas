<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends TenantModel
{
    protected $table = 'budgets';

    protected $fillable = ['category_id', 'year', 'month', 'amount'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
