<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('authenticated user can invite a member to a tenant', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');
    $tenant = Tenant::factory()->create();

    $response = $this->actingAs($admin)->post("/superadmin/tenants/{$tenant->id}/members/invite", [
        'email' => 'newmember@example.com',
        'name' => 'New Member',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $invitedUser = User::where('email', 'newmember@example.com')->first();
    expect($invitedUser)->not->toBeNull();

    $this->assertDatabaseHas('tenant_user', [
        'tenant_id' => $tenant->id,
        'user_id' => $invitedUser->id,
        'status' => TenantMembershipStatus::Invited->value,
    ]);
});

test('authenticated user can suspend, revoke, and reactivate a tenant member', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');
    $tenant = Tenant::factory()->create();
    $member = User::factory()->create();

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    // Suspend
    $response = $this->actingAs($admin)->post("/superadmin/tenants/{$tenant->id}/members/{$member->id}/suspend");
    $response->assertRedirect();
    $this->assertDatabaseHas('tenant_user', [
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'status' => TenantMembershipStatus::Suspended->value,
    ]);

    // Reactivate
    $response = $this->actingAs($admin)->post("/superadmin/tenants/{$tenant->id}/members/{$member->id}/reactivate");
    $response->assertRedirect();
    $this->assertDatabaseHas('tenant_user', [
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'status' => TenantMembershipStatus::Active->value,
    ]);

    // Revoke
    $response = $this->actingAs($admin)->post("/superadmin/tenants/{$tenant->id}/members/{$member->id}/revoke");
    $response->assertRedirect();
    $this->assertDatabaseHas('tenant_user', [
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'status' => TenantMembershipStatus::Revoked->value,
    ]);
});
