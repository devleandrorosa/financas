<?php

namespace App\Models;

class Investment extends TenantModel
{
    protected $table = 'investments';

    protected $fillable = [
        'name',
        'type',
        'institution',
        'amount',
        'purchased_at',
        'maturity_at',
        'notes',
    ];

    protected $casts = [
        'purchased_at' => 'date',
        'maturity_at'  => 'date',
    ];
}
