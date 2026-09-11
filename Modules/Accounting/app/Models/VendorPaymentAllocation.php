<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Accounting\Database\Factories\VendorPaymentAllocationFactory;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'vendor_payment_id',
    'purchase_bill_id',
    'allocated_amount',
])]
#[Hidden([
    'id',
])]
class VendorPaymentAllocation extends Model
{
    use HasFactory;

    protected $table = 'accounting_vendor_payment_allocations';

    protected static function newFactory(): VendorPaymentAllocationFactory
    {
        return VendorPaymentAllocationFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $allocation): void {
            $allocation->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:6',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            VendorPayment::class,
            'vendor_payment_id'
        );
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseBill::class,
            'purchase_bill_id'
        );
    }
}
