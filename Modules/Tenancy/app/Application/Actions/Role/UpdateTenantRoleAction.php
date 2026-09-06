<?php

namespace Modules\Tenancy\Application\Actions\Role;

use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Models\TenantRole;

final class UpdateTenantRoleAction
{
    public function __construct(
        private readonly OrganizationAuthorizationService $authorizationService,
    ) {}

    /**
     * @param  array<string>|null  $permissions
     */
    public function execute(
        TenantRole $role,
        string $name,
        ?string $description = null,
        ?array $permissions = null,
        ?bool $isActive = null,
    ): TenantRole {
        return DB::transaction(function () use ($role, $name, $description, $permissions, $isActive): TenantRole {
            $updates = [];

            if (! $role->is_system) {
                $updates['name'] = $name;
                if ($isActive !== null) {
                    $updates['is_active'] = $isActive;
                }
            }

            $updates['description'] = $description;

            $role->update($updates);

            if ($permissions !== null) {
                $role->syncPermissions($permissions);
            }

            $this->authorizationService->invalidateRole($role->tenant_id, $role->id);

            return $role->fresh(['permissions']);
        });
    }
}
