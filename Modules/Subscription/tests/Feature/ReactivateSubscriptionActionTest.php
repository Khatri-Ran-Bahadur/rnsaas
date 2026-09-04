<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Subscription\Actions\ReactivateSubscriptionAction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\SubscriptionReactivated;
use Modules\Subscription\Exceptions\SubscriptionCannotBeReactivatedException;
use Modules\Subscription\Models\TenantSubscription;

uses(RefreshDatabase::class);

it('reactivates a scheduled active subscription', function () {
    Event::fake();

    $canceledAt = now()->subDay();
    $periodEndsAt = now()->addDays(20);

    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'canceled_at' => $canceledAt,
        'ends_at' => $periodEndsAt,
        'current_period_ends_at' => $periodEndsAt,
    ]);

    $result = app(ReactivateSubscriptionAction::class)
        ->handle($subscription);

    expect($result->canceled_at)->toBeNull();
    expect($result->ends_at)->toBeNull();

    $this->assertDatabaseHas('tenant_subscriptions', [
        'id' => $subscription->id,
        'canceled_at' => null,
        'ends_at' => null,
        'status' => SubscriptionStatus::Active->value,
    ]);

    Event::assertDispatched(
        SubscriptionReactivated::class,
    );
});

it('reactivates a scheduled trialing subscription', function () {
    $periodEndsAt = now()->addDays(10);

    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Trialing,
        'canceled_at' => now()->subHour(),
        'ends_at' => $periodEndsAt,
        'current_period_ends_at' => $periodEndsAt,
    ]);

    $result = app(ReactivateSubscriptionAction::class)
        ->handle($subscription);

    expect($result->status)->toBe(SubscriptionStatus::Trialing);
    expect($result->canceled_at)->toBeNull();
    expect($result->ends_at)->toBeNull();
});

it('rejects reactivation when subscription is not canceled', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'canceled_at' => null,
        'ends_at' => null,
    ]);

    expect(fn () => app(ReactivateSubscriptionAction::class)->handle($subscription))
        ->toThrow(SubscriptionCannotBeReactivatedException::class);
});

it('rejects reactivation when subscription is expired', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Expired,
        'canceled_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    expect(fn () => app(ReactivateSubscriptionAction::class)->handle($subscription))
        ->toThrow(SubscriptionCannotBeReactivatedException::class);
});

it('rejects reactivation when billing period has ended', function () {
    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'canceled_at' => now()->subDays(10),
        'ends_at' => now()->subDay(),
        'current_period_ends_at' => now()->subDay(),
    ]);

    expect(fn () => app(ReactivateSubscriptionAction::class)->handle($subscription))
        ->toThrow(SubscriptionCannotBeReactivatedException::class);
});
