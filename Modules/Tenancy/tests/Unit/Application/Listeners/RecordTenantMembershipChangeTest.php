<?php

namespace Modules\Tenancy\Tests\Unit\Application\Listeners;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Audit\Models\AuditLog;
use Modules\Tenancy\Application\Listeners\RecordTenantMembershipChange;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Tests\TestCase;

class RecordTenantMembershipChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_a_membership_status_change(): void
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

        $event = new TenantMembershipStatusChanged(
            membership: $membership,
            oldStatus: TenantMembershipStatus::Active,
            newStatus: TenantMembershipStatus::Suspended,
            changedBy: $admin,
        );

        app(RecordTenantMembershipChange::class)->handle($event);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_type' => $admin->getMorphClass(),
            'actor_id' => $admin->id,
            'event' => 'membership.suspended',
            'auditable_type' => $membership->getMorphClass(),
            'auditable_id' => $membership->id,
        ]);

        $audit = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame(
            ['status' => 'active'],
            $audit->old_values,
        );

        $this->assertSame(
            ['status' => 'suspended'],
            $audit->new_values,
        );

        $this->assertSame(
            'tenancy',
            $audit->metadata['module'],
        );
    }
}
