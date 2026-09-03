<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Events\PaymentPaid;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\SubscriptionActivated;
use Modules\Subscription\Listeners\ActivateSubscriptionFromPayment;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('activates the subscription when payment paid event is handled', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Paid,
        'type' => PaymentType::Subscription,
        'paid_at' => now(),
    ]);

    $event = new PaymentPaid($payment);

    app(ActivateSubscriptionFromPayment::class)
        ->handle($event);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Active);
});

it('does not change an already active subscription', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Paid,
        'type' => PaymentType::Subscription,
        'paid_at' => now(),
    ]);

    $event = new PaymentPaid($payment);

    expect(fn () => app(ActivateSubscriptionFromPayment::class)
        ->handle($event))
        ->toThrow(
            \Modules\Subscription\Exceptions\SubscriptionCannotBeActivatedException::class,
        );

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Active);
});

it('does not activate subscription for unpaid payment', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Pending,
        'type' => PaymentType::Subscription,
        'paid_at' => null,
    ]);

    $event = new PaymentPaid($payment);

    expect(fn () => app(ActivateSubscriptionFromPayment::class)
        ->handle($event))
        ->toThrow(
            \Modules\Subscription\Exceptions\SubscriptionCannotBeActivatedException::class,
        );

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Pending);
});


it('activates the subscription when payment paid event is dispatched', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Paid,
        'type' => PaymentType::Subscription,
        'paid_at' => now(),
    ]);

    PaymentPaid::dispatch($payment);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Active);
});