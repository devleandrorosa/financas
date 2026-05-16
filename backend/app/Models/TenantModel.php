<?php

namespace App\Models;

use App\Core\Services\TenantProvisioningService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class TenantModel extends Model
{
    // Laravel resolves route model bindings before route middleware runs, so
    // the search_path hasn't been set yet. We set it here so implicit binding works.
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $user = auth()->user();

        if ($user && $user->family) {
            $schema = TenantProvisioningService::schemaName($user->family->slug);
            DB::statement("SET search_path = \"{$schema}\", public");
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
