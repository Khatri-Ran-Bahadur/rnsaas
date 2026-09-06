<?php

namespace Modules\Tenancy\Application\Actions\Role;

use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Models\TenantRole;

final class DeleteTenantRoleAction
{
    public function __construct(
        private readonly OrganizationAuthorizationService $authorizationService,
    ) {}

    public function execute(TenantRole $role): void
    {
        if ($role->is_system) {
            throw new LogicException('System roles cannot be deleted.');
        }

        if ($role->members()->exists()) {
            throw new LogicException('Cannot delete a role that is currently assigned to members.');
        }

        $tenantId = $role->tenant_id;
        $roleId = $role->id;

        DB::transaction(function () use ($role): void {
            $role->permissions()->delete();
            $role->delete();
        });

        $this->authorizationService->invalidateRole($tenantId, $roleId);
    }
}
