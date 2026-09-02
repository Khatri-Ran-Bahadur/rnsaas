<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;

final class ReactivateTenantMemberAction
{
    public function execute(
        TenantMembership $membership,
    ): TenantMembership {
        return DB::transaction(function () use ($membership): TenantMembership {
            $membership->refresh();

            if ($membership->status === TenantMembershipStatus::Active) {
                return $membership;
            }

            if ($membership->status !== TenantMembershipStatus::Suspended) {
                throw new LogicException(
                    'Only a suspended membership can be reactivated.',
                );
            }

            $membership->update([
                'status' => TenantMembershipStatus::Active,
                'suspended_at' => null,
                'suspended_by' => null,
                'version' => $membership->version + 1,
            ]);

            return $membership->fresh();
        });
    }
}
