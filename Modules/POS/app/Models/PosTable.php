<?php

namespace Modules\POS\Models;

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
    'table_number',
    'name',
    'section',
    'capacity',
    'shape',
    'status',
    'x_pos',
    'y_pos',
    'width',
    'height',
    'current_order_ref',
    'current_order_total',
    'occupied_minutes',
    'assigned_server',
])]
class PosTable extends Model
{
    use HasFactory;

    protected $table = 'pos_tables';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'x_pos' => 'integer',
            'y_pos' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'current_order_total' => 'decimal:2',
            'occupied_minutes' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'table_id');
    }
}
