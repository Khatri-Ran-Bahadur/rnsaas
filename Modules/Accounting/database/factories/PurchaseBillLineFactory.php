<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\PurchaseBillLine;

class PurchaseBillLineFactory extends Factory
{
    protected $model = PurchaseBillLine::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'purchase_bill_id' => PurchaseBill::factory(),
            'line_number' => 1,
            'description' => fake()->sentence(3),
            'quantity' => 1,
            'unit_price' => 100,
            'discount_amount' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'subtotal' => 100,
            'total' => 100,
            'debit_account_id' => null,
            'tax_account_id' => null,
        ];
    }
}
