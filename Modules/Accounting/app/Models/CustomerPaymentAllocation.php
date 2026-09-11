<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'customer_payment_id',
    'sales_invoice_id',
    'allocated_amount',
])]
#[Hidden([
    'id',
])]
class CustomerPaymentAllocation extends Model
{
    protected $table = 'accounting_customer_payment_allocations';

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $allocation): void {
            $allocation->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customerPayment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'customer_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
