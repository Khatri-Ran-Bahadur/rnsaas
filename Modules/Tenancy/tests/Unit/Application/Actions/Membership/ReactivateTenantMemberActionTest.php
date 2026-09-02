<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Modules\Tenancy\Application\Actions\Membership\ReactivateTenantMemberAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class ReactivateTenantMemberActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reactivates_a_suspended_membership(): void
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
            'version' => 2,
        ]);

        $result = app(ReactivateTenantMemberAction::class)
            ->execute($membership);

        $this->assertSame(
            TenantMembershipStatus::Active,
            $result->status,
        );

        $this->assertNull($result->suspended_at);

        $this->assertNull($result->suspended_by);

        $this->assertSame(3, $result->version);
    }

    public function test_active_membership_is_idempotent(): void
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

        $result = app(ReactivateTenantMemberAction::class)
            ->execute($membership);

        $this->assertSame(
            TenantMembershipStatus::Active,
            $result->status,
        );

        $this->assertSame(5, $result->version);
    }

    public function test_invited_membership_cannot_be_reactivated(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Invited,
            'invited_at' => now(),
            'settings' => [],
            'version' => 1,
        ]);

        $this->expectException(LogicException::class);

        app(ReactivateTenantMemberAction::class)
            ->execute($membership);
    }
}
