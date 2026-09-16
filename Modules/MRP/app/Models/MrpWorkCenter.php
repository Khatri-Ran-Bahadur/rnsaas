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
    'code',
    'name',
    'branch',
    'type',
    'capacity_hours_per_day',
    'hourly_rate',
    'hourly_cost',
    'efficiency_percentage',
    'oee_percentage',
    'utilization_percent',
    'status',
    'machines_count',
])]
class MrpWorkCenter extends Model
{
    use HasFactory;

    protected $table = 'mrp_work_centers';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'capacity_hours_per_day' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'hourly_cost' => 'decimal:2',
            'efficiency_percentage' => 'integer',
            'oee_percentage' => 'integer',
            'utilization_percent' => 'integer',
            'machines_count' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MrpWorkOrder::class, 'work_center_id');
    }
}
