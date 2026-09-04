<?php

namespace Modules\SuperAdmin\Actions\Roles;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Contracts\AuditLogger;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreatePlatformRoleAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(
        string $name,
        array $permissionNames = [],
        ?object $actor = null,
    ): Role {
        return DB::transaction(function () use (
            $name,
            $permissionNames,
            $actor,
        ): Role {
            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissionNames)
                ->get();

            $role = Role::create([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);

            $this->auditLogger->record(
                event: 'platform.role.created',
                actor: $actor,
                auditable: $role,
                newValues: [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $permissions
                        ->pluck('name')
                        ->values()
                        ->all(),
                ],
            );

            return $role->fresh(['permissions']);
        });
    }
}
