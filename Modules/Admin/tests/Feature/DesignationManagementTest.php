<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Domain\Enums\DesignationStatus;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function createDesignationTenantAdmin(string $tenantName = 'Test Organization'): array
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

it('allows organization admin to view own organization designations', function (): void {
    [$user, $tenant] = createDesignationTenantAdmin();

    Designation::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Senior Developer',
        'code' => 'SR-DEV',
    ]);

    $response = $this->actingAs($user)->get('/admin/designations');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Designations/Index')
            ->has('designations.data', 1)
            ->where('designations.data.0.name', 'Senior Developer')
            ->where('designations.data.0.code', 'SR-DEV')
    );
});

it('prevents organization admin from seeing other organization designations', function (): void {
    [$userA, $tenantA] = createDesignationTenantAdmin('Org A');
    [$userB, $tenantB] = createDesignationTenantAdmin('Org B');

    Designation::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Role A',
        'code' => 'ROLE-A',
    ]);

    Designation::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Role B',
        'code' => 'ROLE-B',
    ]);

    $response = $this->actingAs($userA)->get('/admin/designations');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Designations/Index')
            ->has('designations.data', 1)
            ->where('designations.data.0.name', 'Role A')
    );
});

it('allows admin to create a new designation', function (): void {
    [$user, $tenant] = createDesignationTenantAdmin();

    $response = $this->actingAs($user)->post('/admin/designations', [
        'name' => 'Product Manager',
        'code' => 'pm',
    ]);

    $response->assertRedirect('/admin/designations');

    $this->assertDatabaseHas('designations', [
        'tenant_id' => $tenant->id,
        'name' => 'Product Manager',
        'code' => 'PM',
        'status' => DesignationStatus::Active->value,
    ]);
});

it('enforces unique code per organization for designations', function (): void {
    [$user, $tenant] = createDesignationTenantAdmin();

    Designation::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Accountant',
        'code' => 'ACCT',
    ]);

    $response = $this->actingAs($user)->post('/admin/designations', [
        'name' => 'Chief Accountant',
        'code' => 'acct',
    ]);

    $response->assertSessionHasErrors(['code']);
});

it('allows admin to update a designation', function (): void {
    [$user, $tenant] = createDesignationTenantAdmin();

    $designation = Designation::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'QA Junior',
        'code' => 'QA-JR',
    ]);

    $response = $this->actingAs($user)->put("/admin/designations/{$designation->id}", [
        'name' => 'QA Lead',
        'code' => 'QA-LEAD',
    ]);

    $response->assertRedirect('/admin/designations');

    $this->assertDatabaseHas('designations', [
        'id' => $designation->id,
        'tenant_id' => $tenant->id,
        'name' => 'QA Lead',
        'code' => 'QA-LEAD',
    ]);
});

it('allows admin to deactivate and reactivate a designation', function (): void {
    [$user, $tenant] = createDesignationTenantAdmin();

    $designation = Designation::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => DesignationStatus::Active,
    ]);

    $this->actingAs($user)
        ->patch("/admin/designations/{$designation->id}/deactivate")
        ->assertRedirect();

    expect($designation->refresh()->status)->toBe(DesignationStatus::Inactive);

    $this->actingAs($user)
        ->patch("/admin/designations/{$designation->id}/activate")
        ->assertRedirect();

    expect($designation->refresh()->status)->toBe(DesignationStatus::Active);
});
