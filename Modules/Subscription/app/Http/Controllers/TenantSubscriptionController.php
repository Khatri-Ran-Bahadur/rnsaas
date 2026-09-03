<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscription\Actions\CreateSubscriptionAction;
use Modules\Subscription\Http\Requests\StoreTenantSubscriptionRequest;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

class TenantSubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $subscriptions = TenantSubscription::query()
            ->with([
                'tenant:id,public_id,name',
                'plan:id,public_id,name,price,currency,billing_cycle',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render(
            'Subscription/Subscriptions/Index',
            [
                'subscriptions' => $subscriptions,
            ],
        );
    }

    public function create(): Response
    {
        $tenants = Tenant::query()
            ->where('status', 'active')
            ->select([
                'id',
                'public_id',
                'name',
            ])
            ->orderBy('name')
            ->get();

        $plans = Plan::query()
            ->active()
            ->select([
                'id',
                'public_id',
                'name',
                'price',
                'currency',
                'billing_cycle',
                'trial_days',
            ])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render(
            'Subscription/Subscriptions/Create',
            [
                'tenants' => $tenants,
                'plans' => $plans,
            ],
        );
    }

    public function store(
        StoreTenantSubscriptionRequest $request,
        CreateSubscriptionAction $action,
    ): RedirectResponse {
        $validated = $request->validated();

        $tenant = Tenant::query()
            ->findOrFail($validated['tenant_id']);

        $plan = Plan::query()
            ->active()
            ->findOrFail($validated['plan_id']);

        $subscription = $action->handle(
            $tenant,
            $plan,
            $validated['starts_at']
                ? \Illuminate\Support\Carbon::parse(
                    $validated['starts_at'],
                )
                : null,
        );

        return redirect()
            ->route('admin.subscriptions.index')
            ->with(
                'success',
                "Subscription for '{$tenant->name}' was created successfully.",
            );
    }

    public function show(
        TenantSubscription $subscription,
    ): Response {
        $subscription->load([
            'tenant:id,public_id,name',
            'plan:id,public_id,name,price,currency,billing_cycle,trial_days',
        ]);

        return Inertia::render(
            'Subscription/Subscriptions/Show',
            [
                'subscription' => $subscription,
            ],
        );
    }
}