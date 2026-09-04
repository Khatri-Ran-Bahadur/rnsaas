<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;
use Modules\Tenancy\Domain\Exceptions\TenantMembershipConcurrencyException;
use Modules\Tenancy\Models\TenantMembership;

final class ReactivateTenantMemberAction
{
    public function execute(
        TenantMembership $membership,
        User $reactivatedBy,
    ): TenantMembership {
        return DB::transaction(function () use (
            $membership,
            $reactivatedBy,
        ): TenantMembership {
            $membership->refresh();

            if ($membership->status === TenantMembershipStatus::Active) {
                return $membership;
            }

            if ($membership->status !== TenantMembershipStatus::Suspended) {
                throw new LogicException(
                    'Only a suspended membership can be reactivated.',
                );
            }

            $oldStatus = $membership->status;
            $currentVersion = $membership->version;

            $updated = TenantMembership::query()
                ->whereKey($membership->getKey())
                ->where('version', $currentVersion)
                ->update([
                    'status' => TenantMembershipStatus::Active,
                    'suspended_at' => null,
                    'suspended_by' => null,
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
                $reactivatedBy,
            );

            return $membership;
        });
    }
}