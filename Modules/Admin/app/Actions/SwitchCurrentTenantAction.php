<?php

namespace Modules\Admin\Actions;

use App\Models\User;
use LogicException;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;

final class SwitchCurrentTenantAction
{
    public function handle(User $user, Tenant $tenant): Tenant
    {
        $membership = $user->tenants()
            ->whereKey($tenant->getKey())
            ->wherePivot(
                'status',
                TenantMembershipStatus::Active->value,
            )
            ->first();

        if ($membership === null) {
            throw new LogicException(
                'You do not have access to this organization.',
            );
        }

        return $membership;
    }
}
