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
