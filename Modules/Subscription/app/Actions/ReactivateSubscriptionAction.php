<?php

namespace Modules\Subscription\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Subscription\Events\SubscriptionReactivated;
use Modules\Subscription\Exceptions\SubscriptionCannotBeReactivatedException;
use Modules\Subscription\Models\TenantSubscription;

class ReactivateSubscriptionAction
{
    public function handle(
        TenantSubscription $subscription,
    ): TenantSubscription {
        return DB::transaction(function () use ($subscription): TenantSubscription {
            $subscription = TenantSubscription::query()
                ->lockForUpdate()
                ->findOrFail($subscription->id);

            if (! $subscription->status->isActiveOrTrialing()) {
                throw new SubscriptionCannotBeReactivatedException;
            }

            if ($subscription->canceled_at === null) {
                throw new SubscriptionCannotBeReactivatedException(
                    'The subscription is not scheduled for cancellation.',
                );
            }

            if (
                $subscription->current_period_ends_at !== null
                && $subscription->current_period_ends_at->isPast()
            ) {
                throw new SubscriptionCannotBeReactivatedException(
                    'The subscription period has already ended and cannot be reactivated.',
                );
            }

            $subscription->update([
                'canceled_at' => null,
                'ends_at' => null,
            ]);

            $subscription->refresh();

            SubscriptionReactivated::dispatch($subscription);

            return $subscription;
        });
    }
}
