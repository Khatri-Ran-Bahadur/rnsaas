<?php

namespace Modules\Subscription\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\TenantSubscriptionExpired;
use Modules\Subscription\Exceptions\SubscriptionCannotBeExpiredException;
use Modules\Subscription\Models\TenantSubscription;

class ExpireSubscriptionAction
{
    public function handle(
        TenantSubscription $subscription,
    ): TenantSubscription {
        return DB::transaction(function () use ($subscription): TenantSubscription {
            if (! $subscription->status->isActiveOrTrialing()) {
                throw new SubscriptionCannotBeExpiredException();
            }

            $expirationAt = $subscription->ends_at
                ?? $subscription->current_period_ends_at;

            if ($expirationAt === null || $expirationAt->isFuture()) {
                throw new SubscriptionCannotBeExpiredException();
            }

            $subscription->update([
                'status' => SubscriptionStatus::Expired,
                'ends_at' => $subscription->ends_at ?? $expirationAt,
            ]);

            $subscription->refresh();

            TenantSubscriptionExpired::dispatch($subscription);

            return $subscription;
        });
    }
}