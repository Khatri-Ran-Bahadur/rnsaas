<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Application\Actions\Membership\RevokeTenantMemberAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class RevokeTenantMemberActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_revokes_an_active_membership(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Active,
            'joined_at' => now(),
            'settings' => [],
            'version' => 1,
        ]);

        $result = app(RevokeTenantMemberAction::class)->execute(
            membership: $membership,
            revokedBy: $admin->id,
        );

        $this->assertSame(
            TenantMembershipStatus::Revoked,
            $result->status,
        );

        $this->assertNotNull($result->revoked_at);

        $this->assertSame(
            $admin->id,
            $result->revoked_by,
        );

        $this->assertSame(2, $result->version);

        $this->assertDatabaseHas('tenant_user', [
            'id' => $membership->id,
            'status' => TenantMembershipStatus::Revoked->value,
            'revoked_by' => $admin->id,
        ]);
    }

    public function test_revoking_an_already_revoked_membership_is_idempotent(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Revoked,
            'revoked_at' => now(),
            'revoked_by' => $admin->id,
            'settings' => [],
            'version' => 4,
        ]);

        $result = app(RevokeTenantMemberAction::class)->execute(
            membership: $membership,
            revokedBy: $admin->id,
        );

        $this->assertSame(
            TenantMembershipStatus::Revoked,
            $result->status,
        );

        $this->assertSame(4, $result->version);
    }

    public function test_it_can_revoke_an_invited_membership(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Invited,
            'invited_at' => now(),
            'invited_by' => $admin->id,
            'settings' => [],
            'version' => 1,
        ]);

        $result = app(RevokeTenantMemberAction::class)->execute(
            membership: $membership,
            revokedBy: $admin->id,
        );

        $this->assertSame(
            TenantMembershipStatus::Revoked,
            $result->status,
        );
    }
}
