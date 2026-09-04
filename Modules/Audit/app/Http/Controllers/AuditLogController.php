<?php

namespace Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Audit\Models\AuditLog;
use Modules\Tenancy\Models\Tenant;

class AuditLogController extends Controller
{
    /**
     * Display a paginated listing of platform audit logs.
     */
    public function index(Request $request): Response
    {
        $perPage = $request->integer('per_page') ?: 20;
        $perPage = min(max($perPage, 5), 100);

        $query = AuditLog::query()
            ->with([
                'tenant:id,name,slug',
                'actor',
                'auditable',
            ]);

        // Search query across event, request_id, tenant name/slug, and actor name/email
        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search): void {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('request_id', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tq) use ($search): void {
                        $tq->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    })
                    ->orWhereHasMorph('actor', ['App\Models\User'], function ($aq) use ($search): void {
                        $aq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Event filter
        if ($event = $request->string('event')->trim()->value()) {
            $query->where('event', $event);
        }

        // Tenant filter
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        // Date From filter
        if ($dateFrom = $request->string('date_from')->trim()->value()) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        // Date To filter
        if ($dateTo = $request->string('date_to')->trim()->value()) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $auditLogs = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // Retrieve distinct event names recorded in the system, supplemented by platform defaults
        $dbEvents = AuditLog::query()
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->all();

        $defaultEvents = [
            'membership.invited',
            'membership.revoked',
            'membership.suspended',
            'payment.received',
            'subscription.activated',
            'subscription.canceled',
            'subscription.created',
            'subscription.expired',
            'tenant.created',
            'tenant.updated',
        ];

        $events = array_values(array_unique(array_merge($dbEvents, $defaultEvents)));
        sort($events);

        // Retrieve actual tenant records for the dropdown filter
        $tenants = Tenant::query()
            ->select(['id', 'name', 'slug'])
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ]);

        return Inertia::render('Audit/AuditLogs/Index', [
            'auditLogs' => $auditLogs,
            'filters' => [
                'search' => $request->string('search')->value(),
                'event' => $request->string('event')->value(),
                'tenant_id' => $request->input('tenant_id') ? (int) $request->input('tenant_id') : '',
                'date_from' => $request->string('date_from')->value(),
                'date_to' => $request->string('date_to')->value(),
                'per_page' => $perPage,
            ],
            'events' => $events,
            'tenants' => $tenants,
        ]);
    }
}
