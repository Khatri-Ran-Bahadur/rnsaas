<?php

namespace Modules\Subscription\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Subscription\Enums\BillingCycle;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\TenantSubscriptionCreated;
use Modules\Subscription\Exceptions\InactivePlanException;
use Modules\Subscription\Exceptions\TenantAlreadySubscribedException;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

class CreateSubscriptionAction
{
    public function handle(
        Tenant $tenant,
        Plan $plan,
        ?Carbon $startsAt = null,
    ): TenantSubscription {
        return DB::transaction(function () use (
            $tenant,
            $plan,
            $startsAt,
        ): TenantSubscription {
            if (! $plan->is_active) {
                throw new InactivePlanException;
            }

            $hasCurrentSubscription = $tenant
                ->subscriptions()
                ->whereIn('status', [
                    SubscriptionStatus::Active->value,
                    SubscriptionStatus::Trialing->value,
                ])
                ->exists();

            if ($hasCurrentSubscription) {
                throw new TenantAlreadySubscribedException;
            }

            $startsAt ??= now();

            $trialEndsAt = $plan->trial_days > 0
                ? $startsAt->copy()->addDays($plan->trial_days)
                : null;

            $currentPeriodStartsAt = $startsAt->copy();

            $currentPeriodEndsAt = match ($plan->billing_cycle) {
                BillingCycle::Monthly => $startsAt->copy()->addMonth(),
                BillingCycle::Quarterly => $startsAt->copy()->addMonths(3),
                BillingCycle::Yearly => $startsAt->copy()->addYear(),
                BillingCycle::Lifetime => null,
            };

            $subscription = TenantSubscription::create([
                'public_id' => (string) Str::ulid(),
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $trialEndsAt
                    ? SubscriptionStatus::Trialing
                    : SubscriptionStatus::Active,
                'starts_at' => $startsAt,
                'trial_ends_at' => $trialEndsAt,
                'current_period_starts_at' => $currentPeriodStartsAt,
                'current_period_ends_at' => $currentPeriodEndsAt,
                'canceled_at' => null,
                'ends_at' => null,
                'metadata' => null,
            ]);

            TenantSubscriptionCreated::dispatch($subscription);

            return $subscription;
        });
    }
}
