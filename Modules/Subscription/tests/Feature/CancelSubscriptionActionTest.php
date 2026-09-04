<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Audit\Models\AuditLog;
use Modules\Subscription\Actions\CancelSubscriptionAction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\TenantSubscriptionCanceled;
use Modules\Subscription\Exceptions\SubscriptionCannotBeCanceledException;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('cancels an active subscription at the end of the current period', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
        'trial_days' => 0,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'canceled_at' => null,
        'ends_at' => null,
    ]);

    $subscription = app(CancelSubscriptionAction::class)
        ->handle($subscription);

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Active);

    expect($subscription->canceled_at)
        ->not->toBeNull();

    expect($subscription->ends_at)
        ->not->toBeNull();

    expect($subscription->ends_at->equalTo(
        $subscription->current_period_ends_at,
    ))->toBeTrue();
});

it('cancels a trialing subscription', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
        'trial_days' => 14,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Trialing,
        'canceled_at' => null,
        'ends_at' => null,
    ]);

    $subscription = app(CancelSubscriptionAction::class)
        ->handle($subscription);

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Trialing);

    expect($subscription->canceled_at)
        ->not->toBeNull();

    expect($subscription->ends_at)
        ->not->toBeNull();
});

it('cannot cancel an already canceled subscription', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'canceled_at' => now()->subDay(),
        'ends_at' => now()->addDays(10),
    ]);

    expect(fn () => app(CancelSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeCanceledException::class);
});

it('cannot cancel an expired subscription', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Expired,
    ]);

    expect(fn () => app(CancelSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeCanceledException::class);
});

it('cannot cancel a past due subscription', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::PastDue,
    ]);

    expect(fn () => app(CancelSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeCanceledException::class);
});

it('dispatches the subscription canceled event', function () {
    Event::fake();

    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'canceled_at' => null,
        'ends_at' => null,
    ]);

    $subscription = app(CancelSubscriptionAction::class)
        ->handle($subscription);

    Event::assertDispatched(
        TenantSubscriptionCanceled::class,
        function (
            TenantSubscriptionCanceled $event
        ) use ($subscription): bool {
            return $event->subscription->is($subscription);
        },
    );
});

it('records an audit log when a subscription is canceled', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'canceled_at' => null,
        'ends_at' => null,
    ]);

    $subscription = app(CancelSubscriptionAction::class)
        ->handle($subscription);

    $auditLog = AuditLog::query()
        ->where('auditable_type', $subscription->getMorphClass())
        ->where('auditable_id', $subscription->id)
        ->where('event', 'subscription.canceled')
        ->first();

    expect($auditLog)
        ->not->toBeNull();

    expect($auditLog->tenant_id)
        ->toBe($subscription->tenant_id);

    expect($auditLog->new_values)
        ->toMatchArray([
            'canceled_at' => $subscription->canceled_at?->toISOString(),
            'ends_at' => $subscription->ends_at?->toISOString(),
        ]);

    expect($auditLog->metadata)
        ->toMatchArray([
            'module' => 'subscription',
            'source' => 'subscription.cancel',
            'subscription_public_id' => $subscription->public_id,
        ]);
});
