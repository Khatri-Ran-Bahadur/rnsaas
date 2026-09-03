<?php

namespace Modules\Subscription\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\TenantSubscriptionCanceled;
use Modules\Subscription\Exceptions\SubscriptionCannotBeCanceledException;
use Modules\Subscription\Models\TenantSubscription;

class CancelSubscriptionAction
{
    public function handle(
        TenantSubscription $subscription,
    ): TenantSubscription {
        return DB::transaction(function () use ($subscription): TenantSubscription {
            if (! $subscription->status->isActiveOrTrialing()) {
                throw new SubscriptionCannotBeCanceledException();
            }

            if ($subscription->canceled_at !== null) {
                throw new SubscriptionCannotBeCanceledException();
            }

            $subscription->update([
                'canceled_at' => now(),
                'ends_at' => $subscription->current_period_ends_at,
            ]);

            $subscription->refresh();

            TenantSubscriptionCanceled::dispatch($subscription);

            return $subscription;
        });
    }
}