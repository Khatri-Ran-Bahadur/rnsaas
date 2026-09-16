<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'branch_id',
    'category_id',
    'unit_id',
    'name',
    'brand_name',
    'sku',
    'barcode',
    'type',
    'selling_price',
    'cost_price',
    'on_hand_stock',
    'reserved_stock',
    'reorder_point',
    'status',
    'is_pos_available',
    'is_favorite',
    'track_stock',
    'tax_rate',
    'modifier_groups',
])]
class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'on_hand_stock' => 'decimal:2',
            'reserved_stock' => 'decimal:2',
            'reorder_point' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_pos_available' => 'boolean',
            'is_favorite' => 'boolean',
            'track_stock' => 'boolean',
            'modifier_groups' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InventoryStockAdjustment::class, 'item_id');
    }
}
