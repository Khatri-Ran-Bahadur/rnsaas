<?php

namespace Modules\SuperAdmin\Actions\Roles;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Contracts\AuditLogger;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeletePlatformRoleAction
{
    private const PROTECTED_ROLE = 'SuperAdmin';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(
        Role $role,
        ?object $actor = null,
    ): void {
        if ($role->name === self::PROTECTED_ROLE) {
            throw new UnprocessableEntityHttpException(
                'The SuperAdmin role cannot be deleted.',
            );
        }

        if ($role->users()->exists()) {
            throw new UnprocessableEntityHttpException(
                'This role cannot be deleted while it is assigned to users.',
            );
        }

        DB::transaction(function () use ($role, $actor): void {
            $role->loadMissing('permissions');

            $oldValues = [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->all(),
            ];

            $this->auditLogger->record(
                event: 'platform.role.deleted',
                actor: $actor,
                auditable: $role,
                oldValues: $oldValues,
            );

            $role->delete();
        });
    }
}
