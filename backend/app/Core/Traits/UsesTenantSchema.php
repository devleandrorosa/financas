<?php

namespace App\Core\Traits;

use Illuminate\Support\Facades\DB;

trait UsesTenantSchema
{
    public static function setSchema(string $schema): void
    {
        DB::statement("SET search_path = {$schema}, public");
    }

    public static function resetSchema(): void
    {
        DB::statement('SET search_path = public');
    }
}
