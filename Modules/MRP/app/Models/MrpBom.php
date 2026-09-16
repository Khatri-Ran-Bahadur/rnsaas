<?php

namespace Modules\MRP\Models;

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
    'bom_number',
    'product_name',
    'sku',
    'bom_type',
    'version',
    'output_qty',
    'unit',
    'estimated_cost',
    'unit_cost',
    'status',
    'is_active',
    'is_default',
    'routing_name',
    'levels_count',
    'effective_from',
    'effective_to',
    'created_by',
    'notes',
])]
class MrpBom extends Model
{
    use HasFactory;

    protected $table = 'mrp_boms';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'output_qty' => 'decimal:2',
            'estimated_cost' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'levels_count' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MrpBomItem::class, 'bom_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MrpWorkOrder::class, 'bom_id');
    }
}
