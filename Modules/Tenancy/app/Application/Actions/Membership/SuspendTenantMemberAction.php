<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;
use Modules\Tenancy\Domain\Exceptions\TenantMembershipConcurrencyException;
use Modules\Tenancy\Models\TenantMembership;

final class SuspendTenantMemberAction
{
    public function execute(
        TenantMembership $membership,
        User $suspendedBy,
    ): TenantMembership {
        return DB::transaction(function () use (
            $membership,
            $suspendedBy,
        ): TenantMembership {
            $membership->refresh();

            if ($membership->status === TenantMembershipStatus::Suspended) {
                return $membership;
            }

            if ($membership->status !== TenantMembershipStatus::Active) {
                throw new LogicException(
                    'Only an active membership can be suspended.',
                );
            }

            $oldStatus = $membership->status;
            $currentVersion = $membership->version;

            $updated = TenantMembership::query()
                ->whereKey($membership->getKey())
                ->where('version', $currentVersion)
                ->update([
                    'status' => TenantMembershipStatus::Suspended,
                    'suspended_at' => now(),
                    'suspended_by' => $suspendedBy->id,
                    'version' => $currentVersion + 1,
                ]);

            if ($updated !== 1) {
                throw new TenantMembershipConcurrencyException();
            }

            $membership->refresh();

            TenantMembershipStatusChanged::dispatch(
                $membership,
                $oldStatus,
                TenantMembershipStatus::Suspended,
                $suspendedBy,
            );

            return $membership;
        });
    }
}