<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentTenant
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $tenantId = $request->session()->get('current_tenant_id');

        if ($tenantId !== null) {
            $tenant = $user->tenants()
                ->whereKey($tenantId)
                ->wherePivot('status', 'active')
                ->first();

            if ($tenant !== null) {
                app(CurrentTenant::class)->set($tenant);

                return $next($request);
            }

            $request->session()->forget('current_tenant_id');
        }

        $tenant = $user->tenants()
            ->wherePivot('status', 'active')
            ->orderBy('tenants.id')
            ->first();

        if ($tenant === null) {
            abort(403, 'You do not belong to an active organization.');
        }

        $request->session()->put('current_tenant_id', $tenant->id);

        app(CurrentTenant::class)->set($tenant);

        return $next($request);
    }
}