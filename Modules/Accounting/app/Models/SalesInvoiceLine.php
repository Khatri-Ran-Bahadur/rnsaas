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
    'sales_invoice_id',
    'revenue_account_id',
    'line_number',
    'description',
    'quantity',
    'unit_price',
    'discount_amount',
    'tax_rate',
    'tax_amount',
    'subtotal',
    'total',
])]
#[Hidden([
    'id',
])]
class SalesInvoiceLine extends Model
{
    protected $table = 'accounting_sales_invoice_lines';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $line): void {
            $line->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }
}
