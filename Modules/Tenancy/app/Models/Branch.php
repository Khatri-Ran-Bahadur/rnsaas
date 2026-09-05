<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Tenancy\Domain\Enums\BranchStatus;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'code',
        'status',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => BranchStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::creating(function (Branch $branch): void {
            $branch->public_id ??= (string) str()->uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            BranchStatus::Active->value
        );
    }

    public function scopeForTenant(
        Builder $query,
        int $tenantId
    ): Builder {
        return $query->where('tenant_id', $tenantId);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
