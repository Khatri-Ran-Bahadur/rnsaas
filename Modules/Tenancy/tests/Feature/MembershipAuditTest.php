<?php

namespace Modules\Tenancy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Audit\Models\AuditLog;
use Modules\Tenancy\Application\Actions\Membership\SuspendTenantMemberAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class MembershipAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspending_membership_creates_an_audit_log(): void
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

        app(SuspendTenantMemberAction::class)->execute(
            membership: $membership,
            suspendedBy: $admin,
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $admin->id,
            'event' => 'membership.suspended',
            'auditable_id' => $membership->id,
        ]);

        $audit = AuditLog::query()
            ->where('event', 'membership.suspended')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            ['status' => 'active'],
            $audit->old_values,
        );

        $this->assertSame(
            ['status' => 'suspended'],
            $audit->new_values,
        );
    }
}
