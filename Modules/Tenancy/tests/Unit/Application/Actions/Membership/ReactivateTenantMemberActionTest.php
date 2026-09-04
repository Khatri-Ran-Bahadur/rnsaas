<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use Modules\Tenancy\Application\Actions\Membership\ReactivateTenantMemberAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class ReactivateTenantMemberActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reactivates_a_suspended_membership(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $reactivatedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Suspended,
            'version' => 1,
        ]);

        $result = app(ReactivateTenantMemberAction::class)->execute(
            membership: $membership,
            reactivatedBy: $reactivatedBy,
        );

        $this->assertInstanceOf(
            TenantMembership::class,
            $result,
        );

        $this->assertSame(
            TenantMembershipStatus::Active,
            $result->status,
        );

        $this->assertSame(
            2,
            $result->version,
        );

        $this->assertNull(
            $result->suspended_at,
        );

        $this->assertNull(
            $result->suspended_by,
        );

        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Active->value,
            'version' => 2,
        ]);

        Event::assertDispatched(
            TenantMembershipStatusChanged::class,
            function (TenantMembershipStatusChanged $event) use (
                $membership,
                $reactivatedBy,
            ): bool {
                return $event->membership->is($membership)
                    && $event->oldStatus === TenantMembershipStatus::Suspended
                    && $event->newStatus === TenantMembershipStatus::Active
                    && $event->changedBy->is($reactivatedBy);
            },
        );
    }

    public function test_active_membership_is_idempotent(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $reactivatedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Active,
            'version' => 5,
        ]);

        $result = app(ReactivateTenantMemberAction::class)->execute(
            membership: $membership,
            reactivatedBy: $reactivatedBy,
        );

        $this->assertSame(
            TenantMembershipStatus::Active,
            $result->status,
        );

        $this->assertSame(
            5,
            $result->version,
        );

        Event::assertNotDispatched(
            TenantMembershipStatusChanged::class,
        );
    }

    public function test_invited_membership_cannot_be_reactivated(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $reactivatedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Invited,
            'version' => 1,
        ]);

        $this->expectException(LogicException::class);

        app(ReactivateTenantMemberAction::class)->execute(
            membership: $membership,
            reactivatedBy: $reactivatedBy,
        );
    }
}