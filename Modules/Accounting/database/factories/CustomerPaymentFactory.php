<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\CustomerPayment;
use Modules\Tenancy\Models\Tenant;

class CustomerPaymentFactory extends Factory
{
    protected $model = CustomerPayment::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'payment_number' => 'PAY-'.fake()->unique()->numerify('#####'),
            'payment_date' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'MYR',
            'bank_account_id' => null,
            'payment_method' => 'bank_transfer',
            'reference' => 'PAYREF-'.fake()->numerify('####'),
            'notes' => fake()->sentence(),
            'status' => 'draft',
            'journal_entry_id' => null,
            'created_by' => null,
        ];
    }
}
