<?php

namespace Modules\Tenancy\Application\Actions\Role;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantRole;

final class CreateTenantRoleAction
{
    /**
     * @param  array<string>  $permissions
     */
    public function execute(
        Tenant $tenant,
        string $name,
        ?string $description = null,
        array $permissions = [],
        bool $isActive = true,
    ): TenantRole {
        return DB::transaction(function () use ($tenant, $name, $description, $permissions, $isActive): TenantRole {
            $baseSlug = Str::slug($name);
            $slug = $baseSlug;
            $counter = 1;

            while (TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $role = TenantRole::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'is_system' => false,
                'is_active' => $isActive,
            ]);

            $role->syncPermissions($permissions);

            return $role->fresh(['permissions']);
        });
    }
}
