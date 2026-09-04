<?php

namespace Modules\SuperAdmin\Actions\Roles;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Contracts\AuditLogger;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UpdatePlatformRoleAction
{
    private const PROTECTED_ROLE = 'SuperAdmin';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(
        Role $role,
        string $name,
        array $permissionNames = [],
        ?object $actor = null,
    ): Role {
        if ($role->name === self::PROTECTED_ROLE) {
            throw new UnprocessableEntityHttpException(
                'The SuperAdmin role is protected and cannot be modified.',
            );
        }

        return DB::transaction(function () use (
            $role,
            $name,
            $permissionNames,
            $actor,
        ): Role {
            $role->loadMissing('permissions');

            $oldValues = [
                'name' => $role->name,
                'permissions' => $role->permissions
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->all(),
            ];

            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissionNames)
                ->get();

            $role->update([
                'name' => $name,
            ]);

            $role->syncPermissions($permissions);

            $newValues = [
                'name' => $role->name,
                'permissions' => $permissions
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->all(),
            ];

            $this->auditLogger->record(
                event: 'platform.role.updated',
                actor: $actor,
                auditable: $role,
                oldValues: $oldValues,
                newValues: $newValues,
            );

            return $role->fresh(['permissions']);
        });
    }
}
