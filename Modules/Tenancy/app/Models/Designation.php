<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenancy\Domain\Enums\DesignationStatus;

class Designation extends Model
{
    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => DesignationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Designation $designation): void {
            $designation->public_id ??= (string) str()->uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(TenantStaff::class);
    }

    public function scopeForTenant(
        Builder $query,
        int $tenantId
    ): Builder {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            DesignationStatus::Active->value
        );
    }
}
