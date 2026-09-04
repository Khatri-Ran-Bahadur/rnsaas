<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Actions\CreateManualPaymentAction;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('creates a pending bank transfer payment', function () {
    $tenant = Tenant::factory()->create();

    $payment = app(CreateManualPaymentAction::class)
        ->handle(
            tenant: $tenant,
            amount: '49.00',
            currency: 'USD',
            type: PaymentType::Subscription,
            idempotencyKey: 'bank-transfer-test-001',
        );

    expect($payment)
        ->toBeInstanceOf(PaymentTransaction::class);

    expect($payment->tenant_id)
        ->toBe($tenant->id);

    expect($payment->provider)
        ->toBe('bank_transfer');

    expect($payment->status)
        ->toBe(PaymentStatus::Pending);

    expect($payment->type)
        ->toBe(PaymentType::Subscription);

    expect($payment->amount)
        ->toBe('49.00');

    expect($payment->currency)
        ->toBe('USD');

    expect($payment->paid_at)
        ->toBeNull();
});

it('creates a payment linked to a subscription', function () {
    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
    ]);

    $payment = app(CreateManualPaymentAction::class)
        ->handle(
            tenant: $tenant,
            amount: '49.00',
            currency: 'USD',
            type: PaymentType::Subscription,
            idempotencyKey: 'subscription-payment-001',
            subscription: $subscription,
        );

    expect($payment->subscription_id)
        ->toBe($subscription->id);
});

it('normalizes the payment currency to uppercase', function () {
    $tenant = Tenant::factory()->create();

    $payment = app(CreateManualPaymentAction::class)
        ->handle(
            tenant: $tenant,
            amount: '49.00',
            currency: 'usd',
            type: PaymentType::Subscription,
            idempotencyKey: 'currency-test-001',
        );

    expect($payment->currency)
        ->toBe('USD');
});

it('stores payment metadata', function () {
    $tenant = Tenant::factory()->create();

    $payment = app(CreateManualPaymentAction::class)
        ->handle(
            tenant: $tenant,
            amount: '49.00',
            currency: 'USD',
            type: PaymentType::Subscription,
            idempotencyKey: 'metadata-test-001',
            metadata: [
                'bank_name' => 'Example Bank',
                'reference_number' => 'BT-12345',
            ],
        );

    expect($payment->metadata)
        ->toMatchArray([
            'bank_name' => 'Example Bank',
            'reference_number' => 'BT-12345',
        ]);
});

it('persists the payment transaction in the database', function () {
    $tenant = Tenant::factory()->create();

    $payment = app(CreateManualPaymentAction::class)
        ->handle(
            tenant: $tenant,
            amount: '100.00',
            currency: 'USD',
            type: PaymentType::OneTime,
            idempotencyKey: 'database-test-001',
        );

    $this->assertDatabaseHas('payment_transactions', [
        'id' => $payment->id,
        'tenant_id' => $tenant->id,
        'provider' => 'bank_transfer',
        'status' => PaymentStatus::Pending->value,
        'type' => PaymentType::OneTime->value,
        'idempotency_key' => 'database-test-001',
    ]);
});
