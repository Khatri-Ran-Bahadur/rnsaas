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
use Modules\Accounting\Database\Factories\VendorPaymentFactory;
use Modules\Accounting\Domain\Enums\VendorPaymentStatus;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'vendor_id',
    'payment_number',
    'payment_date',
    'amount',
    'currency',
    'payment_method',
    'bank_account_id',
    'reference',
    'notes',
    'status',
    'journal_entry_id',
    'created_by',
    'posted_by',
    'posted_at',
])]
#[Hidden([
    'id',
])]
class VendorPayment extends Model
{
    use HasFactory;

    protected $table = 'accounting_vendor_payments';

    protected static function newFactory(): VendorPaymentFactory
    {
        return VendorPaymentFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            $payment->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:6',
            'status' => VendorPaymentStatus::class,
            'posted_at' => 'datetime',
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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'bank_account_id'
        );
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            VendorPaymentAllocation::class
        );
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
}
