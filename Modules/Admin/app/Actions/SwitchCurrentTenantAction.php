<?php

namespace Modules\Admin\Actions;

use App\Models\User;
use LogicException;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;

final class SwitchCurrentTenantAction
{
    public function handle(User $user, Tenant $tenant): Tenant
    {
        $isActive = ($tenant->status instanceof TenantStatus)
            ? $tenant->status === TenantStatus::Active
            : $tenant->status === TenantStatus::Active->value;

        if (! $isActive) {
            throw new LogicException(
                'This organization is currently inactive or suspended.',
            );
        }

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
