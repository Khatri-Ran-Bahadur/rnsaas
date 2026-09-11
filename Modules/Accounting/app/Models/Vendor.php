<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Modules\Accounting\Database\Factories\VendorFactory;
use Modules\Accounting\Domain\Enums\VendorStatus;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'vendor_code',
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
    'payable_account_id',
    'credit_limit',
    'payment_terms_days',
    'status',
    'created_by',
    'updated_by',
])]
#[Hidden([
    'id',
])]
class Vendor extends Model
{
    use HasFactory;

    protected $table = 'accounting_vendors';

    protected static function newFactory(): VendorFactory
    {
        return VendorFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $vendor): void {
            $vendor->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'credit_limit' => 'decimal:6',
            'payment_terms_days' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(
            AccountingAttachment::class,
            'attachable'
        );
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', VendorStatus::ACTIVE);
    }
}
