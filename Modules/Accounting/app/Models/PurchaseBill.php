<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Modules\Accounting\Database\Factories\PurchaseBillFactory;
use Modules\Accounting\Domain\Enums\PurchaseBillStatus;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'vendor_id',
    'bill_number',
    'bill_date',
    'due_date',
    'currency',
    'subtotal',
    'discount_total',
    'tax_total',
    'grand_total',
    'status',
    'reference',
    'notes',
    'journal_entry_id',
    'created_by',
    'issued_by',
    'posted_by',
    'voided_by',
    'issued_at',
    'posted_at',
    'voided_at',
])]
#[Hidden([
    'id',
])]
class PurchaseBill extends Model
{
    use HasFactory;

    protected $table = 'accounting_purchase_bills';

    protected static function newFactory(): PurchaseBillFactory
    {
        return PurchaseBillFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $bill): void {
            $bill->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'status' => PurchaseBillStatus::class,
            'subtotal' => 'decimal:6',
            'discount_total' => 'decimal:6',
            'tax_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'issued_at' => 'datetime',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseBillLine::class)
            ->orderBy('line_number');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(
            AccountingAttachment::class,
            'attachable'
        );
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(
            VendorPaymentAllocation::class
        );
    }
}
