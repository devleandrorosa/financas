<?php

namespace App\Models;

class Goal extends TenantModel
{
    protected $table = 'goals';

    protected $fillable = [
        'name',
        'type',
        'target_amount',
        'current_amount',
        'deadline',
        'notes',
    ];

    protected $casts = ['deadline' => 'date'];
}
