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
    'work_order_id',
    'inspection_number',
    'type',
    'wo_number',
    'product_name',
    'item_code',
    'lot_or_batch',
    'checkpoint_name',
    'target',
    'actual',
    'sample_size',
    'inspected_qty',
    'passed_qty',
    'failed_qty',
    'status',
    'inspector',
    'inspection_date',
    'notes',
])]
class MrpQualityInspection extends Model
{
    use HasFactory;

    protected $table = 'mrp_quality_inspections';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'sample_size' => 'integer',
            'inspected_qty' => 'integer',
            'passed_qty' => 'integer',
            'failed_qty' => 'integer',
            'inspection_date' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MrpWorkOrder::class, 'work_order_id');
    }
}
