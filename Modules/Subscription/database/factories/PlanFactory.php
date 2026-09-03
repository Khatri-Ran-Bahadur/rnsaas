<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Subscription\Enums\BillingCycle;
use Modules\Subscription\Models\Plan;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Starter Test',
            'Business Test',
            'Enterprise Test',
        ]);

        return [
            'public_id' => (string) Str::ulid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 200),
            'currency' => 'USD',
            'billing_cycle' => fake()->randomElement([
                BillingCycle::Monthly,
                BillingCycle::Quarterly,
                BillingCycle::Yearly,
                BillingCycle::Lifetime,
            ]),
            'trial_days' => fake()->randomElement([
                0,
                7,
                14,
                30,
            ]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'metadata' => null,
        ];
    }
}