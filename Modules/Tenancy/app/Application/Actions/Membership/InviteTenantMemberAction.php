<?php

namespace Modules\Tenancy\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;

final class InviteTenantMemberAction
{
    public function execute(
        Tenant $tenant,
        User $user,
        User $invitedBy,
        TenantRole|int|null $role = null,
    ): TenantMembership {
        return DB::transaction(function () use (
            $tenant,
            $user,
            $invitedBy,
            $role,
        ): TenantMembership {
            $roleId = $role instanceof TenantRole ? $role->id : $role;

            if (! $roleId) {
                $roleId = TenantRole::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('slug', 'staff')
                    ->value('id');
            }

            $membership = TenantMembership::query()->where('tenant_id', $tenant->getKey())
                ->where('user_id', $user->getKey())
                ->first();

            if (! $membership) {
                $membership = TenantMembership::create([
                    'tenant_id' => $tenant->getKey(),
                    'user_id' => $user->getKey(),
                    'role_id' => $roleId,
                    'status' => TenantMembershipStatus::Invited,
                    'invited_at' => now(),
                    'invited_by' => $invitedBy->getKey(),
                    'invitation_token' => Str::random(40),
                    'expires_at' => now()->addDays(7),
                    'settings' => [],
                    'version' => 1,
                ]);
            } else {
                $membership->update([
                    'role_id' => $roleId ?? $membership->role_id,
                    'status' => TenantMembershipStatus::Invited,
                    'invited_at' => now(),
                    'invited_by' => $invitedBy->getKey(),
                    'invitation_token' => Str::random(40),
                    'expires_at' => now()->addDays(7),
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'version' => $membership->version + 1,
                ]);
            }

            return $membership->fresh(['role', 'user']);
        });
    }
}
