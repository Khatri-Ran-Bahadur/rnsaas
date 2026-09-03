<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Actions\ActivateSubscriptionFromPaymentAction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\SubscriptionActivated;
use Modules\Subscription\Exceptions\SubscriptionCannotBeActivatedException;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('activates a pending subscription from a paid payment', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create([
        'is_active' => true,
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $paidAt = now()->subMinute()->startOfSecond();

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Paid,
        'type' => PaymentType::Subscription,
        'paid_at' => $paidAt,
    ]);

    $subscription = app(ActivateSubscriptionFromPaymentAction::class)
        ->handle($payment);

    expect($subscription->status)
        ->toBe(SubscriptionStatus::Active);

    expect($subscription->starts_at)
        ->toEqual($paidAt);

    expect($subscription->current_period_starts_at)
        ->toEqual($paidAt);
});

it('persists the active status in the database', function () {
    $tenant = Tenant::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);

    app(ActivateSubscriptionFromPaymentAction::class)
        ->handle($payment);

    $this->assertDatabaseHas('tenant_subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::Active->value,
    ]);
});

it('dispatches subscription activated event', function () {
    Event::fake();

    $tenant = Tenant::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);

    $subscription = app(ActivateSubscriptionFromPaymentAction::class)
        ->handle($payment);

    Event::assertDispatched(
        SubscriptionActivated::class,
        function (SubscriptionActivated $event) use ($subscription): bool {
            return $event->subscription->is($subscription);
        },
    );
});

it('cannot activate a subscription from a pending payment', function () {
    $tenant = Tenant::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Pending,
        'paid_at' => null,
    ]);

    expect(fn () => app(ActivateSubscriptionFromPaymentAction::class)
        ->handle($payment))
        ->toThrow(SubscriptionCannotBeActivatedException::class);
});

it('cannot activate a subscription from a failed payment', function () {
    $tenant = Tenant::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Failed,
        'paid_at' => null,
    ]);

    expect(fn () => app(ActivateSubscriptionFromPaymentAction::class)
        ->handle($payment))
        ->toThrow(SubscriptionCannotBeActivatedException::class);
});

it('cannot activate an already active subscription', function () {
    $tenant = Tenant::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);

    expect(fn () => app(ActivateSubscriptionFromPaymentAction::class)
        ->handle($payment))
        ->toThrow(SubscriptionCannotBeActivatedException::class);
});

it('cannot activate when payment and subscription belong to different tenants', function () {
    $subscriptionTenant = Tenant::factory()->create();
    $paymentTenant = Tenant::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $subscriptionTenant->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $paymentTenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);

    expect(fn () => app(ActivateSubscriptionFromPaymentAction::class)
        ->handle($payment))
        ->toThrow(SubscriptionCannotBeActivatedException::class);
});

it('cannot activate when payment is not linked to a subscription', function () {
    $tenant = Tenant::factory()->create();

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => null,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);

    expect(fn () => app(ActivateSubscriptionFromPaymentAction::class)
        ->handle($payment))
        ->toThrow(SubscriptionCannotBeActivatedException::class);
});