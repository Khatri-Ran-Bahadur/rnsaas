<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Accounting\Models\Customer;
use Modules\Tenancy\Models\Tenant;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'customer_code' => 'CUST-'.fake()->unique()->numerify('#####'),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_number' => 'TAX-'.fake()->numerify('########'),
            'billing_address_line_1' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_state' => fake()->state(),
            'billing_postcode' => fake()->postcode(),
            'billing_country' => 'MY',
            'receivable_account_id' => null,
            'credit_limit' => 10000,
            'payment_terms_days' => 30,
            'is_active' => true,
            'status' => 'active',
            'created_by' => null,
        ];
    }
}
