<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenancy\Domain\Contracts\BelongsToTenant;

class TenantRole extends Model implements BelongsToTenant
{
    use HasFactory;

    protected $table = 'tenant_roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getTenantId(): int
    {
        return (int) $this->tenant_id;
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(TenantRolePermission::class, 'tenant_role_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TenantMembership::class, 'role_id');
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasPermission(string $permission): bool
    {
        // System admin roles have all permissions by default
        if ($this->is_system && $this->slug === 'admin') {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('permission', $permission);
        }

        return $this->permissions()->where('permission', $permission)->exists();
    }

    /**
     * Synchronize permissions for this tenant role.
     *
     * @param  array<string>  $permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions()->delete();

        $rows = array_map(fn (string $permission) => [
            'tenant_role_id' => $this->id,
            'permission' => $permission,
            'created_at' => now(),
            'updated_at' => now(),
        ], array_unique($permissions));

        if (! empty($rows)) {
            TenantRolePermission::query()->insert($rows);
        }
    }
}
