<?php

namespace Modules\MRP\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'bom_id',
    'name',
    'sku',
    'quantity',
    'unit',
    'unit_cost',
    'scrap_percentage',
    'is_subassembly',
    'subassembly_bom_id',
])]
class MrpBomItem extends Model
{
    use HasFactory;

    protected $table = 'mrp_bom_items';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'scrap_percentage' => 'decimal:2',
            'is_subassembly' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(MrpBom::class, 'bom_id');
    }

    public function subassemblyBom(): BelongsTo
    {
        return $this->belongsTo(MrpBom::class, 'subassembly_bom_id');
    }
}
