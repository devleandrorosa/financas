<?php

namespace App\Models;

class AIImportItem extends TenantModel
{
    protected $table = 'ai_import_items';

    protected $fillable = [
        'session_id', 'description', 'amount', 'type', 'date',
        'category_id', 'bank_account_id', 'credit_card_id', 'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
