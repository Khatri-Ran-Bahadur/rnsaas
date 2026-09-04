<?php

namespace Modules\Subscription\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Actions\CreateManualPaymentAction;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Enums\BillingCycle;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Exceptions\InactivePlanException;
use Modules\Subscription\Exceptions\TenantAlreadySubscribedException;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

class CreateSubscriptionCheckoutAction
{
    public function __construct(
        private readonly CreateManualPaymentAction $createManualPayment,
    ) {}

    public function handle(
        Tenant $tenant,
        Plan $plan,
        string $idempotencyKey,
    ): PaymentTransaction {
        return DB::transaction(function () use (
            $tenant,
            $plan,
            $idempotencyKey,
        ): PaymentTransaction {
            if (! $plan->is_active) {
                throw new InactivePlanException;
            }

            $hasCurrentSubscription = $tenant
                ->subscriptions()
                ->whereIn('status', [
                    SubscriptionStatus::Pending->value,
                    SubscriptionStatus::Active->value,
                    SubscriptionStatus::Trialing->value,
                ])
                ->exists();

            if ($hasCurrentSubscription) {
                throw new TenantAlreadySubscribedException;
            }

            $startsAt = now();

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
                'status' => SubscriptionStatus::Pending,
                'starts_at' => $startsAt,
                'trial_ends_at' => null,
                'current_period_starts_at' => $startsAt,
                'current_period_ends_at' => $currentPeriodEndsAt,
                'canceled_at' => null,
                'ends_at' => null,
                'metadata' => [
                    'source' => 'subscription.checkout',
                ],
            ]);

            return $this->createManualPayment->handle(
                tenant: $tenant,
                amount: (string) $plan->price,
                currency: $plan->currency,
                type: PaymentType::Subscription,
                idempotencyKey: $idempotencyKey,
                subscription: $subscription,
                metadata: [
                    'plan_id' => $plan->id,
                    'plan_public_id' => $plan->public_id,
                    'subscription_public_id' => $subscription->public_id,
                ],
            );
        });
    }
}
