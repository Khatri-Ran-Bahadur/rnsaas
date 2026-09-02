<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Application\Actions\Membership\InviteTenantMemberAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class InviteTenantMemberActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_invites_a_user_to_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $invitedBy = User::factory()->create();

        $membership = app(InviteTenantMemberAction::class)->execute(
            tenant: $tenant,
            user: $user,
            invitedBy: $invitedBy,
        );

        $this->assertInstanceOf(
            TenantMembership::class,
            $membership,
        );

        $this->assertSame(
            $tenant->id,
            $membership->tenant_id,
        );

        $this->assertSame(
            $user->id,
            $membership->user_id,
        );

        $this->assertSame(
            TenantMembershipStatus::Invited,
            $membership->status,
        );

        $this->assertSame(
            $invitedBy->id,
            $membership->invited_by,
        );

        $this->assertNotNull($membership->invited_at);

        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Invited->value,
            'invited_by' => $invitedBy->id,
        ]);
    }

    public function test_it_does_not_create_duplicate_membership(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $invitedBy = User::factory()->create();

        $action = app(InviteTenantMemberAction::class);

        $first = $action->execute(
            tenant: $tenant,
            user: $user,
            invitedBy: $invitedBy,
        );

        $second = $action->execute(
            tenant: $tenant,
            user: $user,
            invitedBy: $invitedBy,
        );

        $this->assertSame(
            $first->id,
            $second->id,
        );

        $this->assertDatabaseCount('tenant_user', 1);
    }
}
