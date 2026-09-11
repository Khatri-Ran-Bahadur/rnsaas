<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\VendorPayment;
use Modules\Accounting\Models\VendorPaymentAllocation;
use Modules\Tenancy\Models\Tenant;

class VendorPaymentAllocationFactory extends Factory
{
    protected $model = VendorPaymentAllocation::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'vendor_payment_id' => VendorPayment::factory(),
            'purchase_bill_id' => PurchaseBill::factory(),
            'allocated_amount' => 500,
        ];
    }
}
