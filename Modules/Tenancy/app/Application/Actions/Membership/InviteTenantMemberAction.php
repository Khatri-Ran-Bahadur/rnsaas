<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

final class InviteTenantMemberAction
{
    public function execute(
        Tenant $tenant,
        User $user,
        User $invitedBy,
    ): TenantMembership {
        return DB::transaction(function () use (
            $tenant,
            $user,
            $invitedBy,
        ): TenantMembership {
            $membership = TenantMembership::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'status' => TenantMembershipStatus::Invited,
                    'invited_at' => now(),
                    'invited_by' => $invitedBy->getKey(),
                    'settings' => [],
                    'version' => 1,
                ],
            );

            if (
                $membership->status === TenantMembershipStatus::Revoked
            ) {
                $membership->update([
                    'status' => TenantMembershipStatus::Invited,
                    'invited_at' => now(),
                    'invited_by' => $invitedBy->getKey(),
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'version' => $membership->version + 1,
                ]);
            }

            return $membership->fresh();
        });
    }
}
