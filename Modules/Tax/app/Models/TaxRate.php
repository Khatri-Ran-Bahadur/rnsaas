<?php

namespace Modules\Tax\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'tax_type_id',
    'name',
    'code',
    'tax_category',
    'rate_type',
    'rate',
    'fixed_amount',
    'effective_from',
    'effective_until',
    'timeline_status',
    'country',
    'region',
    'is_recoverable',
    'is_compound',
    'description',
])]
class TaxRate extends Model
{
    use HasFactory;

    protected $table = 'tax_rates';

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'fixed_amount' => 'decimal:2',
            'is_recoverable' => 'boolean',
            'is_compound' => 'boolean',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class, 'tax_type_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TaxRule::class, 'applied_tax_rate_id');
    }
}
