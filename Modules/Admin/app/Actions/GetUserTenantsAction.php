<?php

namespace Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;

final class GetUserTenantsAction
{
    public function handle(User $user): Collection
    {
        return $user->tenants()
            ->wherePivot(
                'status',
                TenantMembershipStatus::Active->value,
            )
            ->orderBy('tenants.name')
            ->get([
                'tenants.id',
                'tenants.public_id',
                'tenants.name',
                'tenants.slug',
            ]);
    }
}
