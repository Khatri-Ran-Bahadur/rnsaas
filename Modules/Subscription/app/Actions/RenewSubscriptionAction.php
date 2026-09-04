<?php

namespace Modules\Subscription\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\Enums\BillingCycle;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Exceptions\SubscriptionCannotBeRenewedException;
use Modules\Subscription\Models\TenantSubscription;

class RenewSubscriptionAction
{
    public function handle(
        TenantSubscription $subscription,
        ?Carbon $renewedAt = null,
    ): TenantSubscription {
        return DB::transaction(function () use (
            $subscription,
            $renewedAt,
        ): TenantSubscription {
            if (! $subscription->status->isActiveOrTrialing()) {
                throw new SubscriptionCannotBeRenewedException;
            }

            if ($subscription->current_period_ends_at === null) {
                throw new SubscriptionCannotBeRenewedException;
            }

            if ($subscription->canceled_at !== null) {
                throw new SubscriptionCannotBeRenewedException;
            }

            if ($subscription->current_period_ends_at->isFuture()) {
                throw new SubscriptionCannotBeRenewedException;
            }

            $renewedAt ??= now();

            $periodStartsAt = $subscription->current_period_ends_at;

            $periodEndsAt = match ($subscription->plan->billing_cycle) {
                BillingCycle::Monthly => $periodStartsAt->copy()->addMonth(),
                BillingCycle::Quarterly => $periodStartsAt->copy()->addMonths(3),
                BillingCycle::Yearly => $periodStartsAt->copy()->addYear(),
                BillingCycle::Lifetime => null,
            };

            if ($periodEndsAt === null) {
                throw new SubscriptionCannotBeRenewedException;
            }

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'current_period_starts_at' => $periodStartsAt,
                'current_period_ends_at' => $periodEndsAt,
                'canceled_at' => null,
                'ends_at' => null,
            ]);

            return $subscription->refresh();
        });
    }
}
