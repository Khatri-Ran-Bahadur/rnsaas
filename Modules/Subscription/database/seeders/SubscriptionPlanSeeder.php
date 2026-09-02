<?php

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Subscription\Models\Feature;
use Modules\Subscription\Models\Plan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Essential features for small businesses.',
                'price' => 19.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 1,
                'features' => [
                    'accounting',
                    'accounting.invoices',
                    'accounting.expenses',
                    'inventory',
                    'inventory.products',
                    'pos',
                    'pos.sales',
                ],
            ],

            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Advanced features for growing businesses.',
                'price' => 49.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 2,
                'features' => [
                    'accounting',
                    'accounting.invoices',
                    'accounting.expenses',
                    'accounting.reports',

                    'inventory',
                    'inventory.products',
                    'inventory.stock',

                    'hrm',
                    'hrm.employees',
                    'hrm.attendance',
                    'hrm.leave',

                    'pos',
                    'pos.sales',
                    'pos.products',
                ],
            ],

            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Complete business management features.',
                'price' => 99.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 30,
                'is_active' => true,
                'sort_order' => 3,
                'features' => [
                    'accounting',
                    'accounting.invoices',
                    'accounting.expenses',
                    'accounting.reports',

                    'inventory',
                    'inventory.products',
                    'inventory.stock',

                    'hrm',
                    'hrm.employees',
                    'hrm.attendance',
                    'hrm.leave',

                    'pos',
                    'pos.sales',
                    'pos.products',

                    'payroll',
                    'payroll.salary',
                    'payroll.payslip',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $featureSlugs = $planData['features'];

            unset($planData['features']);

            $plan = Plan::firstOrCreate(
                [
                    'slug' => $planData['slug'],
                ],
                [
                    'public_id' => (string) Str::ulid(),
                    ...$planData,
                ],
            );

            $featureIds = Feature::query()
                ->whereIn('slug', $featureSlugs)
                ->pluck('id')
                ->all();

            $plan->features()->sync($featureIds);
        }
    }
}