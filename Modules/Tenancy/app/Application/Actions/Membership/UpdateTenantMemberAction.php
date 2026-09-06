<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;

final class UpdateTenantMemberAction
{
    public function __construct(
        private readonly SuspendTenantMemberAction $suspendAction,
        private readonly ReactivateTenantMemberAction $reactivateAction,
        private readonly RevokeTenantMemberAction $revokeAction,
        private readonly OrganizationAuthorizationService $authorizationService,
    ) {}

    public function execute(
        TenantMembership $membership,
        ?int $roleId = null,
        ?string $status = null,
        ?User $actor = null,
    ): TenantMembership {
        return DB::transaction(function () use ($membership, $roleId, $status, $actor): TenantMembership {
            if ($roleId !== null) {
                $roleExists = TenantRole::query()
                    ->where('tenant_id', $membership->tenant_id)
                    ->where('id', $roleId)
                    ->exists();

                if (! $roleExists) {
                    throw new InvalidArgumentException('Selected role does not belong to this organization.');
                }

                $membership->update(['role_id' => $roleId]);
            }

            if ($status !== null && $actor !== null) {
                $targetStatus = TenantMembershipStatus::tryFrom($status);
                if ($targetStatus && $targetStatus !== $membership->status) {
                    if ($targetStatus === TenantMembershipStatus::Suspended) {
                        $this->suspendAction->execute($membership, $actor);
                    } elseif ($targetStatus === TenantMembershipStatus::Active && $membership->status === TenantMembershipStatus::Suspended) {
                        $this->reactivateAction->execute($membership, $actor);
                    } elseif ($targetStatus === TenantMembershipStatus::Revoked) {
                        $this->revokeAction->execute($membership, $actor);
                    }
                }
            }

            $this->authorizationService->invalidateUser($membership->tenant_id, $membership->user_id);

            return $membership->fresh(['role', 'user', 'staff']);
        });
    }
}
