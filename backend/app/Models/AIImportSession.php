<?php

namespace App\Models;

class AIImportSession extends TenantModel
{
    protected $table = 'ai_import_sessions';

    protected $fillable = ['user_id', 'file_path', 'original_name', 'status', 'error_message'];

    public function items()
    {
        return $this->hasMany(AIImportItem::class, 'session_id');
    }
}
