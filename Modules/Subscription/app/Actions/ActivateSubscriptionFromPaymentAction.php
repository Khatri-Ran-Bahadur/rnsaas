<?php

namespace Modules\Subscription\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\SubscriptionActivated;
use Modules\Subscription\Exceptions\SubscriptionCannotBeActivatedException;
use Modules\Subscription\Models\TenantSubscription;

class ActivateSubscriptionFromPaymentAction
{
    public function handle(
        PaymentTransaction $payment,
    ): TenantSubscription {
        return DB::transaction(function () use ($payment): TenantSubscription {
            if (! $payment->status->isPaid()) {
                throw new SubscriptionCannotBeActivatedException(
                    'The payment must be paid before the subscription can be activated.',
                );
            }

            if ($payment->subscription_id === null) {
                throw new SubscriptionCannotBeActivatedException(
                    'The payment is not linked to a subscription.',
                );
            }

            $subscription = TenantSubscription::query()
                ->whereKey($payment->subscription_id)
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                throw new SubscriptionCannotBeActivatedException(
                    'The subscription linked to the payment does not exist.',
                );
            }

            if ($subscription->tenant_id !== $payment->tenant_id) {
                throw new SubscriptionCannotBeActivatedException(
                    'The payment and subscription belong to different tenants.',
                );
            }

            if (! $subscription->status->isPending()) {
                throw new SubscriptionCannotBeActivatedException(
                    'The subscription is not pending.',
                );
            }

            // Capture the state before changing it.
            $previousStatus = $subscription->status->value;

            // Payment time becomes the subscription activation time.
            $activationTime = $payment->paid_at ?? now();

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'starts_at' => $activationTime,
                'current_period_starts_at' => $activationTime,
            ]);

            $subscription->refresh();

            // Publish the state transition to other parts of the system.
            SubscriptionActivated::dispatch(
                subscription: $subscription,
                previousStatus: $previousStatus,
            );

            return $subscription;
        });
    }
}