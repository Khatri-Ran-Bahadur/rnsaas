<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Modules\Tenancy\Application\Actions\Membership\AcceptTenantInvitationAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class AcceptTenantInvitationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_an_invitation(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $invitedBy = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Invited,
            'invited_at' => now(),
            'invited_by' => $invitedBy->id,
            'settings' => [],
            'version' => 1,
        ]);

        $result = app(AcceptTenantInvitationAction::class)
            ->execute($membership);

        $this->assertSame(
            TenantMembershipStatus::Active,
            $result->status,
        );

        $this->assertNotNull($result->joined_at);

        $this->assertSame(2, $result->version);

        $this->assertDatabaseHas('tenant_user', [
            'id' => $membership->id,
            'status' => TenantMembershipStatus::Active->value,
        ]);
    }

    public function test_accepting_an_active_membership_is_idempotent(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Active,
            'joined_at' => now(),
            'settings' => [],
            'version' => 5,
        ]);

        $result = app(AcceptTenantInvitationAction::class)
            ->execute($membership);

        $this->assertSame(
            TenantMembershipStatus::Active,
            $result->status,
        );

        $this->assertSame(5, $result->version);
    }

    public function test_non_invited_membership_cannot_be_accepted(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Suspended,
            'settings' => [],
            'version' => 1,
        ]);

        $this->expectException(LogicException::class);

        app(AcceptTenantInvitationAction::class)
            ->execute($membership);
    }
}
