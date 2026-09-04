<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Audit\Models\AuditLog;
use Modules\Subscription\Actions\ExpireSubscriptionAction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\TenantSubscriptionExpired;
use Modules\Subscription\Exceptions\SubscriptionCannotBeExpiredException;
use Modules\Subscription\Models\TenantSubscription;

uses(RefreshDatabase::class);

it('expires an active subscription after its current period ends', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_starts_at' => now()->subMonth(),
        'current_period_ends_at' => now()->subDay(),
        'ends_at' => null,
    ]);

    $subscription = app(ExpireSubscriptionAction::class)
        ->handle($subscription);

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Expired);

    expect($subscription->ends_at)
        ->not->toBeNull();
});

it('expires a canceled subscription after its end date', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => now()->addDay(),
        'canceled_at' => now()->subDay(),
        'ends_at' => now()->subHour(),
    ]);

    $subscription = app(ExpireSubscriptionAction::class)
        ->handle($subscription);

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Expired);

    expect($subscription->canceled_at)
        ->not->toBeNull();
});

it('expires a trialing subscription after its period ends', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Trialing,
        'current_period_starts_at' => now()->subDays(15),
        'current_period_ends_at' => now()->subDay(),
        'ends_at' => null,
    ]);

    $subscription = app(ExpireSubscriptionAction::class)
        ->handle($subscription);

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Expired);
});

it('cannot expire a subscription before its expiration date', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => now()->addDay(),
        'ends_at' => null,
    ]);

    expect(fn () => app(ExpireSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeExpiredException::class);
});

it('cannot expire an already expired subscription', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Expired,
        'current_period_ends_at' => now()->subDay(),
    ]);

    expect(fn () => app(ExpireSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeExpiredException::class);
});

it('cannot expire a past due subscription', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::PastDue,
        'current_period_ends_at' => now()->subDay(),
    ]);

    expect(fn () => app(ExpireSubscriptionAction::class)
        ->handle($subscription))
        ->toThrow(SubscriptionCannotBeExpiredException::class);
});

it('dispatches the subscription expired event', function () {
    Event::fake();

    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => now()->subDay(),
        'ends_at' => null,
    ]);

    $subscription = app(ExpireSubscriptionAction::class)
        ->handle($subscription);

    Event::assertDispatched(
        TenantSubscriptionExpired::class,
        function (TenantSubscriptionExpired $event) use ($subscription): bool {
            return $event->subscription->is($subscription);
        },
    );
});

it('records an audit log when a subscription expires', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => now()->subDay(),
        'ends_at' => null,
    ]);

    $subscription = app(ExpireSubscriptionAction::class)
        ->handle($subscription);

    $auditLog = AuditLog::query()
        ->where('auditable_type', $subscription->getMorphClass())
        ->where('auditable_id', $subscription->id)
        ->where('event', 'subscription.expired')
        ->first();

    expect($auditLog)
        ->not->toBeNull();

    expect($auditLog->tenant_id)
        ->toBe($subscription->tenant_id);

    expect($auditLog->new_values)
        ->toMatchArray([
            'status' => SubscriptionStatus::Expired->value,
        ]);

    expect($auditLog->metadata)
        ->toMatchArray([
            'module' => 'subscription',
            'source' => 'subscription.expire',
            'subscription_public_id' => $subscription->public_id,
        ]);
});
