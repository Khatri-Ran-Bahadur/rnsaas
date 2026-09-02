<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Modules\Tenancy\Application\Actions\Membership\SuspendTenantMemberAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class SuspendTenantMemberActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_suspends_an_active_membership(): void
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

        $result = app(SuspendTenantMemberAction::class)->execute(
            membership: $membership,
            suspendedBy: $admin,
        );

        $this->assertSame(
            TenantMembershipStatus::Suspended,
            $result->status,
        );

        $this->assertNotNull($result->suspended_at);

        $this->assertSame(
            $admin->id,
            $result->suspended_by,
        );

        $this->assertSame(2, $result->version);

        $this->assertDatabaseHas('tenant_user', [
            'id' => $membership->id,
            'status' => TenantMembershipStatus::Suspended->value,
            'suspended_by' => $admin->id,
        ]);
    }

    public function test_suspending_an_already_suspended_membership_is_idempotent(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $admin = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Suspended,
            'suspended_at' => now(),
            'suspended_by' => $admin->id,
            'settings' => [],
            'version' => 3,
        ]);

        $result = app(SuspendTenantMemberAction::class)->execute(
            membership: $membership,
            suspendedBy: $admin,
        );

        $this->assertSame(
            TenantMembershipStatus::Suspended,
            $result->status,
        );

        $this->assertSame(3, $result->version);
    }

    public function test_invited_membership_cannot_be_suspended(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $admin = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Invited,
            'invited_at' => now(),
            'settings' => [],
            'version' => 1,
        ]);

        $this->expectException(LogicException::class);

        app(SuspendTenantMemberAction::class)->execute(
            membership: $membership,
            suspendedBy: $admin,
        );
    }
}
