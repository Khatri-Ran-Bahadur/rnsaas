<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'name',
    'description',
    'timezone',
    'default_start_time',
    'default_end_time',
    'break_minutes',
    'late_grace_minutes',
    'early_leave_grace_minutes',
    'is_active',
])]
class WorkSchedule extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'break_minutes' => 'integer',
            'late_grace_minutes' => 'integer',
            'early_leave_grace_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkSchedule $schedule): void {
            $schedule->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class);
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
