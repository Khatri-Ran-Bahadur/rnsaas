<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;

final class RevokeTenantMemberAction
{
    public function execute(
        TenantMembership $membership,
        int $revokedBy,
    ): TenantMembership {
        return DB::transaction(function () use (
            $membership,
            $revokedBy,
        ): TenantMembership {
            $membership->refresh();

            if ($membership->status === TenantMembershipStatus::Revoked) {
                return $membership;
            }

            $membership->update([
                'status' => TenantMembershipStatus::Revoked,
                'revoked_at' => now(),
                'revoked_by' => $revokedBy,
                'version' => $membership->version + 1,
            ]);

            return $membership->fresh();
        });
    }
}
