<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

it('redirects unauthenticated users from admin dashboard to login', function (): void {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirect('/login');
});

it('renders the organization login page for unauthenticated visitors', function (): void {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page->component('Admin/Auth/Login')
    );
});

it('redirects authenticated superadmin from admin login to superadmin dashboard', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $response = $this->actingAs($superadmin)->get('/admin/login');

    $response->assertRedirect(route('superadmin.dashboard'));
});

it('denies access to admin dashboard for user without an active organization membership', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertForbidden();
});

it('allows user with active membership to access admin dashboard', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => 'Acme Corp',
        'status' => TenantStatus::Active,
    ]);

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('tenant.name', 'Acme Corp')
            ->has('members')
            ->has('subscription')
            ->has('organizations', 1)
    );
});

it('scopes organization members index to current active tenant', function (): void {
    $user = User::factory()->create(['name' => 'Main Admin']);
    $tenantA = Tenant::factory()->create(['status' => TenantStatus::Active]);
    $tenantB = Tenant::factory()->create(['status' => TenantStatus::Active]);

    // Member of Tenant A
    TenantMembership::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $otherMemberA = User::factory()->create(['name' => 'Team A Member']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $otherMemberA->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    // Member of Tenant B only
    $memberB = User::factory()->create(['name' => 'Team B Member']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $memberB->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($user)->get('/admin/members');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Members/Index')
            ->has('members.data', 2)
            ->where('members.data.0.name', fn ($name) => in_array($name, ['Main Admin', 'Team A Member']))
    );
});

it('allows switching organization if user belongs to multiple active organizations', function (): void {
    $user = User::factory()->create();
    $tenantA = Tenant::factory()->create(['name' => 'Org A', 'status' => TenantStatus::Active]);
    $tenantB = Tenant::factory()->create(['name' => 'Org B', 'status' => TenantStatus::Active]);

    TenantMembership::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->post(route('admin.tenant.switch', $tenantB));

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertEquals($tenantB->id, session('current_tenant_id'));
});

it('allows a superadmin to impersonate an active organization and records audit log', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $tenant = Tenant::factory()->create([
        'name' => 'Target Tenant',
        'status' => TenantStatus::Active,
    ]);

    $response = $this->actingAs($superadmin)
        ->post(route('superadmin.tenants.impersonate', $tenant));

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertEquals($tenant->id, session('impersonated_tenant_id'));
    $this->assertEquals($superadmin->id, session('impersonated_by_user_id'));

    // Check audit log
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'superadmin.tenant.impersonate',
        'tenant_id' => $tenant->id,
        'actor_id' => $superadmin->id,
    ]);

    // Now superadmin can access /admin/dashboard without having direct membership
    $dashboardResponse = $this->actingAs($superadmin)->get('/admin/dashboard');
    $dashboardResponse->assertSuccessful();
    $dashboardResponse->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('tenant.name', 'Target Tenant')
    );
});

it('prevents impersonating an inactive organization', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Suspended,
    ]);

    $response = $this->actingAs($superadmin)
        ->post(route('superadmin.tenants.impersonate', $tenant));

    $response->assertForbidden();
});

it('allows exiting impersonation mode and records audit log', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Active,
    ]);

    $response = $this->actingAs($superadmin)
        ->withSession([
            'impersonated_tenant_id' => $tenant->id,
            'impersonated_by_user_id' => $superadmin->id,
            'current_tenant_id' => $tenant->id,
        ])
        ->post(route('admin.impersonate.exit'));

    $response->assertRedirect(route('superadmin.tenancy.show', $tenant));
    $this->assertNull(session('impersonated_tenant_id'));

    // Check exit audit log
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'superadmin.tenant.impersonate_exit',
        'tenant_id' => $tenant->id,
        'actor_id' => $superadmin->id,
    ]);
});
