<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;
use Modules\Tenancy\Domain\Exceptions\TenantMembershipConcurrencyException;
use Modules\Tenancy\Models\TenantMembership;

final class RevokeTenantMemberAction
{
    public function execute(
        TenantMembership $membership,
        User $revokedBy,
    ): TenantMembership {
        return DB::transaction(function () use (
            $membership,
            $revokedBy,
        ): TenantMembership {
            $membership->refresh();

            if ($membership->status === TenantMembershipStatus::Revoked) {
                return $membership;
            }

            $oldStatus = $membership->status;
            $currentVersion = $membership->version;

            $updated = TenantMembership::query()
                ->whereKey($membership->getKey())
                ->where('version', $currentVersion)
                ->update([
                    'status' => TenantMembershipStatus::Revoked,
                    'revoked_at' => now(),
                    'revoked_by' => $revokedBy->id,
                    'version' => $currentVersion + 1,
                ]);

            if ($updated !== 1) {
                throw new TenantMembershipConcurrencyException;
            }

            $membership->refresh();

            TenantMembershipStatusChanged::dispatch(
                $membership,
                $oldStatus,
                TenantMembershipStatus::Revoked,
                $revokedBy,
            );

            return $membership;
        });
    }
}
