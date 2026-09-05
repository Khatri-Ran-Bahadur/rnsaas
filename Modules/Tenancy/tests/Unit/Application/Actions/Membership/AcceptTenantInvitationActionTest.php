<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions\Membership;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use Modules\Tenancy\Application\Actions\Membership\AcceptTenantInvitationAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class AcceptTenantInvitationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_an_invitation(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $acceptedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Invited,
            'version' => 1,
        ]);

        $result = app(AcceptTenantInvitationAction::class)->execute(
            membership: $membership,
            acceptedBy: $acceptedBy,
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

        $this->assertNotNull(
            $result->joined_at,
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
                $acceptedBy,
            ): bool {
                return $event->membership->is($membership)
                    && $event->oldStatus === TenantMembershipStatus::Invited
                    && $event->newStatus === TenantMembershipStatus::Active
                    && $event->changedBy->is($acceptedBy);
            },
        );
    }

    public function test_accepting_an_active_membership_is_idempotent(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $acceptedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Active,
            'version' => 5,
        ]);

        $result = app(AcceptTenantInvitationAction::class)->execute(
            membership: $membership,
            acceptedBy: $acceptedBy,
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

    public function test_non_invited_membership_cannot_be_accepted(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create();

        $acceptedBy = User::factory()->create();

        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::Suspended,
            'version' => 1,
        ]);

        $this->expectException(LogicException::class);

        app(AcceptTenantInvitationAction::class)->execute(
            membership: $membership,
            acceptedBy: $acceptedBy,
        );
    }
}
