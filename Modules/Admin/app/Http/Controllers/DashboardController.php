<?php

namespace Modules\Admin\Http\Controllers;

use App\Support\Tenancy\CurrentTenant;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(CurrentTenant $currentTenant): Response
    {
        $tenant = $currentTenant->get();

        return Inertia::render('Admin/Dashboard', [
            'tenant' => [
                'public_id' => $tenant->public_id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'industry' => $tenant->industry,
                'status' => $tenant->status->value,
                'country_code' => $tenant->country_code,
                'timezone' => $tenant->timezone,
                'locale' => $tenant->locale,
                'currency' => $tenant->currency,
            ],
        ]);
    }
}