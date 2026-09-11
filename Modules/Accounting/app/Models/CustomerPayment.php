<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Modules\Accounting\Database\Factories\CustomerPaymentFactory;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'customer_id',
    'payment_number',
    'payment_date',
    'amount',
    'currency',
    'bank_account_id',
    'payment_method',
    'reference',
    'notes',
    'status',
    'journal_entry_id',
    'created_by',
])]
#[Hidden([
    'id',
])]
class CustomerPayment extends Model
{
    use HasFactory;

    protected $table = 'accounting_customer_payments';

    protected function casts(): array
    {
        return [
            'payment_date' => 'immutable_date',
            'amount' => 'decimal:4',
        ];
    }

    protected static function newFactory(): CustomerPaymentFactory
    {
        return CustomerPaymentFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            $payment->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerPaymentAllocation::class, 'customer_payment_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(AccountingAttachment::class, 'attachable');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
