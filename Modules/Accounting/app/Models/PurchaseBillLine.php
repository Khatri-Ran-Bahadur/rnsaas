<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Accounting\Database\Factories\PurchaseBillLineFactory;

#[Fillable([
    'purchase_bill_id',
    'line_number',
    'description',
    'quantity',
    'unit_price',
    'discount_amount',
    'tax_rate',
    'tax_amount',
    'subtotal',
    'total',
    'debit_account_id',
    'tax_account_id',
])]
#[Hidden([
    'id',
])]
class PurchaseBillLine extends Model
{
    use HasFactory;

    protected $table = 'accounting_purchase_bill_lines';

    protected static function newFactory(): PurchaseBillLineFactory
    {
        return PurchaseBillLineFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $line): void {
            $line->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'discount_amount' => 'decimal:6',
            'tax_rate' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'subtotal' => 'decimal:6',
            'total' => 'decimal:6',
        ];
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'debit_account_id'
        );
    }

    public function taxAccount(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'tax_account_id'
        );
    }
}
