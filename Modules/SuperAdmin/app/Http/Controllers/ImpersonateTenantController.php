<?php

namespace Modules\SuperAdmin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;

final class ImpersonateTenantController
{
    /**
     * Start impersonating an organization as SuperAdmin.
     */
    public function impersonate(Request $request, Tenant $tenant): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('SuperAdmin')) {
            abort(403, 'Only SuperAdmin can impersonate an organization.');
        }

        $isActive = $tenant->status instanceof TenantStatus
            ? $tenant->status === TenantStatus::Active
            : $tenant->status === 'active';

        if (! $isActive) {
            abort(403, 'Cannot impersonate an inactive organization.');
        }

        // Record audit event
        app(AuditLogger::class)->record(
            event: 'superadmin.tenant.impersonate',
            tenantId: $tenant->id,
            actor: $user,
            auditable: $tenant,
            metadata: [
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug,
            ],
        );

        // Put impersonation markers in session
        $request->session()->put('impersonated_tenant_id', $tenant->id);
        $request->session()->put('impersonated_by_user_id', $user->id);
        $request->session()->put('current_tenant_id', $tenant->id);

        return redirect()->route('admin.dashboard')->with(
            'success',
            "Now viewing {$tenant->name} in Admin Mode.",
        );
    }

    /**
     * Exit organization impersonation mode and return to SuperAdmin.
     */
    public function exit(Request $request): RedirectResponse
    {
        $tenantId = $request->session()->get('impersonated_tenant_id');

        if ($tenantId !== null) {
            $tenant = Tenant::query()->find($tenantId);

            app(AuditLogger::class)->record(
                event: 'superadmin.tenant.impersonate_exit',
                tenantId: (int) $tenantId,
                actor: $request->user(),
                auditable: $tenant,
            );

            $request->session()->forget([
                'impersonated_tenant_id',
                'impersonated_by_user_id',
                'current_tenant_id',
            ]);

            if ($tenant !== null) {
                return redirect()->route('superadmin.tenancy.show', $tenant)->with(
                    'success',
                    'Exited Admin Mode and returned to SuperAdmin.',
                );
            }
        }

        return redirect()->route('superadmin.dashboard')->with(
            'success',
            'Exited Admin Mode.',
        );
    }
}
