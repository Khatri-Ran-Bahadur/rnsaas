<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;
use Modules\Tenancy\Domain\Exceptions\TenantMembershipConcurrencyException;
use Modules\Tenancy\Models\TenantMembership;

final class AcceptTenantInvitationAction
{
    public function execute(
        TenantMembership $membership,
        User $acceptedBy,
    ): TenantMembership {
        return DB::transaction(function () use (
            $membership,
            $acceptedBy,
        ): TenantMembership {
            $membership->refresh();

            if ($membership->status === TenantMembershipStatus::Active) {
                return $membership;
            }

            if ($membership->status !== TenantMembershipStatus::Invited) {
                throw new LogicException(
                    'Only an invited membership can be accepted.',
                );
            }

            $oldStatus = $membership->status;
            $currentVersion = $membership->version;

            $updated = TenantMembership::query()
                ->whereKey($membership->getKey())
                ->where('version', $currentVersion)
                ->update([
                    'status' => TenantMembershipStatus::Active,
                    'joined_at' => now(),
                    'version' => $currentVersion + 1,
                ]);

            if ($updated !== 1) {
                throw new TenantMembershipConcurrencyException();
            }

            $membership->refresh();

            TenantMembershipStatusChanged::dispatch(
                $membership,
                $oldStatus,
                TenantMembershipStatus::Active,
                $acceptedBy,
            );

            return $membership;
        });
    }
}