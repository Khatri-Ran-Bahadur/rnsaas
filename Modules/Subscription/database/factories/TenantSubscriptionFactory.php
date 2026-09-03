<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

/**
 * @extends Factory<TenantSubscription>
 */
class TenantSubscriptionFactory extends Factory
{
    protected $model = TenantSubscription::class;

    public function definition(): array
    {
        $startsAt = now()->subDays(
            fake()->numberBetween(0, 30),
        );

        $periodEndsAt = $startsAt->copy()->addMonth();

        return [
            'public_id' => (string) Str::ulid(),
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'starts_at' => $startsAt,
            'trial_ends_at' => null,
            'current_period_starts_at' => $startsAt,
            'current_period_ends_at' => $periodEndsAt,
            'canceled_at' => null,
            'ends_at' => null,
            'metadata' => null,
        ];
    }
}
