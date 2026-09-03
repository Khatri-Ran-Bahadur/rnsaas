<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscription\Actions\RenewSubscriptionAction;
use Modules\Subscription\Enums\BillingCycle;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Exceptions\SubscriptionCannotBeRenewedException;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;

uses(RefreshDatabase::class);

it('renews a monthly subscription after the current period ends', function () {
    $periodStartsAt = now()->subMonth();
    $periodEndsAt = now()->subDay();

    $plan = Plan::factory()->create([
        'billing_cycle' => BillingCycle::Monthly,
        'is_active' => true,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'current_period_starts_at' => $periodStartsAt,
        'current_period_ends_at' => $periodEndsAt,
        'canceled_at' => null,
        'ends_at' => null,
    ]);

    $subscription = app(RenewSubscriptionAction::class)
        ->handle($subscription);

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Active);

    expect($subscription->current_period_starts_at->isSameSecond($periodEndsAt))
        ->toBeTrue();

    expect($subscription->current_period_ends_at->isSameSecond($periodEndsAt->copy()->addMonth()))
        ->toBeTrue();
});

it('renews a quarterly subscription correctly', function () {
    $periodEndsAt = now()->subDay();

    $plan = Plan::factory()->create([
        'billing_cycle' => BillingCycle::Quarterly,
        'is_active' => true,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => $periodEndsAt,
        'canceled_at' => null,
        'ends_at' => null,
    ]);

    $subscription = app(RenewSubscriptionAction::class)
        ->handle($subscription);

    expect($subscription->current_period_starts_at->isSameSecond($periodEndsAt))
        ->toBeTrue();

    expect($subscription->current_period_ends_at->isSameSecond($periodEndsAt->copy()->addMonths(3)))
        ->toBeTrue();
});

it('renews a yearly subscription correctly', function () {
    $periodEndsAt = now()->subDay();

    $plan = Plan::factory()->create([
        'billing_cycle' => BillingCycle::Yearly,
        'is_active' => true,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => $periodEndsAt,
        'canceled_at' => null,
        'ends_at' => null,
    ]);

    $subscription = app(RenewSubscriptionAction::class)
        ->handle($subscription);

    expect($subscription->current_period_starts_at->isSameSecond($periodEndsAt))
        ->toBeTrue();

    expect($subscription->current_period_ends_at->isSameSecond($periodEndsAt->copy()->addYear()))
        ->toBeTrue();
});

it('cannot renew a lifetime subscription', function () {
    $plan = Plan::factory()->create([
        'billing_cycle' => BillingCycle::Lifetime,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => null,
    ]);

    expect(fn () => app(RenewSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeRenewedException::class);
});

it('cannot renew before the current period ends', function () {
    $plan = Plan::factory()->create([
        'billing_cycle' => BillingCycle::Monthly,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => now()->addDay(),
        'canceled_at' => null,
    ]);

    expect(fn () => app(RenewSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeRenewedException::class);
});

it('cannot renew a canceled subscription', function () {
    $plan = Plan::factory()->create([
        'billing_cycle' => BillingCycle::Monthly,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => now()->subDay(),
        'canceled_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    expect(fn () => app(RenewSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeRenewedException::class);
});
