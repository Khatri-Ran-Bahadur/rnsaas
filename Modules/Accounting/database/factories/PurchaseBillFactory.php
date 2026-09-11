<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\PurchaseBillStatus;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\Vendor;
use Modules\Tenancy\Models\Tenant;

class PurchaseBillFactory extends Factory
{
    protected $model = PurchaseBill::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'vendor_id' => Vendor::factory(),
            'bill_number' => 'BILL-'.fake()->unique()->numerify('#####'),
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'MYR',
            'subtotal' => 1000,
            'discount_total' => 0,
            'tax_total' => 60,
            'grand_total' => 1060,
            'status' => PurchaseBillStatus::DRAFT,
            'reference' => 'REF-'.fake()->numerify('####'),
            'notes' => fake()->sentence(),
            'journal_entry_id' => null,
            'created_by' => null,
            'issued_by' => null,
            'posted_by' => null,
            'voided_by' => null,
            'issued_at' => null,
            'posted_at' => null,
            'voided_at' => null,
        ];
    }
}
