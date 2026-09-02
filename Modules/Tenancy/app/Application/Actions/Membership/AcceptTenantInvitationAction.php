<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;

final class AcceptTenantInvitationAction
{
    public function execute(
        TenantMembership $membership,
    ): TenantMembership {
        return DB::transaction(function () use ($membership): TenantMembership {
            $membership->refresh();

            if ($membership->status === TenantMembershipStatus::Active) {
                return $membership;
            }

            if ($membership->status !== TenantMembershipStatus::Invited) {
                throw new LogicException(
                    'Only an invited membership can be accepted.',
                );
            }

            $membership->update([
                'status' => TenantMembershipStatus::Active,
                'joined_at' => now(),
                'version' => $membership->version + 1,
            ]);

            return $membership->fresh();
        });
    }
}
