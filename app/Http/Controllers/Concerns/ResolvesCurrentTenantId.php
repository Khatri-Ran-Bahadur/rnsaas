<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;

trait ResolvesCurrentTenantId
{
    protected function getTenantId(Request $request): int
    {
        $currentTenant = app(CurrentTenant::class);

        if ($currentTenant->has()) {
            return $currentTenant->id();
        }

        $tenantId = $request->user()?->tenants()->first()?->id;

        if ($tenantId === null) {
            abort(403, 'No active organization is selected.');
        }

        return (int) $tenantId;
    }
}
