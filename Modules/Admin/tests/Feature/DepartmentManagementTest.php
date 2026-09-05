<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Domain\Enums\DepartmentStatus;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function createDepartmentTenantAdmin(string $tenantName = 'Test Organization'): array
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

it('allows organization admin to view own organization departments', function (): void {
    [$user, $tenant] = createDepartmentTenantAdmin();

    Department::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Engineering',
        'code' => 'ENG',
    ]);

    $response = $this->actingAs($user)->get('/admin/departments');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.name', 'Engineering')
            ->where('departments.data.0.code', 'ENG')
    );
});

it('prevents organization admin from seeing other organization departments', function (): void {
    [$userA, $tenantA] = createDepartmentTenantAdmin('Org A');
    [$userB, $tenantB] = createDepartmentTenantAdmin('Org B');

    Department::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Engineering A',
        'code' => 'ENG-A',
    ]);

    Department::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Marketing B',
        'code' => 'MKT-B',
    ]);

    $response = $this->actingAs($userA)->get('/admin/departments');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.name', 'Engineering A')
    );
});

it('allows admin to create a new department', function (): void {
    [$user, $tenant] = createDepartmentTenantAdmin();

    $response = $this->actingAs($user)->post('/admin/departments', [
        'name' => 'Human Resources',
        'code' => 'hr',
    ]);

    $response->assertRedirect('/admin/departments');

    $this->assertDatabaseHas('departments', [
        'tenant_id' => $tenant->id,
        'name' => 'Human Resources',
        'code' => 'HR',
        'status' => DepartmentStatus::Active->value,
    ]);
});

it('enforces unique code per organization for departments', function (): void {
    [$user, $tenant] = createDepartmentTenantAdmin();

    Department::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Operations',
        'code' => 'OPS',
    ]);

    $response = $this->actingAs($user)->post('/admin/departments', [
        'name' => 'Operations 2',
        'code' => 'ops',
    ]);

    $response->assertSessionHasErrors(['code']);
});

it('allows admin to update a department', function (): void {
    [$user, $tenant] = createDepartmentTenantAdmin();

    $department = Department::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Tech Support',
        'code' => 'SUPP',
    ]);

    $response = $this->actingAs($user)->put("/admin/departments/{$department->id}", [
        'name' => 'Customer Support',
        'code' => 'CS',
    ]);

    $response->assertRedirect('/admin/departments');

    $this->assertDatabaseHas('departments', [
        'id' => $department->id,
        'tenant_id' => $tenant->id,
        'name' => 'Customer Support',
        'code' => 'CS',
    ]);
});

it('allows admin to deactivate and reactivate a department', function (): void {
    [$user, $tenant] = createDepartmentTenantAdmin();

    $department = Department::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => DepartmentStatus::Active,
    ]);

    $this->actingAs($user)
        ->patch("/admin/departments/{$department->id}/deactivate")
        ->assertRedirect();

    expect($department->refresh()->status)->toBe(DepartmentStatus::Inactive);

    $this->actingAs($user)
        ->patch("/admin/departments/{$department->id}/activate")
        ->assertRedirect();

    expect($department->refresh()->status)->toBe(DepartmentStatus::Active);
});
