<?php

namespace Modules\SuperAdmin\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Audit\Models\AuditLog;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;

class DashboardController
{
    public function __invoke(): Response
    {
        $stats = [
            'totalTenants' => Tenant::query()->count(),
            'activeTenants' => Tenant::query()->where('status', TenantStatus::Active->value)->count(),
            'pendingTenants' => Tenant::query()->where('status', TenantStatus::Pending->value)->count(),
            'suspendedTenants' => Tenant::query()->where('status', TenantStatus::Suspended->value)->count(),
            'totalUsers' => User::query()->count(),
        ];

        $recentTenants = Tenant::query()
            ->latest()
            ->take(5)
            ->get();

        $recentAudits = AuditLog::query()
            ->with(['tenant', 'actor'])
            ->latest('id')
            ->take(6)
            ->get();

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'recentAudits' => $recentAudits,
        ]);
    }
}
