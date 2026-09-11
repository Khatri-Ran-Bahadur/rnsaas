<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Tenancy\Models\Tenant;

class SalesInvoiceFactory extends Factory
{
    protected $model = SalesInvoice::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'MYR',
            'subtotal' => 1000,
            'discount_total' => 0,
            'tax_total' => 60,
            'grand_total' => 1060,
            'status' => 'draft',
            'reference' => 'REF-'.fake()->numerify('####'),
            'notes' => fake()->sentence(),
            'journal_entry_id' => null,
            'created_by' => null,
        ];
    }
}
