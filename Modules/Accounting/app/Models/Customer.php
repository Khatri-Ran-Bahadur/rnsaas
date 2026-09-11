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
use Modules\Accounting\Database\Factories\CustomerFactory;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'customer_code',
    'name',
    'email',
    'phone',
    'tax_number',
    'billing_address_line_1',
    'billing_address_line_2',
    'billing_city',
    'billing_state',
    'billing_postcode',
    'billing_country',
    'receivable_account_id',
    'credit_limit',
    'payment_terms_days',
    'is_active',
    'status',
    'created_by',
])]
#[Hidden([
    'id',
])]
class Customer extends Model
{
    use HasFactory;

    protected $table = 'accounting_customers';

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:4',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            $customer->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'customer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'customer_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(AccountingAttachment::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
