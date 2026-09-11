<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\VendorPaymentStatus;
use Modules\Accounting\Models\Vendor;
use Modules\Accounting\Models\VendorPayment;
use Modules\Tenancy\Models\Tenant;

class VendorPaymentFactory extends Factory
{
    protected $model = VendorPayment::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'vendor_id' => Vendor::factory(),
            'payment_number' => 'VP-'.fake()->unique()->numerify('#####'),
            'payment_date' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'MYR',
            'payment_method' => 'bank_transfer',
            'bank_account_id' => null,
            'reference' => 'VPREF-'.fake()->numerify('####'),
            'notes' => fake()->sentence(),
            'status' => VendorPaymentStatus::DRAFT,
            'journal_entry_id' => null,
            'created_by' => null,
            'posted_by' => null,
            'posted_at' => null,
        ];
    }
}
