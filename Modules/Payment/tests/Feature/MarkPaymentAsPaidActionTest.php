<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Payment\Actions\MarkPaymentAsPaidAction;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Events\PaymentPaid;
use Modules\Payment\Exceptions\PaymentCannotBeMarkedAsPaidException;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('marks a pending payment as paid', function () {
    $tenant = Tenant::factory()->create();

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => PaymentStatus::Pending,
        'type' => PaymentType::Subscription,
        'paid_at' => null,
    ]);

    $payment = app(MarkPaymentAsPaidAction::class)
        ->handle($payment);

    expect($payment->status)
        ->toBe(PaymentStatus::Paid);

    expect($payment->paid_at)
        ->not->toBeNull();
});

it('persists the paid status in the database', function () {
    $payment = PaymentTransaction::factory()->create([
        'status' => PaymentStatus::Pending,
        'paid_at' => null,
    ]);

    app(MarkPaymentAsPaidAction::class)
        ->handle($payment);

    $this->assertDatabaseHas('payment_transactions', [
        'id' => $payment->id,
        'status' => PaymentStatus::Paid->value,
    ]);
});

it('dispatches the payment paid event', function () {
    Event::fake();

    $payment = PaymentTransaction::factory()->create([
        'status' => PaymentStatus::Pending,
    ]);

    $payment = app(MarkPaymentAsPaidAction::class)
        ->handle($payment);

    Event::assertDispatched(
        PaymentPaid::class,
        function (PaymentPaid $event) use ($payment): bool {
            return $event->payment->is($payment);
        },
    );
});

it('cannot mark an already paid payment as paid again', function () {
    $payment = PaymentTransaction::factory()->create([
        'status' => PaymentStatus::Paid,
        'paid_at' => now()->subMinute(),
    ]);

    expect(fn () => app(MarkPaymentAsPaidAction::class)
        ->handle($payment))
        ->toThrow(PaymentCannotBeMarkedAsPaidException::class);
});

it('cannot mark a failed payment as paid', function () {
    $payment = PaymentTransaction::factory()->create([
        'status' => PaymentStatus::Failed,
        'paid_at' => null,
    ]);

    expect(fn () => app(MarkPaymentAsPaidAction::class)
        ->handle($payment))
        ->toThrow(PaymentCannotBeMarkedAsPaidException::class);
});

it('cannot mark a refunded payment as paid', function () {
    $payment = PaymentTransaction::factory()->create([
        'status' => PaymentStatus::Refunded,
        'paid_at' => null,
    ]);

    expect(fn () => app(MarkPaymentAsPaidAction::class)
        ->handle($payment))
        ->toThrow(PaymentCannotBeMarkedAsPaidException::class);
});

it('only performs the pending to paid transition when the expected status matches', function () {
    $payment = PaymentTransaction::factory()->create([
        'status' => PaymentStatus::Paid,
        'paid_at' => now()->subMinute(),
    ]);

    $updated = PaymentTransaction::query()
        ->whereKey($payment->id)
        ->where('status', PaymentStatus::Pending->value)
        ->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

    expect($updated)->toBe(0);

    $this->assertDatabaseHas('payment_transactions', [
        'id' => $payment->id,
        'status' => PaymentStatus::Paid->value,
    ]);
});
