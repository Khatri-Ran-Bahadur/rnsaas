<?php

namespace Modules\Admin\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;
use Modules\Admin\Actions\SwitchCurrentTenantAction;
use Modules\Tenancy\Models\Tenant;

final class TenantSwitcherController
{
    public function __invoke(
        Request $request,
        Tenant $tenant,
        SwitchCurrentTenantAction $action,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $tenant = $action->handle($user, $tenant);
        } catch (LogicException $exception) {
            abort(403, $exception->getMessage());
        }

        $request->session()->put(
            'current_tenant_id',
            $tenant->id,
        );

        return to_route('admin.dashboard');
    }
}
