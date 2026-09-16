<?php

namespace Modules\Payroll\Models;

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
    'code',
    'type',
    'calculation_type',
    'default_amount',
    'percentage_rate',
    'is_taxable',
    'is_statutory',
    'is_active',
    'sort_order',
])]
class PayrollComponent extends Model
{
    use HasFactory;

    protected $table = 'payroll_components';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'code',
        'type',
        'calculation_type',
        'default_amount',
        'percentage_rate',
        'is_taxable',
        'is_statutory',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'percentage_rate' => 'decimal:2',
            'is_taxable' => 'boolean',
            'is_statutory' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
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
}
