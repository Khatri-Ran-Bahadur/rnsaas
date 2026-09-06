<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Application\Actions\Membership\UpdateTenantMemberAction;
use Modules\Tenancy\Application\Actions\Role\UpdateTenantRoleAction;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Domain\Constants\OrganizationPermissions;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Models\TenantStaff;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

// Helper to create a tenant with a custom role and active membership
function createMemberWithPermissions(array $permissions, ?Tenant $tenant = null, bool $roleIsActive = true): array
{
    $tenant = $tenant ?? Tenant::factory()->create(['status' => TenantStatus::Active]);
    $user = User::factory()->create();

    $role = TenantRole::create([
        'tenant_id' => $tenant->id,
        'name' => 'Custom Role',
        'slug' => 'custom-role-'.uniqid(),
        'is_system' => false,
        'is_active' => $roleIsActive,
    ]);

    $role->syncPermissions($permissions);

    $membership = TenantMembership::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    return [$user, $tenant, $role, $membership];
}

it('allows a user with active tenant membership to access an allowed organization permission', function (): void {
    [$user, $tenant] = createMemberWithPermissions([
        OrganizationPermissions::STAFF_VIEW,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff');

    $response->assertSuccessful();
});

it('denies access with 403 when user lacks the required organization permission', function (): void {
    [$user, $tenant] = createMemberWithPermissions([
        OrganizationPermissions::DASHBOARD_VIEW,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff');

    $response->assertForbidden();
});

it('denies access with 403 when membership is suspended', function (): void {
    [$user, $tenant, $role, $membership] = createMemberWithPermissions([
        OrganizationPermissions::STAFF_VIEW,
    ]);

    $membership->update([
        'status' => TenantMembershipStatus::Suspended,
        'suspended_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff');

    $response->assertForbidden();
});

it('denies access with 403 when membership is revoked', function (): void {
    [$user, $tenant, $role, $membership] = createMemberWithPermissions([
        OrganizationPermissions::STAFF_VIEW,
    ]);

    $membership->update([
        'status' => TenantMembershipStatus::Revoked,
        'revoked_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff');

    $response->assertForbidden();
});

it('denies access with 403 when role is inactive', function (): void {
    [$user, $tenant] = createMemberWithPermissions(
        permissions: [OrganizationPermissions::STAFF_VIEW],
        roleIsActive: false,
    );

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff');

    $response->assertForbidden();
});

it('strictly isolates permissions between Tenant A and Tenant B for the same user', function (): void {
    $ram = User::factory()->create(['name' => 'Ram']);

    $tenantA = Tenant::factory()->create(['name' => 'Tenant A', 'status' => TenantStatus::Active]);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B', 'status' => TenantStatus::Active]);

    // Ram is Manager in Tenant A with staff.manage
    $managerRole = TenantRole::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Manager',
        'slug' => 'manager',
        'is_system' => false,
        'is_active' => true,
    ]);
    $managerRole->syncPermissions([
        OrganizationPermissions::STAFF_VIEW,
        OrganizationPermissions::STAFF_MANAGE,
    ]);
    TenantMembership::create([
        'tenant_id' => $tenantA->id,
        'user_id' => $ram->id,
        'role_id' => $managerRole->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    // Ram is Accountant in Tenant B WITHOUT staff.manage (only staff.view)
    $accountantRole = TenantRole::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Accountant',
        'slug' => 'accountant',
        'is_system' => false,
        'is_active' => true,
    ]);
    $accountantRole->syncPermissions([
        OrganizationPermissions::STAFF_VIEW,
    ]);
    TenantMembership::create([
        'tenant_id' => $tenantB->id,
        'user_id' => $ram->id,
        'role_id' => $accountantRole->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    // In Tenant A: Ram CAN access /admin/staff/create (requires staff.manage)
    $responseA = $this->actingAs($ram)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get('/admin/staff/create');
    $responseA->assertSuccessful();

    // In Tenant B: Ram CANNOT access /admin/staff/create (gets 403 Forbidden)
    $responseB = $this->actingAs($ram)
        ->withSession(['current_tenant_id' => $tenantB->id])
        ->get('/admin/staff/create');
    $responseB->assertForbidden();

    // Direct helper check
    expect($ram->canInTenant(OrganizationPermissions::STAFF_MANAGE, $tenantA))->toBeTrue()
        ->and($ram->canInTenant(OrganizationPermissions::STAFF_MANAGE, $tenantB))->toBeFalse();
});

it('does not leak permissions between tenants', function (): void {
    $tenantA = Tenant::factory()->create(['status' => TenantStatus::Active]);
    $tenantB = Tenant::factory()->create(['status' => TenantStatus::Active]);

    $userA = User::factory()->create();

    $roleA = TenantRole::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Admin A',
        'slug' => 'admin-a',
        'is_system' => false,
        'is_active' => true,
    ]);
    $roleA->syncPermissions([OrganizationPermissions::BRANCHES_VIEW, OrganizationPermissions::BRANCHES_MANAGE]);

    TenantMembership::create([
        'tenant_id' => $tenantA->id,
        'user_id' => $userA->id,
        'role_id' => $roleA->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    // User A has no membership in Tenant B
    expect($userA->canInTenant(OrganizationPermissions::BRANCHES_VIEW, $tenantB))->toBeFalse()
        ->and($userA->canInTenant(OrganizationPermissions::BRANCHES_VIEW, $tenantA))->toBeTrue();
});

it('invalidates relevant cache when role permissions are changed', function (): void {
    [$user, $tenant, $role] = createMemberWithPermissions([
        OrganizationPermissions::STAFF_VIEW,
    ]);

    // First visit: populates cache and allows access
    $response1 = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff');
    $response1->assertSuccessful();

    // Update role permissions removing staff.view
    app(UpdateTenantRoleAction::class)->execute(
        role: $role,
        name: $role->name,
        description: $role->description,
        permissions: [OrganizationPermissions::DASHBOARD_VIEW],
        isActive: true,
    );

    // Second visit: cache was invalidated, now denied with 403
    $response2 = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff');
    $response2->assertForbidden();
});

it('invalidates relevant cache when membership role changes', function (): void {
    [$user, $tenant, $managerRole, $membership] = createMemberWithPermissions([
        OrganizationPermissions::STAFF_VIEW,
        OrganizationPermissions::STAFF_MANAGE,
    ]);

    // User can access create staff
    $response1 = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff/create');
    $response1->assertSuccessful();

    // Create staff-only role
    $staffRole = TenantRole::create([
        'tenant_id' => $tenant->id,
        'name' => 'Staff Only',
        'slug' => 'staff-only',
        'is_system' => false,
        'is_active' => true,
    ]);
    $staffRole->syncPermissions([OrganizationPermissions::STAFF_VIEW]);

    // Update membership to staff-only role
    app(UpdateTenantMemberAction::class)->execute(
        membership: $membership,
        roleId: $staffRole->id,
        status: 'active',
        actor: $user,
    );

    // Now access to create staff is denied with 403
    $response2 = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/staff/create');
    $response2->assertForbidden();
});

it('does not repeatedly query the database on multiple permission checks in the same request', function (): void {
    [$user, $tenant] = createMemberWithPermissions([
        OrganizationPermissions::STAFF_VIEW,
        OrganizationPermissions::STAFF_MANAGE,
        OrganizationPermissions::BRANCHES_VIEW,
        OrganizationPermissions::MEMBERS_VIEW,
    ]);

    app(OrganizationAuthorizationService::class)->clearMemoryCache();

    // Resolve tenant in context
    app(CurrentTenant::class)->set($tenant);

    DB::enableQueryLog();

    // First check hits DB
    $canStaffView = $user->can('staff.view');
    expect($canStaffView)->toBeTrue();
    $firstQueryCount = count(DB::getQueryLog());
    expect($firstQueryCount)->toBeGreaterThan(0);

    // Subsequent checks for different permissions should hit runtime memory cache (0 additional queries!)
    $canStaffManage = $user->can('staff.manage');
    $canBranchesView = $user->can('branches.view');
    $canMembersView = $user->can('members.view');
    $canPayrollManage = $user->can('payroll.manage');

    expect($canStaffManage)->toBeTrue()
        ->and($canBranchesView)->toBeTrue()
        ->and($canMembersView)->toBeTrue()
        ->and($canPayrollManage)->toBeFalse();

    $secondQueryCount = count(DB::getQueryLog());
    expect($secondQueryCount)->toBe($firstQueryCount);

    DB::disableQueryLog();
});

it('enforces both capability authorization AND tenant record boundary isolation', function (): void {
    [$userA, $tenantA] = createMemberWithPermissions([
        OrganizationPermissions::STAFF_VIEW,
        OrganizationPermissions::STAFF_MANAGE,
    ]);

    $tenantB = Tenant::factory()->create(['status' => TenantStatus::Active]);
    $userB = User::factory()->create();

    $staffB = TenantStaff::factory()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $userB->id,
    ]);

    // User A has staff.manage in Tenant A, but attempts to edit Tenant B's staff record
    $response = $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/admin/staff/{$staffB->id}/edit");

    // Must be denied with 404 (or 403)
    $response->assertNotFound();
});

it('allows SuperAdmin during organization impersonation to act as organization Admin', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);

    // SuperAdmin impersonates tenant
    $response = $this->actingAs($superadmin)
        ->withSession([
            'impersonated_tenant_id' => $tenant->id,
            'impersonated_by_user_id' => $superadmin->id,
            'current_tenant_id' => $tenant->id,
        ])
        ->get('/admin/staff');

    $response->assertSuccessful();
});

it('does not allow platform Spatie permissions to leak into tenant organization permissions', function (): void {
    // Permission 'roles.view' exists on platform for SuperAdmin
    Permission::findOrCreate('roles.view', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('roles.view'); // Spatie platform permission

    $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);

    // User has custom role in Tenant WITHOUT roles.view
    $role = TenantRole::create([
        'tenant_id' => $tenant->id,
        'name' => 'Regular Member',
        'slug' => 'regular-member',
        'is_system' => false,
        'is_active' => true,
    ]);
    $role->syncPermissions([OrganizationPermissions::STAFF_VIEW]); // No roles.view!

    TenantMembership::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    // In tenant context, User attempts to access /admin/roles
    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/roles');

    // Platform permission roles.view MUST NOT leak into tenant organization roles!
    $response->assertForbidden();
});
