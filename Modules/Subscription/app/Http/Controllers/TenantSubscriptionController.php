<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscription\Actions\CancelSubscriptionAction;
use Modules\Subscription\Actions\CreateSubscriptionAction;
use Modules\Subscription\Actions\ReactivateSubscriptionAction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Http\Requests\StoreTenantSubscriptionRequest;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

class TenantSubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->integer('per_page') ?: 10;
        $perPage = min(max($perPage, 5), 100);

        $subscriptions = TenantSubscription::query()
            ->with([
                'tenant:id,public_id,name',
                'plan:id,public_id,name,price,currency,billing_cycle',
            ])
            ->search($request->string('search')->value())
            ->withStatus($request->string('status')->value())
            ->forPlan($request->integer('plan') ?: null)
            ->withBillingCycle(
                $request->string('billing_cycle')->value(),
            )
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $plans = Plan::query()
            ->select([
                'id',
                'name',
                'billing_cycle',
            ])
            ->orderBy('name')
            ->get();

        $summary = [
            'total' => TenantSubscription::query()->count(),
            'active' => TenantSubscription::query()->where('status', SubscriptionStatus::Active)->count(),
            'trialing' => TenantSubscription::query()->where('status', SubscriptionStatus::Trialing)->count(),
            'pending' => TenantSubscription::query()->where('status', SubscriptionStatus::Pending)->count(),
            'canceled_or_expired' => TenantSubscription::query()->whereIn('status', [
                SubscriptionStatus::Canceled,
                SubscriptionStatus::Expired,
            ])->count(),
        ];

        return Inertia::render(
            'Subscription/Subscriptions/Index',
            [
                'subscriptions' => $subscriptions,
                'plans' => $plans,
                'summary' => $summary,
                'filters' => [
                    'search' => $request->string('search')->value(),
                    'status' => $request->string('status')->value(),
                    'plan' => $request->integer('plan') ?: null,
                    'billing_cycle' => $request
                        ->string('billing_cycle')
                        ->value(),
                    'per_page' => $perPage,
                ],
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

        $action->handle(
            tenant: $tenant,
            plan: $plan,
            startsAt: filled($validated['starts_at'])
                ? Carbon::parse($validated['starts_at'])
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
            'payments' => fn ($query) => $query
                ->latest('created_at')
                ->select([
                    'id',
                    'public_id',
                    'tenant_id',
                    'subscription_id',
                    'provider',
                    'amount',
                    'currency',
                    'status',
                    'type',
                    'paid_at',
                    'created_at',
                ]),
        ]);

        return Inertia::render(
            'Subscription/Subscriptions/Show',
            [
                'subscription' => $subscription,
            ],
        );
    }

    public function cancel(
        TenantSubscription $subscription,
        CancelSubscriptionAction $action,
    ): RedirectResponse {
        $action->handle($subscription);

        return back()->with(
            'success',
            'Subscription was successfully canceled.',
        );
    }

    public function reactivate(
        TenantSubscription $subscription,
        ReactivateSubscriptionAction $action,
    ): RedirectResponse {
        $action->handle($subscription);

        return back()->with(
            'success',
            'Subscription was successfully reactivated.',
        );
    }
}
