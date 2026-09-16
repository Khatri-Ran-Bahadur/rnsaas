<?php

namespace Modules\Tax\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'name',
    'priority',
    'transaction_type',
    'sales_channel',
    'customer_type',
    'item_type',
    'applied_tax_rate_id',
    'tax_inclusive_mode',
    'is_active',
    'description',
])]
class TaxRule extends Model
{
    use HasFactory;

    protected $table = 'tax_rules';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'tax_inclusive_mode' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'applied_tax_rate_id');
    }
}
