<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Actions\CreateSubscriptionCheckoutAction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('creates a pending subscription and pending payment', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'price' => '49.00',
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $payment = app(CreateSubscriptionCheckoutAction::class)
        ->handle(
            tenant: $tenant,
            plan: $plan,
            idempotencyKey: 'checkout-test-001',
        );

    expect($payment->status)
        ->toBe(PaymentStatus::Pending);

    expect($payment->type)
        ->toBe(PaymentType::Subscription);

    $subscription = TenantSubscription::query()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Pending);

    expect($payment->subscription_id)
        ->toBe($subscription->id);

    expect($payment->amount)
        ->toEqual('49.00');

    expect($payment->currency)
        ->toBe('USD');
});

it('does not create checkout when tenant already has active subscription', function () {
    $tenant = Tenant::factory()->create();

    TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $plan = Plan::factory()->create([
        'is_active' => true,
    ]);

    expect(fn () => app(CreateSubscriptionCheckoutAction::class)
        ->handle(
            tenant: $tenant,
            plan: $plan,
            idempotencyKey: 'checkout-test-002',
        ))
        ->toThrow(
            \Modules\Subscription\Exceptions\TenantAlreadySubscribedException::class,
        );
});

it('does not create checkout when tenant has pending subscription', function () {
    $tenant = Tenant::factory()->create();

    TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $plan = Plan::factory()->create([
        'is_active' => true,
    ]);

    expect(fn () => app(CreateSubscriptionCheckoutAction::class)
        ->handle(
            tenant: $tenant,
            plan: $plan,
            idempotencyKey: 'checkout-test-003',
        ))
        ->toThrow(
            \Modules\Subscription\Exceptions\TenantAlreadySubscribedException::class,
        );
});

it('does not create checkout for inactive plan', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => false,
    ]);

    expect(fn () => app(CreateSubscriptionCheckoutAction::class)
        ->handle(
            tenant: $tenant,
            plan: $plan,
            idempotencyKey: 'checkout-test-004',
        ))
        ->toThrow(
            \Modules\Subscription\Exceptions\InactivePlanException::class,
        );
});

it('creates a monthly billing period', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'price' => '19.00',
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    $payment = app(CreateSubscriptionCheckoutAction::class)
        ->handle(
            tenant: $tenant,
            plan: $plan,
            idempotencyKey: 'checkout-test-005',
        );

    $subscription = TenantSubscription::query()
        ->where('id', $payment->subscription_id)
        ->firstOrFail();

    expect(
        $subscription->current_period_ends_at
            ->equalTo($subscription->current_period_starts_at->copy()->addMonth())
    )->toBeTrue();
});

it('creates a lifetime subscription without a period end', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'price' => '999.00',
        'currency' => 'USD',
        'billing_cycle' => 'lifetime',
        'is_active' => true,
    ]);

    $payment = app(CreateSubscriptionCheckoutAction::class)
        ->handle(
            tenant: $tenant,
            plan: $plan,
            idempotencyKey: 'checkout-test-006',
        );

    $subscription = TenantSubscription::query()
        ->where('id', $payment->subscription_id)
        ->firstOrFail();

    expect($subscription->current_period_ends_at)
        ->toBeNull();
});

it('stores the subscription reference on the payment', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
    ]);

    $payment = app(CreateSubscriptionCheckoutAction::class)
        ->handle(
            tenant: $tenant,
            plan: $plan,
            idempotencyKey: 'checkout-test-007',
        );

    $subscription = TenantSubscription::query()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    expect($payment->subscription_id)
        ->toBe($subscription->id);

    $this->assertDatabaseHas('payment_transactions', [
        'id' => $payment->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Pending->value,
        'type' => PaymentType::Subscription->value,
    ]);
});