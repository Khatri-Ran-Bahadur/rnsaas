<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Actions\GetOrganizationDashboardAction;
use Modules\Admin\Actions\GetUserTenantsAction;

class DashboardController
{
    public function __invoke(
        Request $request,
        GetOrganizationDashboardAction $dashboardAction,
        GetUserTenantsAction $tenantsAction,
    ): Response {
        $user = $request->user();

        return Inertia::render(
            'Admin/Dashboard',
            [
                ...$dashboardAction->handle()->toArray(),
                'organizations' => $tenantsAction
                    ->handle($user)
                    ->map(fn ($tenant) => [
                        'id' => $tenant->id,
                        'public_id' => $tenant->public_id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                    ])
                    ->values()
                    ->all(),
            ],
        );
    }
}
