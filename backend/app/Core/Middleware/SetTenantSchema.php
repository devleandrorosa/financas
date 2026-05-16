<?php

namespace App\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetTenantSchema
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->family) {
            $schema = 'family_' . str_replace('-', '_', $user->family->slug);
            DB::statement("SET search_path = \"{$schema}\", public");
        }

        return $next($request);
    }
}
