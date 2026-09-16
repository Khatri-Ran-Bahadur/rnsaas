<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollRun;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionGatingAndPayrollRunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    }

    public function test_payroll_runs_create_page_auto_provisions_group_and_renders(): void
    {
        $tenant = Tenant::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Payroll Test Org',
            'slug' => 'payroll-test-org',
            'status' => TenantStatus::Active,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, [
            'status' => TenantMembershipStatus::Active->value,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->get(route('admin.payroll.runs.create'));

        $response->assertStatus(200);

        // Verify default group was automatically provisioned
        $this->assertDatabaseHas('payroll_groups', [
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
    }

    public function test_payroll_runs_store_accepts_parameters_and_creates_record(): void
    {
        $tenant = Tenant::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Payroll Run Org',
            'slug' => 'payroll-run-org',
            'status' => TenantStatus::Active,
        ]);

        $group = PayrollGroup::create([
            'tenant_id' => $tenant->id,
            'name' => 'HQ Staff',
            'code' => 'HQ-001',
            'cycle' => 'monthly',
            'pay_day' => 28,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, [
            'status' => TenantMembershipStatus::Active->value,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->post(route('admin.payroll.runs.store'), [
                'group_id' => $group->id,
                'title' => 'September 2026 Monthly Run',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'pay_date' => '2026-10-05',
            ]);

        $run = PayrollRun::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($run);
        $this->assertEquals('September 2026 Monthly Run', $run->period_name);
        $this->assertEquals('draft', $run->status);
        $response->assertRedirect(route('admin.payroll.runs.show', $run->id));
    }

    public function test_unsubscribed_tenant_redirects_to_plans_when_enforced(): void
    {
        $tenant = Tenant::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'No Sub Org',
            'slug' => 'no-sub-org',
            'status' => TenantStatus::Active,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, [
            'status' => TenantMembershipStatus::Active->value,
        ]);

        // When subscription is enforced
        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->withHeaders(['X-Enforce-Subscription-Test' => 'true'])
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.subscription.plans'));
    }

    public function test_tenant_with_pending_approval_redirects_to_subscription_portal_when_enforced(): void
    {
        $tenant = Tenant::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Pending Approval Org',
            'slug' => 'pending-approval-org',
            'status' => TenantStatus::Active,
        ]);

        $plan = Plan::create([
            'public_id' => (string) Str::ulid(),
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-test',
            'price' => 299,
            'currency' => 'MYR',
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        TenantSubscription::create([
            'public_id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
            'starts_at' => now(),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays(30),
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, [
            'status' => TenantMembershipStatus::Active->value,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->withHeaders(['X-Enforce-Subscription-Test' => 'true'])
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.subscription.index'));
    }

    public function test_active_subscribed_tenant_can_access_dashboard_normally(): void
    {
        $tenant = Tenant::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Active Sub Org',
            'slug' => 'active-sub-org',
            'status' => TenantStatus::Active,
        ]);

        $plan = Plan::create([
            'public_id' => (string) Str::ulid(),
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-test-2',
            'price' => 299,
            'currency' => 'MYR',
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        TenantSubscription::create([
            'public_id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays(30),
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, [
            'status' => TenantMembershipStatus::Active->value,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->withHeaders(['X-Enforce-Subscription-Test' => 'true'])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }
}
