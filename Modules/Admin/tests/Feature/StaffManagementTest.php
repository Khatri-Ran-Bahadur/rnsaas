<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantStaff;

uses(RefreshDatabase::class);

function createStaffTenantAdmin(string $tenantName = 'Test Organization'): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => $tenantName,
        'status' => TenantStatus::Active,
    ]);

    $membership = TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    return [$user, $tenant, $membership];
}

it('allows organization admin to view own organization staff directory', function (): void {
    [$user, $tenant] = createStaffTenantAdmin();

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);
    $staffUser = User::factory()->create(['name' => 'John Developer', 'email' => 'john@example.com']);

    TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $staffUser->id,
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_code' => 'EMP-001',
    ]);

    $response = $this->actingAs($user)->get('/admin/staff');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Staff/Index')
            ->has('staff.data', 1)
            ->where('staff.data.0.employee_code', 'EMP-001')
            ->where('staff.data.0.user.name', 'John Developer')
    );
});

it('prevents organization admin from seeing other organization staff', function (): void {
    [$userA, $tenantA] = createStaffTenantAdmin('Org A');
    [$userB, $tenantB] = createStaffTenantAdmin('Org B');

    $branchA = Branch::factory()->create(['tenant_id' => $tenantA->id]);
    $deptA = Department::factory()->create(['tenant_id' => $tenantA->id]);
    $desigA = Designation::factory()->create(['tenant_id' => $tenantA->id]);

    $branchB = Branch::factory()->create(['tenant_id' => $tenantB->id]);
    $deptB = Department::factory()->create(['tenant_id' => $tenantB->id]);
    $desigB = Designation::factory()->create(['tenant_id' => $tenantB->id]);

    TenantStaff::factory()->create([
        'tenant_id' => $tenantA->id,
        'branch_id' => $branchA->id,
        'department_id' => $deptA->id,
        'designation_id' => $desigA->id,
        'employee_code' => 'STAFF-A',
    ]);

    TenantStaff::factory()->create([
        'tenant_id' => $tenantB->id,
        'branch_id' => $branchB->id,
        'department_id' => $deptB->id,
        'designation_id' => $desigB->id,
        'employee_code' => 'STAFF-B',
    ]);

    $response = $this->actingAs($userA)->get('/admin/staff');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Staff/Index')
            ->has('staff.data', 1)
            ->where('staff.data.0.employee_code', 'STAFF-A')
    );
});

it('allows admin to access staff create page with active options', function (): void {
    [$user, $tenant] = createStaffTenantAdmin();

    Branch::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kathmandu Office']);
    Department::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Development']);
    Designation::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Architect']);

    $response = $this->actingAs($user)->get('/admin/staff/create');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Staff/Create')
            ->has('branches', 1)
            ->has('departments', 1)
            ->has('designations', 1)
            ->has('availableMembers')
    );
});

it('allows admin to onboard a new staff member', function (): void {
    [$user, $tenant] = createStaffTenantAdmin();

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->post('/admin/staff', [
        'name' => 'Alice Smith',
        'email' => 'alice@company.com',
        'phone' => '+977 9812345678',
        'employee_code' => 'emp-101',
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'joining_date' => '2026-09-01',
    ]);

    $response->assertRedirect('/admin/staff');

    $this->assertDatabaseHas('users', [
        'name' => 'Alice Smith',
        'email' => 'alice@company.com',
    ]);

    $this->assertDatabaseHas('tenant_staff', [
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_code' => 'EMP-101',
        'employment_status' => EmploymentStatus::Active->value,
    ]);
});

it('prevents duplicate employee code in same tenant', function (): void {
    [$user, $tenant] = createStaffTenantAdmin();

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);

    TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_code' => 'EMP-200',
    ]);

    $response = $this->actingAs($user)->post('/admin/staff', [
        'name' => 'Bob Builder',
        'email' => 'bob@company.com',
        'employee_code' => 'emp-200',
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
    ]);

    $response->assertSessionHasErrors(['employee_code']);
});

it('allows admin to update staff member details', function (): void {
    [$user, $tenant] = createStaffTenantAdmin();

    $branch1 = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $branch2 = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);
    $staffUser = User::factory()->create(['name' => 'Old Name', 'email' => 'staff@company.com']);

    $staff = TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $staffUser->id,
        'branch_id' => $branch1->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_code' => 'EMP-300',
    ]);

    $response = $this->actingAs($user)->put("/admin/staff/{$staff->id}", [
        'name' => 'New Staff Name',
        'phone' => '+977 9841000000',
        'employee_code' => 'EMP-301',
        'branch_id' => $branch2->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'joining_date' => '2026-08-15',
        'employment_status' => 'active',
    ]);

    $response->assertRedirect('/admin/staff');

    expect($staffUser->refresh()->name)->toBe('New Staff Name');
    expect($staff->refresh()->branch_id)->toBe($branch2->id);
    expect($staff->employee_code)->toBe('EMP-301');
});

it('allows admin to suspend and reactivate a staff member', function (): void {
    [$user, $tenant] = createStaffTenantAdmin();

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);

    $staff = TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employment_status' => EmploymentStatus::Active,
    ]);

    $this->actingAs($user)
        ->patch("/admin/staff/{$staff->id}/suspend")
        ->assertRedirect();

    expect($staff->refresh()->employment_status)->toBe(EmploymentStatus::Suspended);

    $this->actingAs($user)
        ->patch("/admin/staff/{$staff->id}/activate")
        ->assertRedirect();

    expect($staff->refresh()->employment_status)->toBe(EmploymentStatus::Active);
});

it('allows converting an existing organization member to staff reusing user and membership without duplicates', function (): void {
    [$admin, $tenant] = createStaffTenantAdmin();

    $bimala = User::factory()->create([
        'name' => 'Bimala Sharma',
        'email' => 'bimala@company.com',
    ]);

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $bimala->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);

    $initialUserCount = User::count();
    $initialMembershipCount = TenantMembership::count();

    $response = $this->actingAs($admin)->post('/admin/staff', [
        'mode' => 'existing_member',
        'user_id' => $bimala->id,
        'employee_code' => 'EMP-BIMALA',
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'joining_date' => '2026-09-01',
        'employment_status' => 'active',
    ]);

    $response->assertRedirect('/admin/staff');

    // Asserts existing user and membership were reused without creating duplicate rows
    expect(User::count())->toBe($initialUserCount);
    expect(TenantMembership::count())->toBe($initialMembershipCount);

    $this->assertDatabaseHas('tenant_staff', [
        'tenant_id' => $tenant->id,
        'user_id' => $bimala->id,
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_code' => 'EMP-BIMALA',
        'employment_status' => EmploymentStatus::Active->value,
    ]);
});

it('prevents adding a member as staff if they are already registered as staff in current organization', function (): void {
    [$admin, $tenant] = createStaffTenantAdmin();

    $member = User::factory()->create(['email' => 'staff.member@company.com']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);

    TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_code' => 'EMP-EXISTING',
    ]);

    // Attempt 1: by user_id
    $responseById = $this->actingAs($admin)->post('/admin/staff', [
        'mode' => 'existing_member',
        'user_id' => $member->id,
        'employee_code' => 'EMP-NEW-1',
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
    ]);
    $responseById->assertSessionHasErrors(['user_id']);

    // Attempt 2: by new person flow with same email
    $responseByEmail = $this->actingAs($admin)->post('/admin/staff', [
        'name' => 'Duplicate Person',
        'email' => 'staff.member@company.com',
        'employee_code' => 'EMP-NEW-2',
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
    ]);
    $responseByEmail->assertSessionHasErrors(['email']);
});

it('excludes members from another tenant and members who are already staff from available members in create page', function (): void {
    [$adminA, $tenantA] = createStaffTenantAdmin('Org A');
    [$adminB, $tenantB] = createStaffTenantAdmin('Org B');

    // Member A1: active member in Tenant A, not staff
    $memberA1 = User::factory()->create(['name' => 'Available Member A1']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $memberA1->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    // Member A2: active member in Tenant A, but already staff
    $memberA2 = User::factory()->create(['name' => 'Existing Staff Member A2']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $memberA2->id,
        'status' => TenantMembershipStatus::Active,
    ]);
    TenantStaff::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $memberA2->id,
    ]);

    // Member B: active member in Tenant B
    $memberB = User::factory()->create(['name' => 'Foreign Member B']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $memberB->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($adminA)->get('/admin/staff/create');

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) use ($memberA1): void {
        $page->component('Admin/Staff/Create')
            ->has('availableMembers', 2) // AdminA and MemberA1
            ->where('availableMembers', function ($members) use ($memberA1) {
                $ids = collect($members)->pluck('id')->all();

                return in_array($memberA1->id, $ids, true);
            });
    });
});

it('displays newly created staff member in the staff directory index', function (): void {
    [$admin, $tenant] = createStaffTenantAdmin();

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($admin)->post('/admin/staff', [
        'name' => 'Visible Employee',
        'email' => 'visible@company.com',
        'employee_code' => 'VIS-101',
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
    ]);

    $response = $this->actingAs($admin)->get('/admin/staff');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Staff/Index')
            ->has('staff.data', 1)
            ->where('staff.data.0.employee_code', 'VIS-101')
            ->where('staff.data.0.user.name', 'Visible Employee')
    );
});

it('correctly reports Staff and Not Staff status on the organization members page', function (): void {
    [$admin, $tenant] = createStaffTenantAdmin();

    $memberStaff = User::factory()->create(['name' => 'Bimala Staff']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $memberStaff->id,
        'status' => TenantMembershipStatus::Active,
    ]);
    TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $memberStaff->id,
    ]);

    $memberNonStaff = User::factory()->create(['name' => 'Johnnie Member']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $memberNonStaff->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/members');

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page): void {
        $page->component('Admin/Members/Index')
            ->has('members.data', 3) // Admin, Bimala Staff, Johnnie Member
            ->where('members.data', function ($members) {
                $bimala = collect($members)->firstWhere('name', 'Bimala Staff');
                $johnnie = collect($members)->firstWhere('name', 'Johnnie Member');

                return (bool) $bimala['is_staff'] === true
                    && (bool) $johnnie['is_staff'] === false;
            });
    });
});
