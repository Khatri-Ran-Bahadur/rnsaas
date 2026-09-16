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
    'wo_number',
    'bom_id',
    'work_center_id',
    'product_name',
    'product_sku',
    'bom_version',
    'planned_qty',
    'produced_qty',
    'rejected_qty',
    'unit',
    'branch',
    'priority',
    'status',
    'progress_percent',
    'start_date',
    'due_date',
    'completed_at',
    'assigned_to',
    'notes',
])]
class MrpWorkOrder extends Model
{
    use HasFactory;

    protected $table = 'mrp_work_orders';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'planned_qty' => 'decimal:2',
            'produced_qty' => 'decimal:2',
            'rejected_qty' => 'decimal:2',
            'progress_percent' => 'integer',
            'start_date' => 'datetime',
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
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

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(MrpWorkCenter::class, 'work_center_id');
    }

    public function qualityInspections(): HasMany
    {
        return $this->hasMany(MrpQualityInspection::class, 'work_order_id');
    }

    public function materialIssues(): HasMany
    {
        return $this->hasMany(MrpMaterialIssue::class, 'work_order_id');
    }

    public function finishedGoods(): HasMany
    {
        return $this->hasMany(MrpFinishedGood::class, 'work_order_id');
    }
}
