<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use Modules\Tenancy\Application\Actions\Membership\RevokeTenantMemberAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class RevokeTenantMemberActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_revokes_an_active_membership(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $revokedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Active,
            'version' => 1,
        ]);

        $result = app(RevokeTenantMemberAction::class)->execute(
            membership: $membership,
            revokedBy: $revokedBy,
        );

        $this->assertInstanceOf(
            TenantMembership::class,
            $result,
        );

        $this->assertSame(
            TenantMembershipStatus::Revoked,
            $result->status,
        );

        $this->assertSame(
            2,
            $result->version,
        );

        $this->assertNotNull(
            $result->revoked_at,
        );

        $this->assertSame(
            $revokedBy->id,
            $result->revoked_by,
        );

        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Revoked->value,
            'version' => 2,
            'revoked_by' => $revokedBy->id,
        ]);

        Event::assertDispatched(
            TenantMembershipStatusChanged::class,
            function (TenantMembershipStatusChanged $event) use (
                $membership,
                $revokedBy,
            ): bool {
                return $event->membership->is($membership)
                    && $event->oldStatus === TenantMembershipStatus::Active
                    && $event->newStatus === TenantMembershipStatus::Revoked
                    && $event->changedBy->is($revokedBy);
            },
        );
    }

    public function test_revoking_an_already_revoked_membership_is_idempotent(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $revokedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Revoked,
            'version' => 5,
            'revoked_at' => now()->subDay(),
            'revoked_by' => $revokedBy->id,
        ]);

        $result = app(RevokeTenantMemberAction::class)->execute(
            membership: $membership,
            revokedBy: $revokedBy,
        );

        $this->assertSame(
            TenantMembershipStatus::Revoked,
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

    public function test_it_can_revoke_an_invited_membership(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $revokedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Invited,
            'version' => 1,
        ]);

        $result = app(RevokeTenantMemberAction::class)->execute(
            membership: $membership,
            revokedBy: $revokedBy,
        );

        $this->assertSame(
            TenantMembershipStatus::Revoked,
            $result->status,
        );

        $this->assertSame(
            2,
            $result->version,
        );

        Event::assertDispatched(
            TenantMembershipStatusChanged::class,
            function (TenantMembershipStatusChanged $event) use (
                $membership,
                $revokedBy,
            ): bool {
                return $event->membership->is($membership)
                    && $event->oldStatus === TenantMembershipStatus::Invited
                    && $event->newStatus === TenantMembershipStatus::Revoked
                    && $event->changedBy->is($revokedBy);
            },
        );
    }
}