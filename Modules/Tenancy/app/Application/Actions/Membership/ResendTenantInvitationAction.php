<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;

final class ResendTenantInvitationAction
{
    public function execute(TenantMembership $membership): TenantMembership
    {
        if ($membership->status !== TenantMembershipStatus::Invited) {
            throw new LogicException('Only pending invitations can be resent.');
        }

        return DB::transaction(function () use ($membership): TenantMembership {
            $membership->update([
                'invitation_token' => Str::random(40),
                'invited_at' => now(),
                'expires_at' => now()->addDays(7),
                'version' => $membership->version + 1,
            ]);

            return $membership->fresh(['role', 'user']);
        });
    }
}
