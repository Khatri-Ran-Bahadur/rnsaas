<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
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

        // Support SuperAdmin impersonation
        $impersonatedTenantId = $request->session()->get('impersonated_tenant_id');
        if ($impersonatedTenantId !== null && $user->hasRole('SuperAdmin')) {
            $impersonatedTenant = Tenant::query()->find($impersonatedTenantId);

            $isActive = $impersonatedTenant && (
                $impersonatedTenant->status instanceof TenantStatus
                    ? $impersonatedTenant->status === TenantStatus::Active
                    : $impersonatedTenant->status === 'active'
            );

            if ($isActive) {
                app(CurrentTenant::class)->set($impersonatedTenant);

                return $next($request);
            }

            // Clear invalid impersonation session
            $request->session()->forget([
                'impersonated_tenant_id',
                'impersonated_by_user_id',
                'current_tenant_id',
            ]);
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
