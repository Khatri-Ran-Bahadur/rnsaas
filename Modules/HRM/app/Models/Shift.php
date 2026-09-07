<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\HRM\database\factories\ShiftFactory;
use Modules\Tenancy\Models\Tenant;

/** @use HasFactory<ShiftFactory> */
#[Fillable([
    'public_id',
    'tenant_id',
    'name',
    'code',
    'description',
    'start_time',
    'end_time',
    'break_minutes',
    'late_grace_minutes',
    'early_leave_grace_minutes',
    'is_overnight',
    'is_active',
])]
class Shift extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'late_grace_minutes' => 'integer',
            'early_leave_grace_minutes' => 'integer',
            'is_overnight' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Shift $shift): void {
            $shift->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    #[Scope]
    protected function forTenant(
        Builder $query,
        int $tenantId
    ): void {
        $query->where('tenant_id', $tenantId);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
