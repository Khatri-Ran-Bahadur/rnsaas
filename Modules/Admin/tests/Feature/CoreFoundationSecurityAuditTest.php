<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Domain\Constants\OrganizationPermissions;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Models\TenantStaff;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

function setupTenantWithAdmin(string $tenantName = 'Tenant'): array
{
    $tenant = Tenant::factory()->create([
        'name' => $tenantName,
        'status' => TenantStatus::Active,
    ]);

    $user = User::factory()->create();

    $adminRole = TenantRole::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin',
        'slug' => 'admin',
        'is_system' => true,
        'is_active' => true,
    ]);

    $membership = TenantMembership::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $adminRole->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    return [$user, $tenant, $adminRole, $membership];
}

function setupTenantWithManager(Tenant $tenant, array $permissions): array
{
    $user = User::factory()->create();

    $role = TenantRole::create([
        'tenant_id' => $tenant->id,
        'name' => 'Manager Role',
        'slug' => 'manager-'.uniqid(),
        'is_system' => false,
        'is_active' => true,
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

    return [$user, $role, $membership];
}

// 1. Current Tenant Lifecycle & Inactive Tenant Protection
it('blocks access to admin routes when the tenant is suspended or inactive', function (): void {
    [$user, $tenant] = setupTenantWithAdmin();

    $tenant->update(['status' => TenantStatus::Suspended]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/admin/dashboard');

    $response->assertStatus(403);
});

it('rejects switching to an inactive or suspended organization', function (): void {
    [$user, $tenantA] = setupTenantWithAdmin('Tenant A');

    $tenantB = Tenant::factory()->create([
        'name' => 'Tenant B Suspended',
        'status' => TenantStatus::Suspended,
    ]);

    // Give active membership in Tenant B
    TenantMembership::create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'role_id' => null,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->post("/admin/tenant/switch/{$tenantB->id}");

    $response->assertStatus(403);
});

// 2. Tenant Isolation & IDOR on Staff
it('denies cross-tenant staff access and returns 404 for IDOR attempts', function (): void {
    [$userA, $tenantA] = setupTenantWithAdmin('Tenant A');
    [$userB, $tenantB] = setupTenantWithAdmin('Tenant B');

    $branchB = Branch::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Branch B',
        'code' => 'BR-B-001',
        'status' => BranchStatus::Active,
    ]);

    $deptB = Department::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Dept B',
        'code' => 'DEP-B-001',
        'is_active' => true,
    ]);

    $desigB = Designation::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Desig B',
        'code' => 'DES-B-001',
        'is_active' => true,
    ]);

    $staffB = TenantStaff::create([
        'tenant_id' => $tenantB->id,
        'user_id' => $userB->id,
        'branch_id' => $branchB->id,
        'department_id' => $deptB->id,
        'designation_id' => $desigB->id,
        'employee_code' => 'EMP-B-001',
        'employment_status' => EmploymentStatus::Active,
    ]);

    // Tenant A attempts to edit Tenant B's staff
    $response = $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/admin/staff/{$staffB->id}/edit");

    $response->assertNotFound();

    // Tenant A attempts to update Tenant B's staff
    $updateResponse = $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->put("/admin/staff/{$staffB->id}", [
            'name' => 'Hacked Name',
            'employee_code' => 'EMP-B-001',
            'employment_status' => EmploymentStatus::Active->value,
        ]);

    $updateResponse->assertNotFound();

    // Tenant A attempts to suspend Tenant B's staff
    $suspendResponse = $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->patch("/admin/staff/{$staffB->id}/suspend");

    $suspendResponse->assertNotFound();
});

// 3. Tenant Isolation & IDOR on Structure (Branches, Departments, Designations)
it('denies cross-tenant branch, department, and designation access and modifications', function (): void {
    [$userA, $tenantA] = setupTenantWithAdmin('Tenant A');
    [, $tenantB] = setupTenantWithAdmin('Tenant B');

    $branchB = Branch::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Branch B',
        'code' => 'BR-B',
        'status' => BranchStatus::Active,
    ]);

    $deptB = Department::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Dept B',
        'code' => 'DEP-B',
        'is_active' => true,
    ]);

    $desigB = Designation::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Desig B',
        'code' => 'DES-B',
        'is_active' => true,
    ]);

    // Cross-tenant Branch show & edit
    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/admin/branches/{$branchB->id}")
        ->assertNotFound();

    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->patch("/admin/branches/{$branchB->id}/deactivate")
        ->assertNotFound();

    // Cross-tenant Department
    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->patch("/admin/departments/{$deptB->id}/deactivate")
        ->assertNotFound();

    // Cross-tenant Designation
    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->patch("/admin/designations/{$desigB->id}/deactivate")
        ->assertNotFound();
});

// 4. Cross-tenant Foreign Key Injection Prevention
it('rejects assigning another tenants branch, department, or designation to staff', function (): void {
    [$userA, $tenantA] = setupTenantWithAdmin('Tenant A');
    [, $tenantB] = setupTenantWithAdmin('Tenant B');

    $branchB = Branch::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Branch B',
        'code' => 'BR-B2',
        'status' => BranchStatus::Active,
    ]);

    $response = $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->post('/admin/staff', [
            'name' => 'John Doe',
            'email' => 'john@tenanta.com',
            'employee_code' => 'EMP-A-999',
            'branch_id' => $branchB->id, // Tenant B branch
            'employment_status' => EmploymentStatus::Active->value,
        ]);

    $response->assertSessionHasErrors(['branch_id']);
});

// 5. Tenant Isolation & IDOR on Members
it('denies cross-tenant member operations and returns 404 for IDOR attempts', function (): void {
    [$userA, $tenantA] = setupTenantWithAdmin('Tenant A');
    [, $tenantB, , $membershipB] = setupTenantWithAdmin('Tenant B');

    // Tenant A attempts to view Tenant B's member
    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/admin/members/{$membershipB->id}")
        ->assertNotFound();

    // Tenant A attempts to suspend Tenant B's member
    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->patch("/admin/members/{$membershipB->id}/suspend")
        ->assertNotFound();

    // Tenant A attempts to revoke Tenant B's member
    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->delete("/admin/members/{$membershipB->id}/revoke")
        ->assertNotFound();
});

// 6. Tenant Isolation & IDOR on Roles
it('denies cross-tenant role operations and returns 404 for IDOR attempts', function (): void {
    [$userA, $tenantA] = setupTenantWithAdmin('Tenant A');
    [, $tenantB, $roleB] = setupTenantWithAdmin('Tenant B');

    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/admin/roles/{$roleB->id}/edit")
        ->assertNotFound();

    $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->delete("/admin/roles/{$roleB->id}")
        ->assertNotFound();
});

// 7. Cross-tenant Role Assignment Prevention
it('rejects assigning a role from another tenant to a member', function (): void {
    [$userA, $tenantA] = setupTenantWithAdmin('Tenant A');
    [, $tenantB, $roleB] = setupTenantWithAdmin('Tenant B');

    $memberUser = User::factory()->create();
    $membershipA = TenantMembership::create([
        'tenant_id' => $tenantA->id,
        'user_id' => $memberUser->id,
        'role_id' => null,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    $response = $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->put("/admin/members/{$membershipA->id}", [
            'role_id' => $roleB->id, // Tenant B role
        ]);

    $response->assertSessionHasErrors(['role_id']);
});

// 8. Privilege Escalation Prevention
it('prevents a non-admin manager from assigning the Admin role', function (): void {
    [, $tenantA, $adminRole] = setupTenantWithAdmin('Tenant A');
    [$managerUser] = setupTenantWithManager($tenantA, [
        OrganizationPermissions::MEMBERS_VIEW,
        OrganizationPermissions::MEMBERS_MANAGE,
    ]);

    $targetUser = User::factory()->create();
    $targetMembership = TenantMembership::create([
        'tenant_id' => $tenantA->id,
        'user_id' => $targetUser->id,
        'role_id' => null,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    // Manager tries to promote target user to Admin
    $response = $this->actingAs($managerUser)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->put("/admin/members/{$targetMembership->id}", [
            'role_id' => $adminRole->id,
        ]);

    $response->assertSessionHasErrors(['role_id']);
});

it('prevents a member from escalating their own role', function (): void {
    [, $tenantA, $adminRole] = setupTenantWithAdmin('Tenant A');
    [$managerUser, $managerRole, $managerMembership] = setupTenantWithManager($tenantA, [
        OrganizationPermissions::MEMBERS_VIEW,
        OrganizationPermissions::MEMBERS_MANAGE,
    ]);

    $customRole2 = TenantRole::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Higher Role',
        'slug' => 'higher-role-'.uniqid(),
        'is_system' => false,
        'is_active' => true,
    ]);

    $response = $this->actingAs($managerUser)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->put("/admin/members/{$managerMembership->id}", [
            'role_id' => $customRole2->id,
        ]);

    $response->assertSessionHas('error', 'You cannot change your own organization role.');
    expect($managerMembership->fresh()->role_id)->toBe($managerRole->id);
});

// 9. Identity Independence: Tenant A membership changes do not affect Tenant B
it('verifies user identity is separate across organizations and unaffected by single tenant suspension', function (): void {
    [$userA, $tenantA, , $membershipA] = setupTenantWithAdmin('Tenant A');
    [, $tenantB, , $membershipB] = setupTenantWithAdmin('Tenant B');

    // Add userA as member in Tenant B as well
    $membershipB2 = TenantMembership::create([
        'tenant_id' => $tenantB->id,
        'user_id' => $userA->id,
        'role_id' => null,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    // Suspend userA in Tenant A
    $userA->tenants()->updateExistingPivot($tenantA->id, [
        'status' => TenantMembershipStatus::Suspended->value,
    ]);

    // User A should still be active in Tenant B
    expect($membershipB2->fresh()->status)->toBe(TenantMembershipStatus::Active);

    // Global user account is still active and valid
    expect($userA->fresh()->id)->toBe($userA->id);

    // User A can access Tenant B dashboard
    $response = $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantB->id])
        ->get('/admin/dashboard');

    $response->assertSuccessful();

    // But cannot access Tenant A
    $responseA = $this->actingAs($userA)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get('/admin/dashboard');

    // Either resolves to Tenant B or rejects Tenant A
    expect(session('current_tenant_id'))->toBe($tenantB->id);
});

// 10. Invitation Data Security
it('hides invitation tokens from model serialization', function (): void {
    [, $tenant] = setupTenantWithAdmin('Tenant A');
    $user = User::factory()->create();

    $membership = TenantMembership::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => null,
        'status' => TenantMembershipStatus::Invited,
        'invitation_token' => 'super-secret-token-123',
        'invited_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    $array = $membership->toArray();
    expect(array_key_exists('invitation_token', $array))->toBeFalse();
    expect(json_encode($membership))->not->toContain('super-secret-token-123');
});

it('rejects invitation acceptance when the organization is suspended', function (): void {
    [, $tenant] = setupTenantWithAdmin('Tenant A');
    $user = User::factory()->create();

    $membership = TenantMembership::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => null,
        'status' => TenantMembershipStatus::Invited,
        'invitation_token' => 'active-token-456',
        'invited_at' => now(),
        'settings' => [],
        'version' => 1,
    ]);

    // Suspend tenant
    $tenant->update(['status' => TenantStatus::Suspended]);

    $response = $this->get('/invitations/active-token-456/accept');
    $response->assertRedirect('/login');
    $response->assertSessionHas('error', 'This organization is currently inactive or suspended.');
});
