<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Audit\Models\AuditLog;
use Modules\Subscription\Actions\CreateSubscriptionAction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\TenantSubscriptionCreated;
use Modules\Subscription\Exceptions\InactivePlanException;
use Modules\Subscription\Exceptions\TenantAlreadySubscribedException;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('creates a subscription for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
        'trial_days' => 0,
    ]);

    $subscription = app(CreateSubscriptionAction::class)
        ->handle($tenant, $plan);

    expect($subscription)
        ->toBeInstanceOf(TenantSubscription::class)
        ->and($subscription->tenant_id)
        ->toBe($tenant->id)
        ->and($subscription->plan_id)
        ->toBe($plan->id)
        ->and($subscription->status)
        ->toBe(SubscriptionStatus::Active);

    $this->assertDatabaseHas('tenant_subscriptions', [
        'id' => $subscription->id,
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active->value,
    ]);
});

it('creates a trialing subscription when the plan has trial days', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
        'trial_days' => 14,
    ]);

    $subscription = app(CreateSubscriptionAction::class)
        ->handle($tenant, $plan);

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Trialing);

    expect($subscription->trial_ends_at)
        ->not->toBeNull();
});

it('dispatches the subscription created event', function () {
    Event::fake();

    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
        'trial_days' => 0,
    ]);

    $subscription = app(CreateSubscriptionAction::class)
        ->handle($tenant, $plan);

    Event::assertDispatched(
        TenantSubscriptionCreated::class,
        function (TenantSubscriptionCreated $event) use ($subscription): bool {
            return $event->subscription->is($subscription);
        },
    );
});

it('records an audit log when a subscription is created', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
        'trial_days' => 0,
    ]);

    $subscription = app(CreateSubscriptionAction::class)
        ->handle($tenant, $plan);

    $auditLog = AuditLog::query()
        ->where('auditable_type', $subscription->getMorphClass())
        ->where('auditable_id', $subscription->id)
        ->where('event', 'subscription.created')
        ->first();

    expect($auditLog)
        ->not->toBeNull();

    expect($auditLog->tenant_id)
        ->toBe($tenant->id);

    expect($auditLog->new_values)
        ->toMatchArray([
            'plan_id' => $plan->id,
            'status' => $subscription->status->value,
        ]);

    expect($auditLog->metadata)
        ->toMatchArray([
            'module' => 'subscription',
            'source' => 'subscription.create',
            'subscription_public_id' => $subscription->public_id,
        ]);
});

it('does not create another subscription when the tenant already has an active subscription', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
        'trial_days' => 0,
    ]);

    TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    expect(fn () => app(CreateSubscriptionAction::class)
        ->handle($tenant, $plan))
        ->toThrow(
            TenantAlreadySubscribedException::class,
        );

    expect(
        TenantSubscription::query()
            ->where('tenant_id', $tenant->id)
            ->count()
    )->toBe(1);
});

it('does not create a subscription for an inactive plan', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => false,
    ]);

    expect(fn () => app(CreateSubscriptionAction::class)
        ->handle($tenant, $plan))
        ->toThrow(
            InactivePlanException::class,
        );

    expect(
        TenantSubscription::query()
            ->where('tenant_id', $tenant->id)
            ->exists()
    )->toBeFalse();
});
