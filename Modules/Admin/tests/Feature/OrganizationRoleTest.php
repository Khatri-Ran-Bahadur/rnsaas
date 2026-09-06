<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Application\Actions\Tenant\ProvisionTenantDefaultsAction;
use Modules\Tenancy\Domain\Constants\OrganizationPermissions;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;

uses(RefreshDatabase::class);

function createRoleTenantAdmin(string $tenantName = 'Role Test Org'): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => $tenantName,
        'status' => TenantStatus::Active,
    ]);

    app(ProvisionTenantDefaultsAction::class)->handle($tenant);

    $adminRole = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'admin')->first();

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $adminRole?->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    return [$user, $tenant, $adminRole];
}

it('allows admin to view organization roles', function (): void {
    [$admin, $tenant] = createRoleTenantAdmin();

    $response = $this->actingAs($admin)->get('/admin/roles');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles', 3) // Admin, Manager, Staff default roles
    );
});

it('isolates roles between different organizations', function (): void {
    [$adminA, $tenantA] = createRoleTenantAdmin('Org A');
    [$adminB, $tenantB] = createRoleTenantAdmin('Org B');

    TenantRole::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Custom Role A',
        'slug' => 'custom-role-a',
        'is_system' => false,
        'is_active' => true,
    ]);

    $response = $this->actingAs($adminB)->get('/admin/roles');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->where('roles.0.name', fn ($name) => $name !== 'Custom Role A')
            ->where('roles.1.name', fn ($name) => $name !== 'Custom Role A')
            ->where('roles.2.name', fn ($name) => $name !== 'Custom Role A')
    );
});

it('allows admin to create a custom role with permissions', function (): void {
    [$admin, $tenant] = createRoleTenantAdmin();

    $response = $this->actingAs($admin)->post('/admin/roles', [
        'name' => 'Support Agent',
        'description' => 'Handles member tickets and department views',
        'permissions' => [
            OrganizationPermissions::DASHBOARD_VIEW,
            OrganizationPermissions::DEPARTMENTS_VIEW,
        ],
        'is_active' => true,
    ]);

    $response->assertRedirect('/admin/roles');

    $this->assertDatabaseHas('tenant_roles', [
        'tenant_id' => $tenant->id,
        'name' => 'Support Agent',
        'slug' => 'support-agent',
        'is_system' => 0,
    ]);

    $role = TenantRole::where('tenant_id', $tenant->id)->where('slug', 'support-agent')->firstOrFail();
    expect($role->permissions()->count())->toBe(2);
    expect($role->hasPermission(OrganizationPermissions::DASHBOARD_VIEW))->toBeTrue();
    expect($role->hasPermission(OrganizationPermissions::DEPARTMENTS_VIEW))->toBeTrue();
    expect($role->hasPermission(OrganizationPermissions::DEPARTMENTS_MANAGE))->toBeFalse();
});

it('allows admin to update custom role permissions', function (): void {
    [$admin, $tenant] = createRoleTenantAdmin();

    $role = TenantRole::create([
        'tenant_id' => $tenant->id,
        'name' => 'Shift Lead',
        'slug' => 'shift-lead',
        'is_system' => false,
        'is_active' => true,
    ]);
    $role->syncPermissions([OrganizationPermissions::DASHBOARD_VIEW]);

    $response = $this->actingAs($admin)->put("/admin/roles/{$role->id}", [
        'name' => 'Senior Shift Lead',
        'description' => 'Updated description',
        'permissions' => [
            OrganizationPermissions::DASHBOARD_VIEW,
            OrganizationPermissions::STAFF_VIEW,
        ],
        'is_active' => true,
    ]);

    $response->assertRedirect('/admin/roles');
    expect($role->refresh()->name)->toBe('Senior Shift Lead');
    expect($role->permissions()->count())->toBe(2);
});

it('allows deleting custom roles that have no assigned members', function (): void {
    [$admin, $tenant] = createRoleTenantAdmin();

    $role = TenantRole::create([
        'tenant_id' => $tenant->id,
        'name' => 'Temporary Role',
        'slug' => 'temporary-role',
        'is_system' => false,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/roles/{$role->id}");

    $response->assertRedirect('/admin/roles');
    $this->assertDatabaseMissing('tenant_roles', ['id' => $role->id]);
});

it('prevents deleting system roles or roles with assigned members', function (): void {
    [$admin, $tenant, $adminRole] = createRoleTenantAdmin();

    // 1. Try deleting system role
    $this->actingAs($admin)->delete("/admin/roles/{$adminRole->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('tenant_roles', ['id' => $adminRole->id]);

    // 2. Try deleting custom role with assigned member
    $customRole = TenantRole::create([
        'tenant_id' => $tenant->id,
        'name' => 'Assigned Role',
        'slug' => 'assigned-role',
        'is_system' => false,
        'is_active' => true,
    ]);

    $memberUser = User::factory()->create();
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $memberUser->id,
        'role_id' => $customRole->id,
    ]);

    $this->actingAs($admin)->delete("/admin/roles/{$customRole->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('tenant_roles', ['id' => $customRole->id]);
});
