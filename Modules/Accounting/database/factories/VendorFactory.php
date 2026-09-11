<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\VendorStatus;
use Modules\Accounting\Models\Vendor;
use Modules\Tenancy\Models\Tenant;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'vendor_code' => 'VEND-'.fake()->unique()->numerify('#####'),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_number' => 'TAX-'.fake()->numerify('########'),
            'billing_address_line_1' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_state' => fake()->state(),
            'billing_postcode' => fake()->postcode(),
            'billing_country' => 'MY',
            'payable_account_id' => null,
            'credit_limit' => 10000,
            'payment_terms_days' => 30,
            'status' => VendorStatus::ACTIVE,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
