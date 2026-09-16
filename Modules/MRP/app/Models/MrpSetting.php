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
    'auto_reserve_materials',
    'allow_negative_raw_materials',
    'require_qa_approval_before_fg',
    'default_scrap_percentage',
    'mrp_planning_horizon_days',
    'default_overhead_allocation_rate',
    'track_lot_genealogy',
    'require_manager_override_for_scrap',
])]
class MrpSetting extends Model
{
    use HasFactory;

    protected $table = 'mrp_settings';

    protected function casts(): array
    {
        return [
            'auto_reserve_materials' => 'boolean',
            'allow_negative_raw_materials' => 'boolean',
            'require_qa_approval_before_fg' => 'boolean',
            'default_scrap_percentage' => 'decimal:2',
            'mrp_planning_horizon_days' => 'integer',
            'default_overhead_allocation_rate' => 'decimal:2',
            'track_lot_genealogy' => 'boolean',
            'require_manager_override_for_scrap' => 'boolean',
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
